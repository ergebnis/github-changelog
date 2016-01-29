it: cs test

composer:
	composer validate
	composer install

cs: composer
	vendor/bin/php-cs-fixer fix --verbose --diff

test: composer
	vendor/bin/phpunit
