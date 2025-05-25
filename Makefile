DOCKER_COMPOSE=docker compose
PHP_CS_FIXER=./vendor/bin/php-cs-fixer
RECTOR=./tools/bin/rector

build:
	COMPOSE_BAKE=true $(DOCKER_COMPOSE) build

bash:
	$(DOCKER_COMPOSE) run -it --rm php bash

install:
	$(DOCKER_COMPOSE) run --rm php composer install

update:
	$(DOCKER_COMPOSE) run --rm php composer update

cs_fix:
	$(DOCKER_COMPOSE) run --rm php $(PHP_CS_FIXER) fix

cs_check:
	$(DOCKER_COMPOSE) run --rm php $(PHP_CS_FIXER) check --diff

test:
	$(DOCKER_COMPOSE) run --rm php ./vendor/bin/phpunit

coverage:
	$(DOCKER_COMPOSE) run --rm -e XDEBUG_MODE=coverage  php ./vendor/bin/phpunit --coverage-html build/coverage

phpstan:
	$(DOCKER_COMPOSE) run --rm php ./vendor/bin/phpstan analyse

phpstan-baseline:
	$(DOCKER_COMPOSE) run --rm php ./vendor/bin/phpstan analyse -b

rector:
	$(DOCKER_COMPOSE) run --rm php $(RECTOR) --dry-run

rectify:
	$(DOCKER_COMPOSE) run --rm php $(RECTOR)

quality: rector cs_check test
