#syntax=docker/dockerfile:1

# ---------- base ----------
FROM dunglas/frankenphp:php8.3 AS frankenphp_base

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
		acl file gettext git openssh-client \
	&& rm -rf /var/lib/apt/lists/*

RUN set -eux; \
	install-php-extensions \
		@composer \
		apcu \
		intl \
		opcache \
		zip \
		pdo_mysql

# .spells' composer() alias expects the binary at this exact path
RUN ln -s /usr/local/bin/composer /usr/bin/composer

# .spells execs as the host uid:gid; tools like ssh call getpwuid() and refuse to run
# for an unknown uid. Default to 1000 (the common single-user Linux uid) - override with
# --build-arg DEV_UID/DEV_GID if your host uid differs.
# Home is a real writable dir, not /tmp/.ssh (that's the read-only host ~/.ssh mount -
# ssh's ControlPath multiplexing socket lives under ~/.ssh and needs to create files there).
ARG DEV_UID=1000
ARG DEV_GID=1000
RUN echo "devuser:x:${DEV_UID}:${DEV_GID}::/home/devuser:/bin/sh" >> /etc/passwd \
	&& mkdir -p /home/devuser/.ssh \
	&& chown -R ${DEV_UID}:${DEV_GID} /home/devuser

# node/npm, needed at build time: FOSJsRoutingBundle's webpack plugin shells out to `php bin/console`
COPY --from=node:20 /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20 /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
	&& ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY docker/frankenphp/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

COPY composer.json composer.lock symfony.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
	composer install --no-scripts --prefer-dist

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
	npm ci

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# ---------- dev ----------
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev XDEBUG_MODE=off
RUN install-php-extensions xdebug

COPY . .
RUN npm run build

# .spells execs as the host uid:gid, which doesn't own these container-built,
# volume-backed dirs (var/vendor/node_modules) - open them up for local dev
RUN mkdir -p var && chmod -R a+rwX var vendor node_modules

# ---------- prod ----------
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod

RUN --mount=type=cache,target=/root/.composer/cache \
	composer install --no-dev --no-scripts --prefer-dist

COPY . .
# route dump (fos:js-routing:dump) only needs the kernel to boot, not prod secrets/DB - dev env has those in .env.dev
RUN APP_ENV=dev npm run build && rm -rf node_modules

RUN composer dump-autoload --classmap-authoritative --no-dev \
	&& composer dump-env prod \
	&& composer run-script --no-dev post-install-cmd \
	&& chmod +x bin/console
