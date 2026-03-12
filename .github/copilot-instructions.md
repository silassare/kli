# Kli — Copilot Instructions

IMPORTANT:

- no hallucination or invention. Go through the entire code base to understand before generating code, the `.github/copilot-instructions.md` or docs. Focus on what can be directly observed in the codebase, not on idealized practices or assumptions.
- When a bug or issue is found in the codebase, do not fix it directly, but rather ask for feedback and approval.
- If `AGENTS.md`, `CLAUDE.md`, `GEMINI.md` do not exist, symlink them to `.github/copilot-instructions.md`.
- **No Unicode shortcut characters in comments or docblocks.** Always use plain ASCII equivalents:

| use      | don't use  |
| -------- | ---------- |
| `->`     | `→`        |
| `<-`     | `←`        |
| `<->`    | `↔`        |
| `-->`    | `───▶`     |
| `>=`     | `≥`        |
| `<=`     | `≤`        |
| `!=`     | `≠`        |
| `*`      | `×`        |
| `/`      | `÷`        |
| `-`      | `—` or `–` |
| `IN`     | `∈`        |
| `NOT IN` | `∉`        |
| `...`    | `…`        |

---

## Overview

`kli` is a PHP >=8.1 fluent-builder CLI framework. Namespace root: `Kli`. Every source file begins with `declare(strict_types=1)`. The hierarchy is:

```
Kli (orchestrator)
 -> KliCommand  (groups related sub-commands)
    -> KliAction  (a specific sub-command with its own options)
       -> KliOption  (a single parsed option, typed via KliType*)
```

## Developer Workflows

```sh
./run_test   # runs PHPUnit with --testdox --do-not-cache-result
./csfix      # psalm static analysis then oliup-cs style auto-fix
```

PHPUnit config: `phpunit.xml.dist` — `failOnWarning` and `failOnRisky` are both `true`.
Psalm config: `psalm.xml` — error level 4, analyzes `src/` only.
CS rules come from `oliup/oliup-cs-php` (see `phpcs.xml.dist`).

## Fluent Builder Pattern

All mutator methods return `static` (not `self`) to support subclassing. Every builder class uses named factory methods rather than `new`:

```php
$kli = Kli::new('my-tool');
$cmd = $kli->command('hello');
$act = $cmd->action('say');
$act->option('name', 'n')->string()->def('World');
$act->option('age', 'a')->number()->min(0)->def(18);
```

`KliOption` type setters (`->string()`, `->bool()`, `->number()`, `->path()`) return the **type object**, not `$option`. Chain type-specific methods (`def`, `min`, `max`, `pattern`, etc.) on the returned type, not on `$option` itself.

## Handler Registration — Two Levels

```php
// Per-action handler (checked first):
$act->handler(function (KliArgs $args): void { ... });

// Command-level fallback handler (receives action as first arg):
$cmd->handler(function (KliAction $action, KliArgs $args): void { ... });
```

Exactly one of these must be set; the absence of both is a `KliRuntimeException` at execution time.

## Exception Hierarchy

```
Exception
 |- KliException          (base for consumers to catch library errors)
 |   (NOT a parent of KliInputException — catch(KliException) won't catch input errors)
 \- KliInputException     (bad user input; caught internally by Kli::execute(), shown as error())

RuntimeException
 \- KliRuntimeException   (developer/config errors — invalid names, duplicate flags, etc.)
```

`KliInputException` is thrown by all `KliType::validate()` implementations and by `KliParser`.
`KliRuntimeException` is thrown at configuration time and is never caught internally.

## Naming Constraints (enforced by regex)

| Element      | Pattern                        | Notes                               |
| ------------ | ------------------------------ | ----------------------------------- |
| Command name | `[a-zA-Z0-9][a-zA-Z0-9-_]+`    | 2+ chars, no colons                 |
| Action name  | `[a-zA-Z0-9]([a-zA-Z0-9-_:]+)` | colons allowed, e.g., `create:user` |
| Option name  | `[a-zA-Z0-9][a-zA-Z0-9-_]*`    | 1+ chars                            |
| Option alias | `[a-zA-Z0-9][a-zA-Z0-9-_]+`    | 2+ chars                            |
| Option flag  | `[a-zA-Z0-9]`                  | exactly 1 char                      |

A 1-char option name is auto-promoted to a flag; a longer name auto-adds itself as an alias.

## Option Locking

Once an option is added to a `KliAction` via `addOption()`, it is `lock()`-ed; subsequent calls to `offsets()` throw `KliRuntimeException`. Do not modify options after calling `addOption()`.

## ANSI Styling

`KliStyle::apply()` emits ANSI codes only when `stream_isatty(STDOUT)` is true — output is plain text in pipes/redirects. Reset codes are feature-specific (per-attribute reset, not global `\033[0m`), so multiple independent styles can coexist on one line.

## Table Rendering

```php
$table = $kli->table();
$table->addHeader('Name', 'name')->alignLeft();
$table->addHeader('Age',  'age')->alignRight()->setWidth(6);
$table->addRows($data);
echo $table; // __toString() calls render()
```

Default borders use Unicode box-drawing (`╔═╤╗║│╟─┼╢╚═╧╝`). Override with `setBorderChars(array)` (merges, not replaces). `KliTableFormatter` provides built-in cell formatters: `::bool()`, `::number()`, `::date()`. Custom formatters implement `KliTableCellFormatterInterface` and receive the full row for conditional styling.

## Snapshot Testing

Tests in `tests/` compare output against golden files in `tests/snapshots/`. The pattern is write-on-first-run via `TestUtils::ensureSnapshotFile()` — if a snapshot does not exist it is created; otherwise `assertStringEqualsFile()` validates against it. To regenerate a snapshot, delete the corresponding file and rerun the tests.

## Key Files

- [src/Kli.php](../src/Kli.php) — entry point, dispatch logic, I/O helpers
- [src/KliParser.php](../src/KliParser.php) — argv parsing and option validation
- [src/KliOption.php](../src/KliOption.php) — option config, type-returning builder
- [src/Types/](../src/Types/) — `KliTypeString`, `KliTypeBool`, `KliTypeNumber`, `KliTypePath`
- [src/Exceptions/](../src/Exceptions/) — `KliException`, `KliInputException`, `KliRuntimeException`
- [src/Table/](../src/Table/) — `KliTable`, `KliTableHeader`, `KliTableFormatter`
- [tests/KliTest.php](../tests/KliTest.php) — idiomatic builder usage examples
