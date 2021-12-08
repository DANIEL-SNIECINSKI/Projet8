# Pour exportation des résultats des tests vers des fichiers XML 

import xmlrunner

# Le module unittest est un Python intégré basé sur JUnit de Java. Ce module fournit le cadre pour l'organisation des cas de test

import unittest

# Le module selenium.webdriver fournit toutes les implémentations de WebDriver

from selenium import webdriver

# Définition des options néccessaire à l'éxécution des tests dans Jenkins

from selenium.webdriver.chrome.options import Options
chrome_options = Options()
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument("--headless")
chrome_options.add_argument("--disable-gpu")
chrome_options.binary_location = '/usr/bin/google-chrome'

# La classe Keys fournit des touches dans le clavier telles que RETURN, F1, ALT, etc.

from selenium.webdriver.common.keys import Keys

# La classe de cas de test est héritée de unittest.TestCase
# Hériter de la classe TestCase est le moyen de dire au module unittest qu'il s'agit d'un cas de test

class admin_is_ready(unittest.TestCase):

# Le setUp fait partie de l'initialisation, cette méthode sera appelée avant chaque fonction de test écrite dans cette classe de cas de test
# Ici, on crée l'instance de Chrome WebDriver


    def setUp(self):
        self.driver = webdriver.Chrome('/usr/bin/chromedriver', options=chrome_options)

# C'est la méthode des cas de test. La méthode des cas de test doit toujours commencer par les caractères test 
# La première ligne à l'intérieur de cette méthode crée une référence locale à l'objet pilote créé dans la méthode setUp 

    def test_dftg_results(self):
        driver = self.driver

# La méthode driver.get naviguera vers une page donnée par l'URL
# WebDriver attendra que la page soit complètement chargée
# (c'est-à-dire que l'événement "onload" se soit déclenché) avant de rendre le contrôle à votre test ou script

        driver.get("http://127.0.0.1/prestashop/admin338j8tqkk")

# La ligne suivante est une assertion pour confirmer que le titre contient le mot "dftg"

        self.assertIn("dftg", driver.title)

# Après la soumission de la page, on doit obtenir le résultat selon la recherche s'il y en a
# Pour s'assurer que certains résultats sont trouvés, on fait une assertion 

        assert "No results found." not in driver.page_source

# La méthode tearDown sera appelée après chaque méthode de test
# C'est un endroit pour faire toutes les actions de nettoyage. Dans la méthode actuelle, la fenêtre du navigateur est fermée# Vous pouvez également appeler la méthode quit au lieu de close .
# Le quit va quitter le navigateur entier, alors à proximité se fermer un onglet, mais si elle est la seule onglet ouvert,
# par défaut le plus navigateur va quitter entièrement

    def tearDown(self):
        self.driver.close()

# Les lignes finales sont du code passe-partout pour exécuter la suite de tests :

if __name__ == '__main__':
    unittest.main() 
