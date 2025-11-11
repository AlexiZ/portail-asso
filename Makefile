# —— Inspired by ———————————————————————————————————————————————————————————————
# http://fabien.potencier.org/symfony4-best-practices.html
# https://speakerdeck.com/mykiwi/outils-pour-ameliorer-la-vie-des-developpeurs-symfony?slide=47
# https://blog.theodo.fr/2018/05/why-you-need-a-makefile-on-your-project/
# Setup ————————————————————————————————————————————————————————————————————————

# Parameters
SHELL         			= sh
PROJECT       			= Portail Plab
GIT_AUTHOR    			= AlexiZ
HTTP_PORT     			= 8000
REMOTE_USER   			= #
REMOTE_SERVER 			= #
MAINTENANCE_PAGE_SOURCE = templates/Exception/503.html
MAINTENANCE_PAGE_NAME   = public/maintenance/maintenance.html
REMOTE_DIRECTORY        = #
remote 					?= preprod
REMOTE 					?= $(remote)

# Executables
EXEC_PHP      = php
COMPOSER      = composer
REDIS         = redis-cli
GIT           = git
NPM           = npm
NPX           = npx
DEP			  = vendor/bin/dep

# Alias
SYMFONY       = $(EXEC_PHP) bin/console

# Executables: vendors
PHPUNIT       = ./vendor/bin/phpunit
PHPSTAN       = ./vendor/bin/phpstan
PHP_CS_FIXER  = PHP_CS_FIXER_IGNORE_ENV=true ./vendor/bin/php-cs-fixer
PHPMETRICS    = ./vendor/bin/phpmetrics

# Executables: local only
SYMFONY_BIN   = symfony

# Executables: prod only
CERTBOT       = certbot

# Misc
.DEFAULT_GOAL = help
.PHONY        : # Not needed here, but you can put your all your targets to be sure
                # there is no name conflict between your files and your targets.

## —— 🐝 The Symfony Makefile 🐝 ———————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

ssh: ## Connect to server with SSH
	@$(DEP) ssh $(REMOTE)

## —— Composer 🧙‍♂️ ————————————————————————————————————————————————————————————
install: composer.lock ## Install vendors according to the current composer.lock file
	@$(COMPOSER) install --no-progress --prefer-dist --optimize-autoloader

update: ## Update vendors
	@$(COMPOSER) update

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands
	@$(SYMFONY)

cc: ## Clear the cache. DID YOU CLEAR YOUR CACHE????
	@$(SYMFONY) c:c

warmup: ## Warmup the cache
	@$(SYMFONY) cache:warmup

fix-perms: ## Fix permissions of all var files
	@chmod -R 777 var/*

assets: purge ## Install the assets with symlinks in the public folder
	@$(SYMFONY) assets:install public/

purge: ## Purge cache and logs
	@rm -rf var/cache/* var/logs/*

## —— Symfony binary 💻 ————————————————————————————————————————————————————————
cert-install: ## Install the local HTTPS certificates
	@$(SYMFONY_BIN) server:ca:install

serve: ## Serve the application with HTTPS support (add "--no-tls" to disable https)
	@$(SYMFONY_BIN) serve --daemon --port=$(HTTP_PORT)

unserve: ## Stop the webserver
	@$(SYMFONY_BIN) server:stop

## —— Project 🐝 ———————————————————————————————————————————————————————————————
start: load-fixtures serve ## Start docker, load fixtures, populate the Elasticsearch index and start the webserver

cc-redis: ## Flush all Redis cache
	@$(REDIS) -p 6389 flushall

commands: ## Display all commands in the project namespace
	@$(SYMFONY) list $(PROJECT)

load-fixtures: ## Build the DB, control the schema validity, load fixtures and check the migration status
	@$(SYMFONY) doctrine:cache:clear-metadata
	@$(SYMFONY) doctrine:database:create --if-not-exists
	@$(SYMFONY) doctrine:schema:drop --force
	@$(SYMFONY) doctrine:schema:create
	@$(SYMFONY) doctrine:schema:validate
	@$(SYMFONY) hautelook:fixtures:load --no-interaction

init-snippet: ## Initialize a new snippet
	@$(SYMFONY) $(PROJECT):init-snippet

database: ## Pull REMOTE database to local instance
	@$(DEP) db:pull $(REMOTE)

medias:
	@$(DEP) download_medias $(REMOTE)

## —— Tests ✅ —————————————————————————————————————————————————————————————————
test: ## Run tests
	@$(PHPUNIT) --stop-on-failure --configuration phpunit.xml

## —— Coding standards ✨ ——————————————————————————————————————————————————————
cs: lint-php lint-js ## Run all coding standards checks

static-analysis: stan ## Run the static analysis (PHPStan)

stan: ## Run PHPStan
	@$(PHPSTAN) analyse --memory-limit 1G --level 5 src tests

lint-php: ## Lint files with php-cs-fixer
	@$(PHP_CS_FIXER) fix --allow-risky=yes --dry-run --config .php-cs-fixer.php -v src tests

fix-php: ## Fix files with php-cs-fixer
	@$(PHP_CS_FIXER) fix --allow-risky=yes --config .php-cs-fixer.php -v src tests

## —— Deploy & Prod 🚀 —————————————————————————————————————————————————————————
web-disable: ## Add maintenance page to close website
	@ssh $(REMOTE_USER)@$(REMOTE_SERVER) "cp $(REMOTE_DIRECTORY)/$(MAINTENANCE_PAGE_SOURCE) $(REMOTE_DIRECTORY)/$(MAINTENANCE_PAGE_NAME)"

web-enable: ## Remove maintenance page to re-open website
	@ssh $(REMOTE_USER)@$(REMOTE_SERVER) "rm $(REMOTE_DIRECTORY)/$(MAINTENANCE_PAGE_NAME)"

deploy: ## Full no-downtime deployment with deployphp/deployer
	@test -z "`git status --porcelain`"                 # Prevent deploy if there are modified or added files
	@test -z "`git diff --stat --cached origin/main`"   # Prevent deploy if there is something to push on master
	@$(DEP) deploy $(REMOTE)		                	# Deploy with deployphp/deployer

le-renew: ## Renew Let's Encrypt HTTPS certificates
	@$(CERTBOT) --apache -d strangebuzz.com -d www.strangebuzz.com

## —— Yarn 🐱 / JavaScript —————————————————————————————————————————————————————
dev: ## Rebuild assets for the dev env
	@$(npm) install
	@$(npm) run build

watch: ## Watch files and build assets when needed for the dev env
	@$(NPM) run watch

build: ## Build assets for production
	@$(NPM) run build

lint-js: ## Lints JS coding standarts
	@$(NPX) eslint assets

fix-js: ## Fixes JS files
	@$(NPX) eslint assets --fix

## —— Stats 📜 —————————————————————————————————————————————————————————————————
stats: ## Commits by the hour for the main author of this project
	@$(GIT) log --author="$(GIT_AUTHOR)" --date=iso | perl -nalE 'if (/^Date:\s+[\d-]{10}\s(\d{2})/) { say $$1+0 }' | sort | uniq -c|perl -MList::Util=max -nalE '$$h{$$F[1]} = $$F[0]; }{ $$m = max values %h; foreach (0..23) { $$h{$$_} = 0 if not exists $$h{$$_} } foreach (sort {$$a <=> $$b } keys %h) { say sprintf "%02d - %4d %s", $$_, $$h{$$_}, "*"x ($$h{$$_} / $$m * 50); }'

## —— Code Quality reports 📊 ——————————————————————————————————————————————————
report-metrics: ## Run the phpmetrics report
	@$(PHPMETRICS) --report-html=var/phpmetrics/ src/

coverage: ## Create the code coverage report with PHPUnit
	$(EXEC_PHP) -d xdebug.enable=1 -d xdebug.mode=coverage -d memory_limit=-1 vendor/bin/phpunit --coverage-html=var/coverage
