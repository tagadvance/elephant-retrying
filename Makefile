all: clean install test

clean:
	-rm --recursive --force vendor/

install:
	composer install

test:
	php vendor/bin/phpunit --bootstrap vendor/autoload.php tests

test-debug:
	php -dxdebug.remote_enable=1 -dxdebug.remote_mode=req -dxdebug.remote_port=9000 -dxdebug.remote_host=127.0.0.1 -dxdebug.remote_connect_back=0 vendor/bin/phpunit --bootstrap vendor/autoload.php tests
