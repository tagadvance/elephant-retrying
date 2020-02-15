all: clean install test

clean:
	-rm --recursive --force vendor/

install:
	composer install

test:
	php vendor/bin/phpunit --bootstrap vendor/autoload.php  tests
