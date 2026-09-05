all: clean install test

clean:
	-rm --recursive --force build/
	-rm --recursive --force vendor/

install:
	composer install

lint:
	php vendor/bin/php-cs-fixer check --diff

fix:
	php vendor/bin/php-cs-fixer fix

analyse:
	php vendor/bin/phpstan analyse --no-progress

test:
	php vendor/bin/phpunit

test-coverage:
	php vendor/bin/phpunit --coverage-clover build/logs/clover.xml

test-debug:
	php -dxdebug.mode=debug -dxdebug.start_with_request=yes -dxdebug.client_port=9003 -dxdebug.client_host=127.0.0.1 vendor/bin/phpunit
