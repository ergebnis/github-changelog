it: cs test

composer:
	composer install

cs: composer
	vendor/bin/php-cs-fixer fix --verbose --diff

test: composer
	vendor/bin/phpunit
