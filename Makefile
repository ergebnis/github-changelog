it: cs test

cs:
	vendor/bin/php-cs-fixer fix --verbose --diff

test:
	vendor/bin/phpunit
