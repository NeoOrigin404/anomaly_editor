# Éditeur de Déclaration d'Anomalies

## Description
Ce projet est une application web PHP permettant de gérer et d'éditer des déclarations d'anomalies au format CSV. Il offre une interface utilisateur intuitive pour créer, modifier, supprimer et exporter des fichiers de déclarations d'anomalies.

## Fonctionnalités
- Création de nouveaux fichiers de déclarations d'anomalies
- Édition des fichiers existants
- Suppression de fichiers
- Renommage de fichiers
- Export des fichiers au format CSV
- Interface utilisateur responsive et intuitive
- Gestion des dates de création et de modification des fichiers
- Validation et nettoyage des noms de fichiers

## Structure du Projet
- `index.php` : Page principale listant les fichiers CSV disponibles et permettant leur gestion
- `anomaly_editor.php` : Éditeur de déclarations d'anomalies avec interface de saisie

## Prérequis
- Serveur web avec support PHP
- Permissions d'écriture dans le répertoire du projet pour la gestion des fichiers CSV

## Installation
1. Clonez le dépôt dans votre répertoire web
2. Assurez-vous que les permissions sont correctement configurées pour permettre l'écriture des fichiers
3. Accédez à l'application via votre navigateur web

## Utilisation
1. Sur la page d'accueil (`index.php`), vous pouvez :
   - Voir la liste des fichiers CSV existants
   - Créer un nouveau fichier
   - Renommer un fichier existant
   - Supprimer un fichier
   - Télécharger un fichier

2. Dans l'éditeur (`anomaly_editor.php`), vous pouvez :
   - Éditer le contenu des déclarations d'anomalies
   - Sauvegarder les modifications
   - Exporter le fichier au format CSV

## Sécurité
- Validation des noms de fichiers
- Protection contre les injections de code
- Gestion sécurisée des sessions

## Support
Pour toute question ou problème, veuillez contacter l'équipe de support.

## Licence
Ce projet est propriétaire et confidentiel. Tous droits réservés. 