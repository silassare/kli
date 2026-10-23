### v1.1.0 (unreleased)

    - An option's flag and name are padded in terminal columns in `--help`
      (`KliUtils::displayWidth()`, as every other width here is), where `str_pad()` counted bytes:
      a name with an accent or a wide character used to push the description out of line
    - Every class constant a value types beyond doubt is typed (`private const int`,
      `public const string`, ...), which PHP 8.3 allows

    - **PHP 8.3 is the minimum** (`"php": ">=8.3"`), the version OZone requires, so that every package
      of the suite answers for one floor. 8.1 and 8.2 are end of life, and their security fixes are
      source backports and not releases, so no release of them can satisfy the advisory database
      `roave/security-advisories` carries: the dependencies of a run on one cannot be resolved at all.
      `config.platform.php` and the PHPCompatibility target (`testVersion`) say 8.3 with it.

    - `KliTable` measures in terminal columns (`KliUtils::displayWidth()`): a wide character or an emoji
      takes two, an ANSI sequence in a value none, and a tab is written as `KliTable::TAB_SPACES` spaces;
      a row used to be misaligned by each of them
    - `KliTable` cells may hold several lines: the row grows to hold them (a line break used to cut the row)
    - `KliTable` pads each cell with one space on each side (both spaces used to go on one side, so text
      touched a border)
    - `KliTable::setMaxWidth(?int)` added: a table wider than its maximum (the terminal's width by default)
      shrinks its widest columns and wraps their text; a column with a fixed width truncates, as before
    - `KliUtils::stripAnsi()`, `KliUtils::displayWidth()` and `KliUtils::terminalWidth()` added
    - `KliUtils::wrap()` (and so `writeLn()`, `info()`, `warn()`, `error()`, `success()`, `indent()`) wraps
      at terminal columns, ANSI sequences left out, instead of bytes; its width defaults to the terminal's
      (`?int $width = null`), else 80. Accented text used to wrap early and wide text late
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
    - `Kli::log()` signature updated to `log(string $level, mixed $msg, array $context = [])`
    - developer workflow scripts replaced with `Makefile` targets (`make test`, `make lint`, `make cs`, `make fix`)
    - `Kli::canPrompt(): bool` added: a missing required option is only prompted for when STDIN is a
      terminal or in interactive mode; otherwise its default is used or the missing option is reported
    - `Kli::readLine()` returns '' at end of input instead of throwing a `TypeError` (readline and fgets paths)
    - `Kli::isEndOfInput(): bool` added; interactive mode stops at the end of input (Ctrl-D or a closed pipe)
    - an empty answer to an option prompt returns the default as is instead of validating it
    - an option prompt stops at the end of input when there is no default: the missing option is
      reported instead of prompting forever
    - an input error in interactive mode is printed and the REPL goes on, instead of a
      `KliAbortException` escaping `execute()` and stopping the REPL

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
