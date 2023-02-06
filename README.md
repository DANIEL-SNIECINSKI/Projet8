PROJET VALIDÉ
Automatisez les tests d'une équipe de qualification
80 heures
Mis à jour le mardi 7 juillet 2020
Scénario
Le poste d'expert DevOps peut exister au sein d'une équipe de développement ou de test. Aujourd'hui, vous intégrez l'équipe de test d’une célèbre entreprise de vente de chaussure, l’entreprise Don’t Feed The Groll, plus couramment appelé DFTG. L’équipe que vous intégrez est en charge de la Qualification, c’est-à-dire les différents contrôles de qualités et de fonctionnalités des différentes “release” qui seront livrées du site.

Le site de DFTG est basé sur Prestashop, un site de e-commerce codé en PHP, par plusieurs équipes de développeurs, front et back.

Le code contient déjà des tests unitaires, mais en revanche, tous les tests fonctionnels se font manuellement pour l’instant : c’est très fastidieux !

Vous êtes appelé à la rescousse pour créer des tests automatisés avec l’outil Selenium. Vous connaissez déjà Gitlab, il est temps de découvrir Jenkins, et de pousser plus loin vos connaissances dans le fabuleux monde des tests !

Instructions :
Etape 1 : Pre-build
Installer Jenkins dans votre machine virtuelle, et les outils nécessaires pour faire tourner les tests.

Votre premier objectif sera de lancer les tests unitaires du projet Prestashop dans un environnement de test adéquat.

Vous lancerez ces tests avec Jenkins.

Le bon fonctionnement de ces tests signifiera que le projet peut passer en phase “build”.

Etape 2 : Build
Vous allez packager l’application Prestashop dans un container Docker.

Pour vous familiariser avec l’outil, vous pouvez installer Prestashop dans votre machine virtuelle en installant les composants un à un.

Vous placerez ensuite la partie code PHP dans un container Docker, vous créerez un container pour MySQL, et vous relierez tous ces containers ensemble à l’aide de docker-compose.

Les construction des images (docker build) devront être lancées par Jenkins.

Etape 3 : Tests
Vous allez créer un test fonctionnel avec Selenium.

Ce test ouvrira une page de Prestashop et lancera automatiquement un test permettant de valider son fonctionnement, et donc que le code fonctionne, et que le build s’est correctement déroulé.

Dans ce contexte, le test fonctionnel tient lieu de test d’intégration / de validation du build.

Etape 4 : Visualisation
De nombreux modules Jenkins permette de visualiser les résultats des tests.

Ajoutez et configurez les plugins nécessaires pour créer un Dashboard qui permettra de visualiser les différentes phases précédentes.

Livrables
Dans un dossier nommé “Projet_08_Nom_Prenom”, vous mettrez à disposition de votre mentor :

Un fichier Jenkins qui contient :
Le lancement des tests unitaires dans un environnement de test.
La construction des containers Docker.
Un test fonctionnel qui teste le bon fonctionnement du build.
Les Dockerfiles qui permettent d’obtenir les différentes images.
Un screenshot de votre dashboard de visualisation des tests. 
Soutenance (25-30 min) :
Pendant 20 minutes, vous présenterez au mentor :

Votre Jenkins file, en expliquant comment vous avez réalisé la phase de pre-build.
Vos différents Dockerfile.
Votre test fonctionnel.
Puis pendant 5 à 10 min, vous échangerez sur vos livrables et choix.

Compétences évaluées
Automatiser l'empaquetage de l'application (build / packaging)
Tester automatiquement les applications avant livraison



