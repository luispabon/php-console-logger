PHP_CONTAINER?="phpdockerio/php80-cli"
XDEBUG_PACKAGE?="php8.0-xdebug"

PHP_RUN=docker run --rm -e XDEBUG_MODE=coverage -v "$(PWD):/workdir" -w "/workdir" --rm $(PHP_CONTAINER)

#### Tests & ci
prep-ci:
	$(PHP_RUN) composer -o install

static-analysis:
	$(PHP_RUN) vendor/bin/phpstan --ansi -v analyse -l 7 src

unit-tests:
	$(PHP_RUN) vendor/bin/phpunit --testdox --colors=always

coverage-tests:
	$(PHP_RUN) bash -c " \
		apt update && \
		apt install $(XDEBUG_PACKAGE) && \
		vendor/bin/phpunit --testdox --colors=always"

mutation-tests:
	$(PHP_RUN) vendor/bin/infection --coverage=reports/infection --threads=2 -s --min-msi=100 --min-covered-msi=100
