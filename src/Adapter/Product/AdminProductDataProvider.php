<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace PrestaShop\PrestaShop\Adapter\Product;

use AppKernel;
use Configuration;
use Context;
use Currency;
use Db;
use DbQuery;
use Doctrine\ORM\EntityManager;
use Hook;
use PrestaShop\PrestaShop\Adapter\Admin\AbstractAdminQueryBuilder;
use PrestaShop\PrestaShop\Adapter\ImageManager;
use PrestaShop\PrestaShop\Adapter\Validate;
use PrestaShopBundle\Entity\AdminFilter;
use PrestaShopBundle\Service\DataProvider\Admin\ProductInterface;
use Product;
use Psr\Cache\CacheItemPoolInterface;
use StockAvailable;
use Tools;

/**
 * Data provider for new Architecture, about Product object model.
 *
 * This class will provide data from DB / ORM about Products for the Admin interface.
 * This is an Adapter that works with the Legacy code and persistence behaviors.
 */
class AdminProductDataProvider extends AbstractAdminQueryBuilder implements ProductInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(
        EntityManager $entityManager,
        ImageManager $imageManager,
        CacheItemPoolInterface $cache
    ) {
        $this->entityManager = $entityManager;
        $this->imageManager = $imageManager;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getPersistedFilterParameters()
    {
        $employee = Context::getContext()->employee;
        $employeeId = $employee->id ?: 0;

        $cachedFilters = $this->cache->getItem("app.product_filters_${employeeId}");

        if (!$cachedFilters->isHit()) {
            $shop = Context::getContext()->shop;
            $filter = $this->entityManager->getRepository(AdminFilter::class)->findOneBy([
                'employee' => $employeeId,
                'shop' => $shop->id ?: 0,
                'controller' => 'ProductController',
                'action' => 'catalogAction',
            ]);

            /** @var $filter AdminFilter */
            if (null === $filter) {
                $filters = AdminFilter::getProductCatalogEmptyFilter();
            } else {
                $filters = $filter->getProductCatalogFilter();
            }

            $cachedFilters->set($filters);
            $this->cache->save($cachedFilters);
        }

        return $cachedFilters->get();
    }

    /**
     * {@inheritdoc}
     */
    public function isCategoryFiltered()
    {
        $filters = $this->getPersistedFilterParameters();

        return !empty($filters['filter_category']) && $filters['filter_category'] > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isColumnFiltered()
    {
        $filters = $this->getPersistedFilterParameters();
        foreach ($filters as $filterKey => $filterValue) {
            if (strpos($filterKey, 'filter_column_') === 0 && $filterValue !== '') {
                return true; // break at first column filter found
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function persistFilterParameters(array $parameters)
    {
        $employee = Context::getContext()->employee;
        $shop = Context::getContext()->shop;
        $filter = $this->entityManager->getRepository(AdminFilter::class)->findOneBy([
            'employee' => $employee->id ?: 0,
            'shop' => $shop->id ?: 0,
            'controller' => 'ProductController',
            'action' => 'catalogAction',
        ]);

        if (!$filter) {
            $filter = new AdminFilter();
            $filter->setEmployee($employee->id ?: 0)->setShop($shop->id ?: 0)->setController('ProductController')->setAction('catalogAction');
        }

        $filter->setProductCatalogFilter($parameters);
        $this->entityManager->persist($filter);

        // if each filter is == '', then remove item from DB :)
        if (count(array_diff($filter->getProductCatalogFilter(), [''])) == 0) {
            $this->entityManager->remove($filter);
        }

        $this->entityManager->flush();

        //Flush cache
        $employee = Context::getContext()->employee;
        $employeeId = $employee->id ?: 0;

        $this->cache->deleteItem("app.product_filters_${employeeId}");
    }

    /**
     * {@inheritdoc}
     */
    public function combinePersistentCatalogProductFilter($paramsIn = [], $avoidPersistence = false)
    {
        // retrieve persisted filter parameters
        $persistedParams = $this->getPersistedFilterParameters();
        // merge with new values
        $paramsOut = array_merge($persistedParams, (array) $paramsIn);
        // persist new values
        if (!$avoidPersistence) {
            $this->persistFilterParameters($paramsOut);
        }

        // return new values
        return $paramsOut;
    }

    /**
     * {@inheritdoc}
     */
    public function getCatalogProductList(
        $offset,
        $limit,
        $orderBy,
        $sortOrder,
        $post = [],
        $avoidPersistence = false,
        $formatCldr = true
    ) {
        $offset = (int) $offset;
        $limit = (int) $limit;
        $orderBy = Validate::isOrderBy($orderBy) ? $orderBy : 'id_product';
        $sortOrder = Validate::isOrderWay($sortOrder) ? $sortOrder : 'desc';

        $filterParams = $this->combinePersistentCatalogProductFilter(array_merge(
            $post,
            ['last_offset' => $offset, 'last_limit' => $limit, 'last_orderBy' => $orderBy, 'last_sortOrder' => $sortOrder]
        ), $avoidPersistence);
        $filterParams = AdminFilter::sanitizeFilterParameters($filterParams);

        $showPositionColumn = $this->isCategoryFiltered();
        if ($orderBy == 'position_ordering' && $showPositionColumn) {
            foreach ($filterParams as $key => $param) {
                if (strpos($key, 'filter_column_') === 0) {
                    $filterParams[$key] = '';
                }
            }
        }
        if ($orderBy == 'position_ordering') {
            $orderBy = 'position';
        }

        $idShop = Context::getContext()->shop->id;
        $idLang = Context::getContext()->language->id;

        $sqlSelect = [
            'id_product' => ['table' => 'p', 'field' => 'id_product', 'filtering' => ' %s '],
            'reference' => ['table' => 'p', 'field' => 'reference', 'filtering' => self::FILTERING_LIKE_BOTH],
            'price' => ['table' => 'sa', 'field' => 'price', 'filtering' => ' %s '],
            'id_shop_default' => ['table' => 'p', 'field' => 'id_shop_default'],
            'is_virtual' => ['table' => 'p', 'field' => 'is_virtual'],
            'name' => ['table' => 'pl', 'field' => 'name', 'filtering' => self::FILTERING_LIKE_BOTH],
            'link_rewrite' => ['table' => 'pl', 'field' => 'link_rewrite', 'filtering' => self::FILTERING_LIKE_BOTH],
            'active' => ['table' => 'sa', 'field' => 'active', 'filtering' => self::FILTERING_EQUAL_NUMERIC],
            'shopname' => ['table' => 'shop', 'field' => 'name'],
            'id_image' => ['table' => 'image_shop', 'field' => 'id_image'],
            'name_category' => ['table' => 'cl', 'field' => 'name', 'filtering' => self::FILTERING_LIKE_BOTH],
            'price_final' => '0',
            'nb_downloadable' => ['table' => 'pd', 'field' => 'nb_downloadable'],
            'sav_quantity' => ['table' => 'sav', 'field' => 'quantity', 'filtering' => ' %s '],
            'badge_danger' => ['select' => 'IF(sav.`quantity`<=0, 1, 0)', 'filtering' => 'IF(sav.`quantity`<=0, 1, 0) = %s'],
        ];
        $sqlTable = [
            'p' => 'product',
            'pl' => [
                'table' => 'product_lang',
                'join' => 'LEFT JOIN',
                'on' => 'pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . $idLang . ' AND pl.`id_shop` = ' . $idShop,
            ],
            'sav' => [
                'table' => 'stock_available',
                'join' => 'LEFT JOIN',
                'on' => 'sav.`id_product` = p.`id_product` AND sav.`id_product_attribute` = 0' .
                StockAvailable::addSqlShopRestriction(null, $idShop, 'sav'),
            ],
            'sa' => [
                'table' => 'product_shop',
                'join' => 'JOIN',
                'on' => 'p.`id_product` = sa.`id_product` AND sa.id_shop = ' . $idShop,
            ],
            'cl' => [
                'table' => 'category_lang',
                'join' => 'LEFT JOIN',
                'on' => 'sa.`id_category_default` = cl.`id_category` AND cl.`id_lang` = ' . $idLang . ' AND cl.id_shop = ' . $idShop,
            ],
            'c' => [
                'table' => 'category',
                'join' => 'LEFT JOIN',
                'on' => 'c.`id_category` = cl.`id_category`',
            ],
            'shop' => [
                'table' => 'shop',
                'join' => 'LEFT JOIN',
                'on' => 'shop.id_shop = ' . $idShop,
            ],
            'image_shop' => [
                'table' => 'image_shop',
                'join' => 'LEFT JOIN',
                'on' => 'image_shop.`id_product` = p.`id_product` AND image_shop.`cover` = 1 AND image_shop.id_shop = ' . $idShop,
            ],
            'i' => [
                'table' => 'image',
                'join' => 'LEFT JOIN',
                'on' => 'i.`id_image` = image_shop.`id_image`',
            ],
            'pd' => [
                'table' => 'product_download',
                'join' => 'LEFT JOIN',
                'on' => 'pd.`id_product` = p.`id_product`',
            ],
        ];
        $sqlWhere = ['AND', 1];
        $sqlOrder = [$orderBy . ' ' . $sortOrder];
        if ($orderBy != 'id_product') {
            $sqlOrder[] = 'id_product asc'; // secondary order by (useful when ordering by active, quantity, price, etc...)
        }
        $sqlLimit = $offset . ', ' . $limit;

        // Column 'position' added if filtering by category
        if ($showPositionColumn) {
            $filteredCategoryId = (int) $filterParams['filter_category'];
            $sqlSelect['position'] = ['table' => 'cp', 'field' => 'position'];
            $sqlTable['cp'] = [
                'table' => 'category_product',
                'join' => 'INNER JOIN',
                'on' => 'cp.`id_product` = p.`id_product` AND cp.`id_category` = ' . $filteredCategoryId,
            ];
        } elseif ($orderBy == 'position') {
            // We do not show position column, so we do not join the table, so we do not order by position!
            $sqlOrder = ['id_product ASC'];
        }

        $sqlGroupBy = [];

        // exec legacy hook but with different parameters (retro-compat < 1.7 is broken here)
        Hook::exec('actionAdminProductsListingFieldsModifier', [
            '_ps_version' => AppKernel::VERSION,
            'sql_select' => &$sqlSelect,
            'sql_table' => &$sqlTable,
            'sql_where' => &$sqlWhere,
            'sql_group_by' => &$sqlGroupBy,
            'sql_order' => &$sqlOrder,
            'sql_limit' => &$sqlLimit,
        ]);
        foreach ($filterParams as $filterParam => $filterValue) {
            if (!$filterValue && $filterValue !== '0') {
                continue;
            }
            if (strpos($filterParam, 'filter_column_') === 0) {
                $filterValue = Db::getInstance()->escape($filterValue, in_array($filterParam, [
                    'filter_column_id_product',
                    'filter_column_sav_quantity',
                    'filter_column_price',
                ]), true);
                $field = substr($filterParam, 14); // 'filter_column_' takes 14 chars
                if (isset($sqlSelect[$field]['table'])) {
                    $sqlWhere[] = $sqlSelect[$field]['table'] . '.`' . $sqlSelect[$field]['field'] . '` ' . sprintf($sqlSelect[$field]['filtering'], $filterValue);
                } else {
                    $sqlWhere[] = '(' . sprintf($sqlSelect[$field]['filtering'], $filterValue) . ')';
                }
            }
            // for 'filter_category', see next if($showPositionColumn) block.
        }
        $sqlWhere[] = 'state = ' . Product::STATE_SAVED;

        // exec legacy hook but with different parameters (retro-compat < 1.7 is broken here)
        Hook::exec('actionAdminProductsListingFieldsModifier', [
            '_ps_version' => AppKernel::VERSION,
            'sql_select' => &$sqlSelect,
            'sql_table' => &$sqlTable,
            'sql_where' => &$sqlWhere,
            'sql_group_by' => &$sqlGroupBy,
            'sql_order' => &$sqlOrder,
            'sql_limit' => &$sqlLimit,
        ]);

        $sql = $this->compileSqlQuery($sqlSelect, $sqlTable, $sqlWhere, $sqlGroupBy, $sqlOrder, $sqlLimit);
        $products = Db::getInstance()->executeS($sql, true, false);
        $total = Db::getInstance()->executeS('SELECT FOUND_ROWS();', true, false);
        $total = $total[0]['FOUND_ROWS()'];

        // post treatment
        $currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        $localeCldr = Tools::getContextLocale(Context::getContext());

        foreach ($products as &$product) {
            $product['total'] = $total; // total product count (filtered)
            $product['price_final'] = Product::getPriceStatic(
                $product['id_product'],
                true,
                null,
                Context::getContext()->getComputingPrecision(),
                null,
                false,
                false,
                1,
                true,
                null,
                null,
                null,
                $nothing,
                true,
                true
            );

            if ($formatCldr) {
                $product['price'] = $localeCldr->formatPrice($product['price'], $currency->iso_code);
                $product['price_final'] = $localeCldr->formatPrice($product['price_final'], $currency->iso_code);
            }
            $product['image'] = $this->imageManager->getThumbnailForListing($product['id_image']);
            $product['image_link'] = Context::getContext()->link->getImageLink($product['link_rewrite'], $product['id_image']);
        }

        // post treatment by hooks
        // exec legacy hook but with different parameters (retro-compat < 1.7 is broken here)
        Hook::exec('actionAdminProductsListingResultsModifier', [
            '_ps_version' => AppKernel::VERSION,
            'products' => &$products,
            'total' => $total,
        ]);

        return $products;
    }

    /**
     * {@inheritdoc}
     */
    public function countAllProducts()
    {
        $idShop = Context::getContext()->shop->id;

        $query = new DbQuery();
        $query->select('COUNT(ps.id_product)');
        $query->from('product_shop', 'ps');
        $query->where('ps.id_shop = ' . (int) $idShop);

        $total = Db::getInstance()->getValue($query);

        return (int) $total;
    }

    /**
     * Translates new Core route parameters into their Legacy equivalent.
     *
     * @param string[] $coreParameters The new Core route parameters
     *
     * @return array<string, int|string> The URL parameters for Legacy URL (GETs)
     */
    public function mapLegacyParametersProductForm($coreParameters = [])
    {
        $params = [];
        if ($coreParameters['id'] == '0') {
            $params['addproduct'] = 1;
        } else {
            $params['updateproduct'] = 1;
            $params['id_product'] = $coreParameters['id'];
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginationLimitChoices()
    {
        $paginationLimitChoices = [20, 50, 100];

        $memory = Tools::getMemoryLimit();

        if ($memory >= 512 * 1024 * 1024) {
            $paginationLimitChoices[] = 300;
        }
        if ($memory >= 1536 * 1024 * 1024) {
            $paginationLimitChoices[] = 1000;
        }

        return $paginationLimitChoices;
    }

    /**
     * {@inheritdoc}
     */
    public function isNewProductDefaultActivated()
    {
        return (bool) Configuration::get('PS_PRODUCT_ACTIVATION_DEFAULT');
    }
}
