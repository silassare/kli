# Three tiers of verification, the cadence every repository of the suite follows:
#
#   make check                        every change: code style and static analysis.
#   make test FILE=... FILTER=...     a feature is done: the tests that cover it.
#   make test                         end of a cycle, and before anything is handed over.
#
# The whole suite runs in about a second here, so FILE and FILTER buy focus and not time. What
# costs is `check`: phpcs and psalm both read every file. phpcs is told to fork over this machine's
# cores, and both keep a cache keyed by file content and by their own configuration, so a second run
# reads only what changed. `make check-full` ignores both caches: it is the run the cached one is
# checked against, and what CI runs. Neither the forking nor the cache changes what a check reports.

.PHONY: test check check-full cs lint fix

PHPUNIT = ./vendor/bin/phpunit
PHP     = php

# phpcs reads one file at a time unless told to fork (it needs ext-pcntl). psalm counts the cores
# itself, and takes one in CI on purpose, so it is left to decide.
PHPCS_JOBS  ?= $(shell nproc 2>/dev/null || echo 1)
# phpcs caches only when given a file to cache into; psalm caches unless told not to.
PHPCS_CACHE ?= --cache=.phpcs.cache
PSALM_CACHE ?=

# = Tests

## Run the unit test suite (FILE=tests/FooTest.php runs one file, FILTER=testBar one case)
test:
	$(PHPUNIT) --testdox --do-not-record-test-run-history $(if $(FILTER),--filter '$(FILTER)',) $(FILE)

# = Code quality

## Code style and static analysis: the gate for every change
check: cs lint

## The same two with their caches ignored: what a handover and CI run
check-full: PHPCS_CACHE := --no-cache
check-full: PSALM_CACHE := --no-cache
check-full: cs lint

## Check code style
cs:
	vendor/bin/phpcs --parallel=$(PHPCS_JOBS) $(PHPCS_CACHE)

## Run static analysis (psalm)
lint:
	vendor/bin/psalm $(PSALM_CACHE)

## Run code style fixer
fix: lint
	vendor/bin/oliup-cs fix
