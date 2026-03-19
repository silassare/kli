### v1.1.0 (unreleased)

    - `warn()` added with optional `?int $exit = null` parameter
    - `error()` updated: default `$exit = 1` (terminates unless `exit: null` is passed)
    - `success()` updated: optional `?int $exit = null` parameter
    - `terminate(int $code = 0): never` added for unconditional process exit
    - `isInteractiveMode(): bool` added
    - `switchToInteractiveMode()` renamed from `interactiveMode()`
    - `allow_interactive_mode` constructor parameter renamed from `enable_interactive`
    - `KliAbortException` added: thrown instead of exit() when output helpers are called
      with a non-null $exit in interactive mode; caught by execute() to keep REPL alive
    - static factory methods in `KliTableFormatter` now return `static` instead of `self`
    - developer workflow scripts replaced with `Makefile` targets (`make test`, `make lint`, `make cs`, `make fix`)

### v1.0.4 (2020-08-23)

    - `KliColor` class added for text styling
    - bell function added to `Kli` class

### v1.0.3 (2019-12-31)

    - display colored error/success/info to terminal
    - newline at end of file

### v1.0.2 (2019-03-18)

    - interactive mode added
    - some bug fix

### v1.0.1 (2019-03-17)

    - log routine added

### v1.0.0 (2017-09-10)

First stable version of Kli.
