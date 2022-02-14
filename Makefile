PHP = $(shell which php) -dphar.readonly=0
COMPOSER = dev/composer.phar
DEV = dev
VENDOR = vendor
BIN = $(VENDOR)/bin

dev:
	mkdir -p $(DEV)
	cd $(DEV)
	wget -O - https://getcomposer.org/installer | $(PHP)

vendor:
	$(PHP) $(COMPOSER) update

fmt:
	$(PHP) $(BIN)/php-cs-fixer fix src

analyse:
	$(PHP) $(BIN)/phpstan analyse -c phpstan.neon.dist
