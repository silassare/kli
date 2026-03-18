# Kli

PHP >=8.1 fluent-builder CLI framework. Add a typed, interactive command-line
interface to any PHP application in minutes.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Architecture](#architecture)
- [Commands and Actions](#commands-and-actions)
- [Options and Types](#options-and-types)
  - [String](#string-type)
  - [Number](#number-type)
  - [Bool](#bool-type)
  - [Path](#path-type)
- [Handlers](#handlers)
- [Parsed Arguments — KliArgs](#parsed-arguments--kliargs)
- [Positional (Offset) Arguments](#positional-offset-arguments)
- [Interactive Prompts](#interactive-prompts)
- [Interactive REPL Mode](#interactive-repl-mode)
- [ANSI Styling — KliStyle](#ansi-styling--klistyle)
- [Table Rendering — KliTable](#table-rendering--klitable)
- [Output Helpers](#output-helpers)
- [Exception Handling](#exception-handling)
- [Naming Rules](#naming-rules)
- [Extending Kli](#extending-kli)
- [Testing](#testing)
- [Developer Workflows](#developer-workflows)

---

## Requirements

- PHP >= 8.1

---

## Installation

```bash
composer require silassare/kli
```

---

## Quick Start

```php
#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kli\Kli;
use Kli\KliArgs;

$kli = Kli::new('my-tool');

$cmd = $kli->command('greet')->description('Greeting utilities');
$act = $cmd->action('say')->description('Print a greeting');
$act->option('name', 'n')->string()->def('World');
$act->handler(function (KliArgs $args) use ($kli): void {
    $kli->success('Hello, ' . $args->get('name') . '!');
});

$kli->execute($argv);
```

```
$ my-tool greet say --name=Alice
  ✔  Hello, Alice!

$ my-tool greet say -n Bob
  ✔  Hello, Bob!

$ my-tool --help
```

---

## Architecture

The hierarchy from outermost to innermost is:

```
Kli           -- orchestrator, command registry, I/O helpers, REPL loop
 -> KliCommand   -- groups related sub-commands under one name
    -> KliAction    -- a specific sub-command with its own named options
       -> KliOption   -- a single parsed option backed by a KliType*
```

Each layer uses a fluent builder API. Every mutator returns `static` so the
chain works correctly in subclasses.

---

## Commands and Actions

```php
$kli = Kli::new('my-tool');

// Create a command (must be 2+ chars: [a-zA-Z0-9][a-zA-Z0-9-_]+)
$cmd = $kli->command('user');
$cmd->description('User management commands');

// Create actions on the command (colons allowed: "create:admin")
$list   = $cmd->action('list',   'List all users');
$create = $cmd->action('create', 'Create a new user');
$delete = $cmd->action('delete', 'Delete a user');
```

**Built-in flags** handled automatically by `Kli::execute()`:

| Token              | Effect                                 |
| ------------------ | -------------------------------------- |
| `--help` / `-?`    | Show help (top-level, command, action) |
| `--version` / `-v` | Show version string                    |
| `--`               | Stop option parsing; rest is anonymous |

---

## Options and Types

`KliAction::option()` adds an option and returns the `KliOption` object.
Call a type setter on it; the setter returns the **type object** (not the
option), so type-specific constraints are chained on the type:

```php
$act->option('name',  'n')            // KliOption
    ->string()                        // returns KliTypeString
    ->min(2)->max(50)
    ->def('World');

$act->option('count', 'c')
    ->number()                        // returns KliTypeNumber
    ->integer()->min(1)->def(10);
```

### String type

```php
$opt->string(?int $min = null, ?int $max = null): KliTypeString
```

| Method                                           | Description                           |
| ------------------------------------------------ | ------------------------------------- |
| `->min(int $n, ?string $msg = null)`             | Minimum character count (multibyte)   |
| `->max(int $n, ?string $msg = null)`             | Maximum character count (multibyte)   |
| `->pattern(string $regex, ?string $msg = null)`  | Regex the value must match            |
| `->validator(callable $fn, ?string $msg = null)` | Custom callable; return false to fail |
| `->def(string $value)`                           | Default value                         |

### Number type

```php
$opt->number(?float $min = null, ?float $max = null): KliTypeNumber
```

| Method                           | Description                         |
| -------------------------------- | ----------------------------------- | ------------- |
| `->min(float $n, ?string $msg)`  | Minimum value (inclusive)           |
| `->max(float $n, ?string $msg)`  | Maximum value (inclusive)           |
| `->integer(?string $msg = null)` | Require a whole number (no decimal) |
| `->def(int                       | float $value)`                      | Default value |

Returns `int` when no decimal part, `float` otherwise.

### Bool type

```php
$opt->bool(bool $strict = false, ?string $msg = null): KliTypeBool
```

Non-strict mode accepts: `true`, `false`, `1`, `0`, `'1'`, `'0'`, `'true'`,
`'false'`, `'yes'`, `'no'`, `'y'`, `'n'`.

Strict mode accepts only: `true`, `false`, `'y'`, `'n'`, `'yes'`, `'no'`.

Always resolves to a native PHP `bool`.

A bool option passed without a value (e.g. `--verbose`) is treated as `true`.

### Path type

```php
$opt->path(?int $min = null, ?int $max = null): KliTypePath
```

| Method                                          | Description                           |
| ----------------------------------------------- | ------------------------------------- |
| `->file(?string $msg = null)`                   | Accept only existing files            |
| `->dir(?string $msg = null)`                    | Accept only existing directories      |
| `->writable(?string $msg = null)`               | Accept only writable paths            |
| `->multiple()`                                  | Return `string[]` instead of `string` |
| `->min(int $n, ?string $msg = null)`            | Minimum number of resolved paths      |
| `->max(int $n, ?string $msg = null)`            | Maximum number of resolved paths      |
| `->pattern(string $regex, ?string $msg = null)` | Filter resolved paths by regex        |
| `->def(string $value)`                          | Default value                         |

Paths are resolved via `realpath()` — they must exist on the filesystem.

---

## Handlers

Every action must have a handler before execution:

```php
// Per-action handler (checked first):
$act->handler(function (KliArgs $args) use ($kli): void {
    // handle the action
});

// Command-level fallback handler (all actions that have no own handler):
$cmd->handler(function (KliAction $action, KliArgs $args) use ($kli): void {
    // $action tells you which sub-command was invoked
});
```

If neither handler is set, `Kli::execute()` throws `KliRuntimeException` at
dispatch time.

---

## Parsed Arguments — KliArgs

The `KliArgs` object is delivered to every handler. Retrieve values by option
name, alias, or single-char flag:

```php
$act->option('output', 'o')->string()->def('/tmp');
$act->option('verbose')->bool()->def(false);

$act->handler(function (KliArgs $args): void {
    $path    = $args->get('output');   // by name
    $path    = $args->get('o');        // by flag
    $verbose = $args->get('verbose');  // bool
    $extra   = $args->getAnonymousArgs(); // anything after --
    $first   = $args->getAnonymousAt(0);
});
```

---

## Positional (Offset) Arguments

Map positional (anonymous) tokens directly to an option with `offsets()`:

```php
// Single position: the first bare token becomes --file
$act->option('file', 'f')->path()->offsets(0);

// Range: tokens at positions 0..2 are collected into an array
$act->option('files', 'f')->path()->multiple()->offsets(0, 2);

// Infinite range: all tokens from position 1 onward
$act->option('args')->string()->offsets(1, INF);
```

Offset ranges cannot overlap across options; a duplicate triggers
`KliRuntimeException` at configuration time.

---

## Interactive Prompts

Mark an option as required **and** enable prompting so the user is asked when
the value is not supplied on the command line:

```php
$act->option('password', 'p')
    ->string()->min(8)
    ->required()
    ->prompt(true, 'Enter your password', true);  // third arg = hide input

$act->option('name', 'n')
    ->string()
    ->required()
    ->prompt(true, 'Your name');
```

If the user enters an invalid value the prompt is shown again with the
validation error until a valid value is provided.

---

## Interactive REPL Mode

Pass `allow_interactive_mode: true` to `Kli::new()`. When the tool is invoked with
no arguments it enters an interactive loop where the user can type commands:

```php
$kli = Kli::new('my-tool', allow_interactive_mode: true);
// ... register commands/actions ...
$kli->execute($argv);
```

```
$ my-tool
  ℹ  Hint: type "quit" or "exit" to stop.

my-tool> greet say --name=Alice
  ✔  Hello, Alice!

my-tool> exit
```

Override `readLine()` in a subclass to customise input (useful in tests — see
`ScriptedKli` in `tests/ScriptedKli.php`). Override `welcome()` to print a
custom banner.

---

## ANSI Styling — KliStyle

Chain color and style methods, then call `apply()` to wrap a string:

```php
$s = $kli->style();   // fresh KliStyle instance

echo $s->bold()->apply('Important');
echo $s->red()->bold()->apply('Error text');
echo $s->green()->apply('OK');
echo $s->cyan()->dim()->apply('Hint');
echo $s->backgroundBlue()->white()->apply(' banner ');
```

**Foreground colors** (method names):
`black()`, `darkGray()`, `blue()`, `lightBlue()`, `green()`, `lightGreen()`,
`cyan()`, `lightCyan()`, `red()`, `lightRed()`, `magenta()`, `lightMagenta()`,
`yellow()`, `lightGray()`, `white()`, `normal()`

**Background colors**: `backgroundBlack()`, `backgroundRed()`,
`backgroundGreen()`, `backgroundYellow()`, `backgroundBlue()`,
`backgroundMagenta()`, `backgroundCyan()`, `backgroundLightGray()`

**Styles**: `bold()`, `dim()`, `underline()`, `blink()`, `invert()`, `hidden()`

ANSI codes are emitted only when `STDOUT` is a TTY. Override with static flags:

```php
KliStyle::forceAnsi(true);   // always emit (e.g. for tests that assert ANSI output)
KliStyle::disableAnsi(true); // never emit  (takes precedence over forceAnsi)
KliStyle::forceAnsi(false);  // restore auto-detect
KliStyle::disableAnsi(false);
```

---

## Table Rendering — KliTable

```php
$table = $kli->table();

$table->addHeader('ID',     'id')->alignRight()->setWidth(4);
$table->addHeader('Name',   'name')->alignLeft();
$table->addHeader('Active', 'active')->alignCenter()
      ->setCellFormatter(KliTableFormatter::bool());
$table->addHeader('Score',  'score')->alignRight()
      ->setCellFormatter(KliTableFormatter::number(2));

$table->addRows([
    ['id' => 1, 'name' => 'Alice', 'active' => true,  'score' => 99.5],
    ['id' => 2, 'name' => 'Bob',   'active' => false, 'score' => 74.0],
]);

echo $table;  // __toString() calls render()
```

**Built-in formatters** (`KliTableFormatter` static factories):

| Factory                                                             | Renders as                           |
| ------------------------------------------------------------------- | ------------------------------------ |
| `::bool(?KliStyle $style = null)`                                   | `true` -> "Yes", `false` -> "No"     |
| `::number(int $decimals, string $dp, string $ts, ?KliStyle $style)` | `number_format()` output             |
| `::date(string $format = 'Y-m-d H:i:s', ?KliStyle $style)`          | Unix timestamp formatted by `date()` |

**Custom formatter**: implement `KliTableCellFormatterInterface`:

```php
class RedNegativeFormatter implements KliTableCellFormatterInterface
{
    public function format(mixed $value, KliTableHeader $header, array $row): string
    {
        return (string) $value;
    }

    public function getStyle(mixed $value, KliTableHeader $header, array $row): ?KliStyle
    {
        return $value < 0 ? (new KliStyle())->red() : null;
    }
}
```

**Styling borders**:

```php
$table->borderStyle()->cyan();  // color all border characters

// Override specific border characters (merges into defaults):
$table->setBorderChars(['top-left' => '+', 'top' => '-']);
```

Default border character set: `╔═╤╗║│╟─┼╢╚═╧╝`.

---

## Output Helpers

All helpers return `static` for chaining. `$wrap = true` runs the message
through `wordwrap()` at 80 characters.

```php
$kli->info('Hint: use --help');                //   ℹ  ...  (cyan icon)
$kli->warn('Deprecated flag used');            //   ⚠  ...  (yellow icon)
$kli->success('All done');                     //   ✔  ...  (green icon)
$kli->error('Something went wrong');           //   ✖  ...  (red bold icon)
$kli->writeLn('raw output');                   // new line then string
$kli->write('inline output');
$kli->log('message', wrap: true);              // append to log file (if configured)
$kli->bell(1);                                 // terminal bell character
```

### Exit codes on output methods

`error()`, `warn()`, and `success()` accept an optional `?int $exit` parameter
that terminates the process after printing:

| Method      | Default `$exit` | Behaviour when `$exit` is non-null                                     |
| ----------- | --------------- | ---------------------------------------------------------------------- |
| `error()`   | `1`             | Terminates with the given code **by default**; pass `null` to skip.    |
| `warn()`    | `null`          | Does **not** terminate by default; pass a code to stop after printing. |
| `success()` | `null`          | Does **not** terminate by default; pass `0` to stop cleanly.           |

In **interactive REPL mode** a non-null `$exit` throws `KliAbortException`
instead of calling `exit()`, so the REPL loop continues rather than dying.

To terminate unconditionally regardless of mode, call `terminate()` directly:

```php
$kli->error('Fatal: cannot continue', exit: null); // just print
$kli->terminate(1);                                // always exits
```

**Version string** — override `getVersion()` in your subclass to return the
real semantic version:

```php
class MyTool extends Kli
{
    public function getVersion(bool $full = false): string
    {
        $version = '2.3.1';
        return $full ? basename($this->getCliEntryPoint()) . ' v' . $version : $version;
    }
}
```

---

## Exception Handling

```
Exception
 |- KliException       base for library errors; catch this in application code
 \- KliInputException  bad user input; caught and displayed by Kli::execute()
                       NOT a child of KliException

RuntimeException
 \- KliRuntimeException  developer/config error; never caught internally
    \- KliAbortException  thrown by error()/warn()/success() in interactive
                          mode when $exit is non-null; caught by execute()
```

`KliInputException` is thrown by all `KliType::validate()` implementations and
by `KliParser`. It is caught inside `execute()` and shown via `error()`.

`KliRuntimeException` is thrown at configuration time (bad names, duplicate
flags, conflicting offsets, missing handler). It always propagates.

`KliAbortException` is an internal signal thrown by `error()`, `warn()`, and
`success()` when `$exit` is non-null and the CLI is in interactive mode.
`execute()` catches it so the REPL loop continues. Application code should
never need to catch it directly.

---

## Naming Rules

| Element      | Pattern                        | Notes                              |
| ------------ | ------------------------------ | ---------------------------------- |
| Command name | `[a-zA-Z0-9][a-zA-Z0-9-_]+`    | 2+ chars, no colons                |
| Action name  | `[a-zA-Z0-9]([a-zA-Z0-9-_:]+)` | colons allowed, e.g. `create:user` |
| Option name  | `[a-zA-Z0-9][a-zA-Z0-9-_]*`    | 1+ chars                           |
| Option alias | `[a-zA-Z0-9][a-zA-Z0-9-_]+`    | 2+ chars                           |
| Option flag  | `[a-zA-Z0-9]`                  | exactly 1 char                     |

Auto-promotion rules applied inside `KliOption::__construct()`:

- A 1-char option name is automatically promoted to a flag (e.g. `option('v')` sets flag `v`).
- A multi-char option name is automatically added as an alias.

---

## Extending Kli

Subclass `Kli` to override behaviour:

```php
class MyApp extends Kli
{
    // Custom version string
    public function getVersion(bool $full = false): string { ... }

    // Custom welcome banner shown in interactive mode and on --help
    public function welcome(): void
    {
        $this->writeLn($this->style()->bold()->apply('Welcome to MyApp!'));
    }

    // Override input source (useful in tests)
    public function readLine(string $prompt, bool $is_password = false): string { ... }

    // Override terminate() to intercept exit() calls (useful in tests)
    public function terminate(int $code = 0): never
    {
        throw new MyAppExitException($code);
    }
}
```

---

## Testing

Tests live in `tests/` and use snapshot files in `tests/snapshots/`.

**Snapshot pattern** (`TestUtils::ensureSnapshotFile()`): on first run the
snapshot is written; subsequent runs assert against it with
`assertStringEqualsFile()`. Delete a snapshot file to regenerate it.

Two snapshot variants are produced for output that includes ANSI codes:

| Suffix     | How produced                  |
| ---------- | ----------------------------- |
| (none)     | `KliStyle::disableAnsi(true)` |
| `.tty.txt` | `KliStyle::forceAnsi(true)`   |

**`ScriptedKli`** (`tests/ScriptedKli.php`) — test helper for interactive
prompts and REPL mode. Subclasses `Kli`, overrides `readLine()` with a
pre-scripted queue of responses, and records every prompt shown:

```php
$kli = new ScriptedKli('test', script: ['Alice', 'quit'], allow_interactive_mode: true);
$kli->executeString('greet say');
$this->assertSame(['Enter name: ', 'test> '], $kli->promptLog);
```

---

## Developer Workflows

```sh
./run_test   # PHPUnit with --testdox --do-not-cache-result
./csfix      # psalm static analysis then oliup-cs style auto-fix
```

- PHPUnit config: `phpunit.xml.dist` — `failOnWarning` and `failOnRisky` are `true`
- Psalm config: `psalm.xml` — error level 4, analyzes `src/` only
- CS rules: `oliup/oliup-cs-php` (see `phpcs.xml.dist`)
