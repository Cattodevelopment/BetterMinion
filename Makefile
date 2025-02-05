PHP = $(shell which php) -dphar.readonly=0
COMPOSER = composer.phar
BIN = vendor/bin

composer.phar:
	curl https://getcomposer.org/installer | $(PHP)

vendor/install:
	$(PHP) $(COMPOSER) install

vendor/update:
	$(PHP) $(COMPOSER) update

fmt: Makefile
	$(PHP) $(BIN)/php-cs-fixer fix src

analyse: Makefile
	$(PHP) $(BIN)/phpstan analyse