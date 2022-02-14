PHP = $(shell which php) -dphar.readonly=0
COMPOSER = dev/composer.phar
DEV = dev
VENDOR = vendor
BIN = $(VENDOR)/bin

default: dev/composer.phar vendor analyse

dev/composer.phar: Makefile 
	cd $(DEV) && wget -O - https://getcomposer.org/installer | $(PHP)

vendor: Makefile
	$(PHP) $(COMPOSER) install	

fmt: Makefile
	$(PHP) $(BIN)/php-cs-fixer fix src

analyse: Makefile
	$(PHP) $(BIN)/phpstan analyse -c phpstan.neon.dist
