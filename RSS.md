# Flux RSS

Le portail expose deux flux RSS 2.0 (`Content-Type: application/rss+xml`) :

| Flux | URL |
|---|---|
| Associations | `/fr/rss/associations.xml` |
| Événements | `/fr/rss/evenements.xml` |

Comme toutes les routes du portail, les URLs sont préfixées par la locale (`/fr/…` ou `/br/…`).

Les deux flux sont annoncés dans le `<head>` de chaque page (`<link rel="alternate" type="application/rss+xml">`) pour la découverte automatique par les navigateurs et lecteurs RSS, et le flux des événements est accessible via l'icône RSS du pied de page.

## Règles communes

- Les paramètres sont passés en query string et sont tous combinables. Ils se cumulent (intersection) : aucun filtre n'en contourne un autre.
- Les paramètres à valeurs multiples sont séparés par des virgules ; plusieurs valeurs d'un même paramètre se combinent en **OU**.
- Un paramètre invalide (valeur mal formée, catégorie ou slug inconnu…) est **ignoré silencieusement** : sa valeur par défaut s'applique.
- `debug=true` remplace le flux XML par une réponse texte (`text/plain`) listant les paramètres ignorés avec leur raison, ainsi que les filtres effectivement appliqués. Défaut : `false`.
- `sort=champ,SENS` trie le flux selon le champ indiqué, `SENS` valant `ASC` ou `DESC` (insensible à la casse, `ASC` si absent). Les champs autorisés dépendent du flux ; un champ ou un sens inconnu fait ignorer `sort` (tri par défaut). Le tri est toujours appliqué avant `limit`.
- `tags` et `categories` sont deux noms du même paramètre. Les valeurs attendues sont celles de l'enum `AssociationCategory`.

## Flux des associations

`/fr/rss/associations.xml`

Contient **toutes** les associations, sans limite, triées par défaut par date de mise à jour décroissante.

Chaque item contient :

| Élément | Valeur |
|---|---|
| `title` | nom de l'association |
| `link` | URL absolue de la page de l'association |
| `guid` | slug de l'association (stable, ne change pas lors d'une mise à jour) |
| `description` | présentation de l'association |
| `pubDate` | date de dernière mise à jour (`updatedAt`) |
| `dcterms:created` | date de création (`createdAt`) |
| `dcterms:modified` | date de dernière mise à jour (`updatedAt`) |

Les modifications d'une association étant acceptées par défaut, le flux reflète toujours sa version la plus à jour.

### Paramètres

| Paramètre | Défaut | Effet |
|---|---|---|
| `tags` / `categories` | aucun | ne garde que les associations ayant au moins une des catégories |
| `sort` | `updatedAt,DESC` | champs : `updatedAt`, `createdAt`, `name` |
| `debug` | `false` | voir « Règles communes » |

Exemple : `/fr/rss/associations.xml?categories=sport,music&sort=name` (associations de sport ou de musique, par ordre alphabétique)

## Flux des événements

`/fr/rss/evenements.xml`

Par défaut : les 30 prochains événements publics, sur l'année à venir, triés par date de début croissante.

Chaque item contient :

| Élément | Valeur |
|---|---|
| `title` | date de l'événement suivie de son titre |
| `link` | URL absolue de la page de l'événement |
| `guid` | slug de l'événement suivi de la date de l'occurrence (unique par occurrence) |
| `description` | date et heure, lieu, association, description courte |
| `pubDate` | date de publication de l'événement (`createdAt`) |

Les événements récurrents sont dépliés : chaque occurrence comprise dans la fenêtre de dates devient un item distinct et compte dans `limit`.

### Paramètres

| Paramètre | Défaut | Effet |
|---|---|---|
| `limit` | `30` | nombre maximum d'événements renvoyés ; `-1` = sans limite. Appliqué en dernier, après filtres et tri |
| `private` | `false` | `true` inclut aussi les événements non publics (non affichés sur l'accueil) |
| `tags` / `categories` | aucun | ne garde que les événements des associations ayant au moins une des catégories |
| `association` | aucun | ne garde que les événements des associations dont le slug est indiqué (plusieurs slugs possibles) |
| `past` | `false` | `true` inclut aussi les événements passés et, sans `sort` explicite, trie par date de début **décroissante** |
| `from` | voir ci-dessous | date de début minimale (`YYYY-MM-DD`) |
| `to` | voir ci-dessous | date de début maximale (`YYYY-MM-DD`, jour inclus) |
| `sort` | `startAt,ASC` (`startAt,DESC` avec `past=true`) | champs : `startAt`, `createdAt`, `title` ; un `sort` explicite prime sur le défaut lié à `past` |
| `debug` | `false` | voir « Règles communes » |

### Fenêtre de dates

Le filtre porte sur la date de début (`startAt`) de chaque événement ou occurrence.

- **Début** :
  - sans `past` : `from` s'il est postérieur à maintenant, sinon maintenant (les événements passés restent exclus) ;
  - avec `past=true` : `from` s'il est fourni, sinon il y a un an.
- **Fin** : `to` s'il est fourni, sinon un an après le plus tardif entre le début et maintenant.

### Exemples

| URL | Résultat |
|---|---|
| `/fr/rss/evenements.xml` | 30 prochains événements publics |
| `/fr/rss/evenements.xml?limit=-1&private=true` | tous les événements de l'année à venir, publics ou non |
| `/fr/rss/evenements.xml?association=asso-a,asso-b&tags=sport` | événements à venir des associations `asso-a` ou `asso-b`, si elles ont la catégorie `sport` |
| `/fr/rss/evenements.xml?past=true&from=2025-01-01&to=2025-12-31&limit=-1` | tous les événements publics de 2025, du plus récent au plus ancien |
| `/fr/rss/evenements.xml?sort=createdAt,DESC&limit=10` | les 10 événements à venir publiés le plus récemment |
| `/fr/rss/evenements.xml?from=2020-01-01` | événements à venir uniquement (`from` antérieur à maintenant est ramené à maintenant sans `past=true`) |
| `/fr/rss/evenements.xml?limit=abc&debug=true` | texte indiquant que `limit` est ignoré (défaut 30 appliqué) |
