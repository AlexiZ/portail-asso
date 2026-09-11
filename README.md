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

Seul **Docker** (avec le plugin Compose) est nécessaire : ni PHP, ni Composer, ni Node, ni le binaire Symfony ne sont requis sur la machine hôte, tout tourne dans les conteneurs (PHP/FrankenPHP, MariaDB, Mailpit).

1. Cloner le dépôt :
```bash
git clone git@github.com:AlexiZ/portail-asso.git
cd portail-asso
```

2. Construire les images et démarrer la stack :
```bash
make docker-up
# équivalent à : docker compose up --build -d
```

3. Sourcer `.spells` pour obtenir des alias `php`, `composer` et `sf` qui exécutent ces binaires dans le conteneur :
```bash
source .spells
```

4. Créer le schéma de base de données (premier démarrage uniquement) :
```bash
sf doctrine:database:create --if-not-exists
sf doctrine:schema:create
```

Le portail est accessible sur http://localhost:8000 (redirigé automatiquement) ou directement sur https://localhost:8443 — le certificat est auto-signé (CA interne de Caddy), le navigateur affichera un avertissement à accepter la première fois.

`.spells` permet ensuite d'utiliser `composer`, `php` et `sf` normalement, ex : `composer require foo/bar`, `sf cache:clear`.

Pour arrêter la stack :
```bash
make docker-down
```

Note : l'envoi d'e-mails passe par l'API Brevo (`BREVO_API_KEY`), pas par SMTP — le conteneur Mailpit tourne mais ne capture rien tant que ce code n'est pas branché dessus.

## License

Le projet est publié sous licence MIT. Voir le fichier [LICENSE](LICENSE.md) pour plus de détails.
