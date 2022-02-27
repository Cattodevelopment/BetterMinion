PHP = $(shell which php) -dphar.readonly=0
COMPOSER = dev/composer.phar
DEV = dev
VENDOR = vendor
BIN = $(VENDOR)/bin

composer: Makefile
	cd $(DEV) && wget -O - https://getcomposer.org/installer | $(PHP)

vendor: Makefile
	$(PHP) $(COMPOSER) install	

fmt: Makefile
	$(PHP) $(BIN)/php-cs-fixer fix src

analyse: Makefile
	$(PHP) $(BIN)/phpstan analyse
