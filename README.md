# Portail des associations et initiatives locales

**Portail-Asso** est un portail web destiné aux habitants d’un territoire pour découvrir les associations locales et leurs activités. Chaque association peut gérer sa page, publier ses événements et partager ses informations avec la communauté. Bien que le projet ait été initialement conçu pour Plabennec, il est facilement adaptable à d’autres communes ou territoires.

---

## Fonctionnalités principales

- Consultation des associations et de leurs activités
- Gestion de la page de l’association (modification des informations, ajout d’événements)
- Gestion des membres d'une association
- Abonnement aux pages association
- Recherche d'associations par nom
- Historique des modifications de page

---

## État du projet

Ce projet est **en cours de développement**. Certaines fonctionnalités peuvent être incomplètes ou sujettes à modification.

---

## Contribution

Les contributions sont les bienvenues. Pour contribuer :

1. **Forker le dépôt** et créer une branche dédiée ;
2. **Respecter la structure du projet** et les conventions de code existantes ;
3. **Documenter** toute nouvelle fonctionnalité ou modification significative dans le code et, si nécessaire, dans le README ;
4. **Proposer une Pull Request** avec un titre et une description clairs ;
5. **Vérifier** que le code est testé et fonctionne correctement avant de soumettre la PR ;

---

## Installation (développement local)

1. Cloner le dépôt :
```bash
git clone git@github.com:AlexiZ/portail-asso.git
cd portail-asso
```

2. Installer les dépendances :
```bash
composer install
npm install
npm run build
```

3. Configurer l’environnement :

Copier .env.dev en .env.local et ajuster les variables de configuration (base de données, SMTP, etc.).

4. Lancer le serveur local :
```bash
symfony server:start
```

Le portail sera accessible sur http://localhost:8000.

## License

Le projet est publié sous licence MIT. Voir le fichier [LICENSE](LICENSE.md) pour plus de détails.
