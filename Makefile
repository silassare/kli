.PHONY: test cs lint fix

PHPUNIT = ./vendor/bin/phpunit
PHP     = php

# = Tests

## Run the unit test suite
test:
	$(PHPUNIT) --testdox --do-not-cache-result

# = Code quality

## Check code style
cs:
	vendor/bin/phpcs

## Run static analysis (psalm)
lint:
	vendor/bin/psalm --no-cache

## Run code style fixer
fix: lint
	vendor/bin/oliup-cs fix
