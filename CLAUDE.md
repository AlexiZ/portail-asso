# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

"Portail-Asso" — a Symfony 7.4 / PHP 8.2+ community portal (originally built for the town of Plabennec, France) where residents browse local associations and their events, and associations self-manage their own page. Server-rendered Twig MVC (no API Platform, no separate API layer).

## Commands

Everything runs in Docker (FrankenPHP `php` service + MariaDB + Mailpit): PHP, Composer, Node/npm and `vendor/`/`node_modules/` (container volumes) are **not** available on the host. Run every command below inside the `php` container — `docker compose exec php <cmd>`, or `source .spells` for `php`/`composer`/`sf`/`npm` aliases (also what `make` targets rely on).

Setup:
```
make docker-up          # docker compose up --build -d
source .spells
sf doctrine:database:create --if-not-exists && sf doctrine:schema:create
```
App on http://localhost:8000 / https://localhost:8443 (self-signed cert).

Tests — `make test` and `make cs` reference config filenames (`phpunit.xml`, `.php-cs-fixer.php`) that aren't the ones committed (`phpunit.dist.xml`, `.php-cs-fixer.dist.php`); pass the `.dist` files explicitly unless a local override exists:
```
vendor/bin/phpunit --configuration phpunit.dist.xml                  # full suite
vendor/bin/phpunit --configuration phpunit.dist.xml --filter testFoo tests/Path/FooTest.php   # single test
```

Lint / static analysis:
```
vendor/bin/php-cs-fixer fix --allow-risky=yes --dry-run --config .php-cs-fixer.dist.php -v src tests   # check
vendor/bin/php-cs-fixer fix --allow-risky=yes --config .php-cs-fixer.dist.php -v src tests             # fix
npx eslint assets                                                     # JS lint (make lint-js)
vendor/bin/phpstan analyse --memory-limit 1G --level 5 src tests      # make stan (no phpstan.neon file)
```

Assets (Webpack Encore, not Vite) — output in `public/build/` (gitignored, rebuild after any change in `assets/`):
```
docker compose exec php npm run watch     # rebuild on change, dev
docker compose exec php npm run build     # build
```

Deploy: `make deploy` (Deployer, default remote `preprod`) — refuses if there are uncommitted or unpushed changes. No CI pipeline exists; these `make` targets are run manually.

## Architecture

**Domain model** — `Association` is the aggregate root:
- `User` — site account; `roles` array (`ROLE_USER`/`ROLE_MODERATOR`/`ROLE_ADMIN`), can be an association's `owner` ("chairman"), and/or hold a `Membership`/`Subscription` to one.
- `Association` — name/slug, JSON `categories` (`AssociationCategory` enum), rich-text `content`, `editablePageAnonymously`/`editableEventsAnonymously` flags for unauthenticated edits. Has a manual `serialize()/unserialize()` used to snapshot state for revisions.
- `Event` — belongs to an association; recurrence via `rlanvin/php-rrule` (`Association::getFutureEvents()` expands recurring events into per-occurrence clones).
- `AssociationRevision` — before/after JSON snapshot + `approved` flag: every page/event edit is captured here and must be signed off by a moderator (`ModeratorController`) before going live.
- `Membership` (User↔Association, `pending`/`accepted`/`refused`) and `Subscription` (User↔Association, drives the weekly email digest) are join entities.
- `Contact` — board contacts attached to an association.
- `Setting` — key/value(JSON) app config, admin-editable, `isEditable` guard flag; exposed globally to Twig via `SettingsTwigExtension`/`SettingsService` (PSR-6 cache-aside).

**Factory pattern (`src/Factory/*Factory.php`)** — the form-handling layer keeping controllers thin: each factory takes a `Request`/`FormInterface` + entity and does populate-from-form, side effects (slug, owner, file upload via VichUploader), and persist/flush. `AssociationFactory::createRevision()` is how the revision/moderation snapshot gets created. Reuse this pattern for new entities with editable forms rather than putting persistence logic in controllers.

**Authorization** — a single voter, `src/Security/AssociationVoter.php`, covers both `Association` and `Event` subjects (`new`, `edit`, `manage`, `delete`, etc.). Admins/moderators always pass; otherwise it's ownership (`user === association.owner`) or accepted-`Membership` for edit rights, with the anonymous-edit flags as an escape hatch. Per-association rules live in the voter, not `security.yaml` — only the blunt `/admin` (ROLE_ADMIN) and `/moderation` (ROLE_MODERATOR) routes are gated in YAML.

**Mailer** (`src/Service/Mailer.php`) — calls the Brevo transactional email API directly via `HttpClientInterface`, *not* Symfony Mailer/Mime. Each notification type renders a Twig template as the body; `mailerDevRecipient` overrides the destination outside prod. `SubscriptionsService::sendWeeklyReports()` (driven by `MailSubscriptionsCommand`) diffs each subscribed user's followed associations since the last report and sends the digest.

**Custom DQL** — `src/DQL/JsonContains.php` wraps MySQL `JSON_CONTAINS` so repositories can filter the JSON `categories` column at the SQL level (see `AssociationRepository::findAllWithCategory`).

**Frontend** — Webpack Encore (not Vite/Tailwind), Bootstrap 5, plain JS in `assets/app.js` (hooked on `DOMContentLoaded`, full page loads). Stimulus and UX Turbo are **not** active: `symfony/stimulus-bundle`/`symfony/ux-turbo` are in composer and `assets/bootstrap.js`/`assets/controllers/` exist, but `@hotwired/stimulus` isn't in `package.json` and `app.js` never imports `bootstrap.js` — a new `*_controller.js` won't run. Likewise `symfony/asset-mapper` + `importmap.php` are installed but unused (templates use `encore_entry_*`). CKEditor5 (+ legacy CKEditor4 via FOSCKEditorBundle) for rich text. `templates/` mirrors controller areas (`admin/`, `association/`, `chairman/`, `event/`, `moderator/`, `user/`, etc.) plus `emails/` for the Brevo email bodies.

**Gotchas**
- `public/adminer-en.php` — a DB admin tool is present under the public web root.
- No `api-platform/*`, no `phpstan.neon` (level 5 is CLI-only), no CI workflow files — checks are developer-run via `make`.
