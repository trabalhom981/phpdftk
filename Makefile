DOCKER_COMPOSE=docker compose
PHP_CS_FIXER=./vendor/bin/php-cs-fixer

build:
	$(DOCKER_COMPOSE) build

bash:
	$(DOCKER_COMPOSE) run -it --rm php bash

cs_fix:
	$(DOCKER_COMPOSE) run --rm php $(PHP_CS_FIXER) fix

cs_check:
	$(DOCKER_COMPOSE) run --rm php $(PHP_CS_FIXER) check --diff

test:
	$(DOCKER_COMPOSE) run --rm php ./vendor/bin/phpunit --no-coverage
