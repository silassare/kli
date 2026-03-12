<?php

/**
 * Copyright (c) 2017-present, Emile Silas Sare
 *
 * This file is part of Kli package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Kli\Tests;

use Kli\Kli;
use Kli\KliAction;
use Kli\KliArgs;
use Kli\KliOption;
use Kli\KliStyle;
use PHPUnit\Framework\TestCase;

/**
 * Class KliIntegrationTest.
 *
 * End-to-end tests that build a small but complete CLI tool with Kli and
 * exercise the full pipeline: option parsing, type validation, handler
 * dispatch, error output, and positional offsets.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliIntegrationTest extends TestCase
{
    private Kli $kli;

    /** @var array<string, mixed> */
    private array $captured = [];

    protected function setUp(): void
    {
        $this->kli      = Kli::new('klitest');
        $this->captured = [];
        $this->buildCli();
    }

    // -----------------------------------------------------------------------
    // String option
    // -----------------------------------------------------------------------

    public function testStringOptionUsesDefault(): void
    {
        $this->exec('greet say');

        self::assertSame('World', $this->captured['say']['name']);
    }

    public function testStringOptionLongSyntax(): void
    {
        $this->exec('greet say --name=Alice');

        self::assertSame('Alice', $this->captured['say']['name']);
    }

    public function testStringOptionShortFlagSyntax(): void
    {
        $this->exec('greet say -n=Bob');

        self::assertSame('Bob', $this->captured['say']['name']);
    }

    // -----------------------------------------------------------------------
    // Number option
    // -----------------------------------------------------------------------

    public function testNumberOptionParsedAsInt(): void
    {
        $this->exec('greet say --count=7');

        self::assertSame(7, $this->captured['say']['count']);
    }

    public function testNumberOptionParsedAsFloat(): void
    {
        $this->exec('greet validate --age=25.5');

        self::assertSame(25.5, $this->captured['validate']['age']);
    }

    public function testNumberUsesDefault(): void
    {
        $this->exec('greet say');

        self::assertSame(1, $this->captured['say']['count']);
    }

    public function testNumberBelowMinShowsError(): void
    {
        $output = $this->exec('greet validate --age=-1');

        self::assertStringContainsString('min=', $output);
        self::assertArrayNotHasKey('validate', $this->captured);
    }

    public function testNumberAboveMaxShowsError(): void
    {
        $output = $this->exec('greet validate --age=999');

        self::assertStringContainsString('max=', $output);
        self::assertArrayNotHasKey('validate', $this->captured);
    }

    // -----------------------------------------------------------------------
    // Bool option
    // -----------------------------------------------------------------------

    public function testBoolFlagAloneSetsTrue(): void
    {
        // combined flag -s (no = value) -> parser sets value = true (bool)
        $this->exec('greet say -s');

        self::assertTrue($this->captured['say']['shout']);
    }

    public function testBoolLongOptionYes(): void
    {
        $this->exec('greet say --shout=yes');

        self::assertTrue($this->captured['say']['shout']);
    }

    public function testBoolLongOptionNo(): void
    {
        $this->exec('greet say --shout=no');

        self::assertFalse($this->captured['say']['shout']);
    }

    public function testBoolLongOptionFalseString(): void
    {
        $this->exec('greet say --shout=false');

        self::assertFalse($this->captured['say']['shout']);
    }

    public function testBoolShortFlagWithValueFalse(): void
    {
        $this->exec('greet say -s=false');

        self::assertFalse($this->captured['say']['shout']);
    }

    public function testBoolShortFlagWithValueYes(): void
    {
        $this->exec('greet say -s=yes');

        self::assertTrue($this->captured['say']['shout']);
    }

    // -----------------------------------------------------------------------
    // Required option
    // -----------------------------------------------------------------------

    public function testRequiredOptionMissingShowsError(): void
    {
        $output = $this->exec('greet hello');

        self::assertStringContainsString('email', $output);
        self::assertArrayNotHasKey('hello', $this->captured);
    }

    public function testRequiredOptionProvided(): void
    {
        $this->exec('greet hello --email=test@example.com');

        self::assertSame('test@example.com', $this->captured['hello']['email']);
        self::assertSame('', $this->captured['hello']['to']); // optional, uses default
    }

    public function testRequiredOptionWithOptionalAlsoSet(): void
    {
        // 'to' option has flag 't', test short-flag syntax for the optional arg
        $this->exec('greet hello --email=user@example.org -t=Alice');

        self::assertSame('user@example.org', $this->captured['hello']['email']);
        self::assertSame('Alice', $this->captured['hello']['to']);
    }

    // -----------------------------------------------------------------------
    // Positional offset options
    // -----------------------------------------------------------------------

    public function testPositionalOffsetsBothFilled(): void
    {
        $this->exec('greet pos hello world');

        self::assertSame('hello', $this->captured['pos']['first']);
        self::assertSame('world', $this->captured['pos']['second']);
    }

    public function testPositionalOffsetFirstOnlyUsesDefaultForSecond(): void
    {
        $this->exec('greet pos hello');

        self::assertSame('hello', $this->captured['pos']['first']);
        self::assertSame('', $this->captured['pos']['second']);
    }

    // -----------------------------------------------------------------------
    // Stop-parsing (--)
    // -----------------------------------------------------------------------

    public function testStopParsingPassesRemainderAsAnonymous(): void
    {
        // --count=99 comes after --, so it must NOT be parsed as the count option
        $this->exec('greet say --name=Alice -- --count=99');

        self::assertSame('Alice', $this->captured['say']['name']);
        self::assertSame(1, $this->captured['say']['count']); // default, not 99
        self::assertContains('--count=99', $this->captured['say']['anonymous']);
    }

    // -----------------------------------------------------------------------
    // String pattern validation
    // -----------------------------------------------------------------------

    public function testStringPatternPassesWhenMatches(): void
    {
        $this->exec('greet validate --code=XYZ');

        self::assertSame('XYZ', $this->captured['validate']['code']);
    }

    public function testStringPatternFailsWhenNoMatch(): void
    {
        // 'abc' fails the ~^[A-Z]{3}$~ pattern
        $output = $this->exec('greet validate --code=abc');

        self::assertStringContainsString('expression', $output);
        self::assertArrayNotHasKey('validate', $this->captured);
    }

    // -----------------------------------------------------------------------
    // Path option
    // -----------------------------------------------------------------------

    public function testPathOptionAcceptsExistingFile(): void
    {
        $this->exec('greet withfile --path=' . __FILE__);

        self::assertSame(\realpath(__FILE__), $this->captured['withfile']['path']);
    }

    public function testPathOptionRejectsNonExistentPath(): void
    {
        $output = $this->exec('greet withfile --path=/nonexistent/path/xyz');

        self::assertStringContainsString('valid path', $output);
        self::assertArrayNotHasKey('withfile', $this->captured);
    }

    public function testPathOptionRejectsDirectoryInFileMode(): void
    {
        // file() mode -> directories must be rejected
        $output = $this->exec('greet withfile --path=' . \sys_get_temp_dir());

        self::assertStringContainsString('file', $output);
        self::assertArrayNotHasKey('withfile', $this->captured);
    }

    // -----------------------------------------------------------------------
    // Command-level fallback handler
    // -----------------------------------------------------------------------

    public function testCommandFallbackHandlerCalledForSave(): void
    {
        $this->exec('store save');

        self::assertSame('save', $this->captured['store']['action']);
        self::assertSame('default-key', $this->captured['store']['key']);
    }

    public function testCommandFallbackHandlerCalledForLoad(): void
    {
        $this->exec('store load --key=mykey');

        self::assertSame('load', $this->captured['store']['action']);
        self::assertSame('mykey', $this->captured['store']['key']);
    }

    // -----------------------------------------------------------------------
    // Unknown command / action
    // -----------------------------------------------------------------------

    public function testUnknownCommandShowsError(): void
    {
        $output = $this->exec('nope action');

        self::assertStringContainsString('unknown command', $output);
    }

    public function testUnknownActionShowsError(): void
    {
        $output = $this->exec('greet nope');

        self::assertStringContainsString('unknown action', $output);
    }

    // -----------------------------------------------------------------------
    // String length constraints
    // -----------------------------------------------------------------------

    public function testStringMinLengthPassesWhenMet(): void
    {
        $kli      = Kli::new('t');
        $result   = [];
        $cmd      = $kli->command('do');
        $act      = $cmd->action('it');
        $act->option('val', 'v')->string()->min(3)->def('aaa');
        $act->handler(static function (KliArgs $args) use (&$result): void {
            $result['val'] = $args->get('val');
        });

        \ob_start();
        $kli->execute(['t', 'do', 'it', '--val=abc']);
        \ob_get_clean();

        self::assertSame('abc', $result['val']);
    }

    public function testStringMinLengthFailsWhenTooShort(): void
    {
        $kli = Kli::new('t');
        $cmd = $kli->command('do');
        $act = $cmd->action('it');
        $act->option('val', 'v')->string()->min(5)->def('aaaaa');
        $act->handler(static function (): void {});

        $output = $this->execOn($kli, 'do it --val=ab');

        self::assertStringContainsString('minlength', $output);
    }

    public function testStringMaxLengthPassesWhenMet(): void
    {
        $kli    = Kli::new('t');
        $result = [];
        $cmd    = $kli->command('do');
        $act    = $cmd->action('it');
        $act->option('val')->string()->max(5)->def('hi');
        $act->handler(static function (KliArgs $args) use (&$result): void {
            $result['val'] = $args->get('val');
        });

        \ob_start();
        $kli->execute(['t', 'do', 'it', '--val=hello']);
        \ob_get_clean();

        self::assertSame('hello', $result['val']);
    }

    public function testStringMaxLengthFailsWhenTooLong(): void
    {
        $kli = Kli::new('t');
        $cmd = $kli->command('do');
        $act = $cmd->action('it');
        $act->option('val')->string()->max(3)->def('hi');
        $act->handler(static function (): void {});

        $output = $this->execOn($kli, 'do it --val=toolong');

        self::assertStringContainsString('maxlength', $output);
    }

    public function testStringCustomValidatorPassesAndFails(): void
    {
        $kli      = Kli::new('t');
        $result   = [];
        $cmd      = $kli->command('do');
        $act      = $cmd->action('it');
        $act->option('val')->string()->validator(static function (string $v): bool {
            return \str_starts_with($v, 'ok-');
        })->def('ok-default');
        $act->handler(static function (KliArgs $args) use (&$result): void {
            $result['val'] = $args->get('val');
        });

        \ob_start();
        $kli->execute(['t', 'do', 'it', '--val=ok-pass']);
        \ob_get_clean();

        self::assertSame('ok-pass', $result['val']);

        $output = $this->execOn($kli, 'do it --val=bad');

        self::assertStringContainsString('validator', $output);
    }

    // -----------------------------------------------------------------------
    // Number integer mode
    // -----------------------------------------------------------------------

    public function testNumberIntegerModeRejectsFloat(): void
    {
        $kli = Kli::new('t');
        $cmd = $kli->command('do');
        $act = $cmd->action('it');
        $act->option('num')->number()->integer()->def(1);
        $act->handler(static function (): void {});

        $output = $this->execOn($kli, 'do it --num=1.5');

        self::assertStringContainsString('integer', $output);
    }

    public function testNumberIntegerModeAcceptsInt(): void
    {
        $kli    = Kli::new('t');
        $result = [];
        $cmd    = $kli->command('do');
        $act    = $cmd->action('it');
        $act->option('num')->number()->integer()->def(1);
        $act->handler(static function (KliArgs $args) use (&$result): void {
            $result['num'] = $args->get('num');
        });

        \ob_start();
        $kli->execute(['t', 'do', 'it', '--num=7']);
        \ob_get_clean();

        self::assertSame(7, $result['num']);
    }

    // -----------------------------------------------------------------------
    // Path dir-only mode
    // -----------------------------------------------------------------------

    public function testPathDirOnlyAcceptsDirectory(): void
    {
        $kli    = Kli::new('t');
        $result = [];
        $cmd    = $kli->command('do');
        $act    = $cmd->action('it');
        $act->option('dir')->path()->dir()->def('');
        $act->handler(static function (KliArgs $args) use (&$result): void {
            $result['dir'] = $args->get('dir');
        });

        $tmpdir = \sys_get_temp_dir();

        \ob_start();
        $kli->execute(['t', 'do', 'it', '--dir=' . $tmpdir]);
        \ob_get_clean();

        self::assertSame(\realpath($tmpdir), $result['dir']);
    }

    public function testPathDirOnlyRejectsFile(): void
    {
        $kli = Kli::new('t');
        $cmd = $kli->command('do');
        $act = $cmd->action('it');
        $act->option('dir')->path()->dir()->def('');
        $act->handler(static function (): void {});

        $output = $this->execOn($kli, 'do it --dir=' . __FILE__);

        self::assertStringContainsString('directory', $output);
    }

    // -----------------------------------------------------------------------
    // Version and help flags
    // -----------------------------------------------------------------------

    public function testVersionFlagLong(): void
    {
        \ob_start();
        $this->kli->execute(['klitest', '--version']);
        $output = (string) \ob_get_clean();

        self::assertStringContainsString('v1.0.0', $output);
    }

    public function testVersionFlagShort(): void
    {
        \ob_start();
        $this->kli->execute(['klitest', '-v']);
        $output = (string) \ob_get_clean();

        self::assertStringContainsString('v1.0.0', $output);
    }

    public function testHelpFlagTopLevel(): void
    {
        \ob_start();
        KliStyle::disableAnsi(true);

        try {
            $this->kli->execute(['klitest', '--help']);
        } finally {
            KliStyle::disableAnsi(false);
        }

        $output = (string) \ob_get_clean();

        self::assertStringContainsString('Usage', $output);
        self::assertStringContainsString('Commands', $output);
        self::assertStringContainsString('greet', $output);
    }

    public function testHelpFlagShort(): void
    {
        \ob_start();
        KliStyle::disableAnsi(true);

        try {
            $this->kli->execute(['klitest', '-?']);
        } finally {
            KliStyle::disableAnsi(false);
        }

        $output = (string) \ob_get_clean();

        self::assertStringContainsString('Usage', $output);
    }

    public function testHelpFlagForCommand(): void
    {
        \ob_start();
        KliStyle::disableAnsi(true);

        try {
            $this->kli->execute(['klitest', 'greet', '--help']);
        } finally {
            KliStyle::disableAnsi(false);
        }

        $output = (string) \ob_get_clean();

        self::assertStringContainsString('Actions', $output);
        self::assertStringContainsString('say', $output);
    }

    public function testHelpFlagForAction(): void
    {
        \ob_start();
        KliStyle::disableAnsi(true);

        try {
            $this->kli->execute(['klitest', 'greet', 'say', '--help']);
        } finally {
            KliStyle::disableAnsi(false);
        }

        $output = (string) \ob_get_clean();

        self::assertStringContainsString('Options', $output);
        self::assertStringContainsString('--name', $output);
    }

    // -----------------------------------------------------------------------
    // Command without action shows info
    // -----------------------------------------------------------------------

    public function testCommandWithoutActionShowsAvailableActions(): void
    {
        $output = $this->exec('greet');

        self::assertStringContainsString('greet', $output);
        self::assertStringContainsString('say', $output);
    }

    // -----------------------------------------------------------------------
    // Offset options at multiple positions
    // -----------------------------------------------------------------------

    public function testOffsetOptionsAtMultiplePositions(): void
    {
        $kli    = Kli::new('t');
        $result = [];
        $cmd    = $kli->command('do');
        $act    = $cmd->action('it');

        $opt1 = new KliOption('first');
        $opt1->offsets(1);
        $opt1->string()->def('');
        $act->addOption($opt1);

        $opt2 = new KliOption('second');
        $opt2->offsets(2);
        $opt2->string()->def('');
        $act->addOption($opt2);

        $opt3 = new KliOption('third');
        $opt3->offsets(3);
        $opt3->string()->def('');
        $act->addOption($opt3);

        $act->handler(static function (KliArgs $args) use (&$result): void {
            $result = [
                'first'  => $args->get('first'),
                'second' => $args->get('second'),
                'third'  => $args->get('third'),
            ];
        });

        \ob_start();
        $kli->execute(['t', 'do', 'it', 'alpha', 'beta', 'gamma']);
        \ob_get_clean();

        self::assertSame('alpha', $result['first']);
        self::assertSame('beta', $result['second']);
        self::assertSame('gamma', $result['third']);
    }

    // -----------------------------------------------------------------------
    // Interactive prompt tests (via ScriptedKli)
    // -----------------------------------------------------------------------

    public function testInteractivePromptFillsMissingRequiredOption(): void
    {
        $kli      = new ScriptedKli('test', ['Alice']);
        $result   = [];
        $cmd      = $kli->command('greet');
        $act      = $cmd->action('say');
        $act->option('name')->required()->prompt(true, 'Enter name')->string();
        $act->handler(static function (KliArgs $args) use (&$result): void {
            $result['name'] = $args->get('name');
        });

        \ob_start();
        $kli->execute(['test', 'greet', 'say']);
        \ob_get_clean();

        self::assertSame('Alice', $result['name']);
    }

    public function testInteractivePromptUsesDefaultOnEmptyInput(): void
    {
        $kli      = new ScriptedKli('test', ['']); // empty -> fall back to default
        $result   = [];
        $cmd      = $kli->command('greet');
        $act      = $cmd->action('say');
        $act->option('name')->required()->prompt(true, 'Enter name')->string()->def('World');
        $act->handler(static function (KliArgs $args) use (&$result): void {
            $result['name'] = $args->get('name');
        });

        \ob_start();
        $kli->execute(['test', 'greet', 'say']);
        \ob_get_clean();

        self::assertSame('World', $result['name']);
    }

    public function testInteractivePromptRetriesOnInvalidThenAccepts(): void
    {
        // First answer fails validation (too short), second is valid
        $kli    = new ScriptedKli('test', ['ab', 'Alice']);
        $result = [];
        $cmd    = $kli->command('greet');
        $act    = $cmd->action('say');
        $act->option('name')->required()->prompt(true, 'Enter name')->string()->min(3);
        $act->handler(static function (KliArgs $args) use (&$result): void {
            $result['name'] = $args->get('name');
        });

        \ob_start();
        $kli->execute(['test', 'greet', 'say']);
        \ob_get_clean();

        self::assertSame('Alice', $result['name']);
        // readLine was called twice
        self::assertCount(2, $kli->promptLog);
    }

    public function testInteractivePromptBoolDefaultTrueShowsYN(): void
    {
        $kli = new ScriptedKli('test', ['yes']);
        $cmd = $kli->command('greet');
        $act = $cmd->action('say');
        $act->option('flag')->required()->prompt(true, 'Confirm')->bool()->def(true);
        $act->handler(static function (): void {});

        \ob_start();
        $kli->execute(['test', 'greet', 'say']);
        \ob_get_clean();

        self::assertStringContainsString('[Y/n]', $kli->promptLog[0]);
    }

    public function testInteractivePromptBoolDefaultFalseShowsYN(): void
    {
        $kli = new ScriptedKli('test', ['no']);
        $cmd = $kli->command('greet');
        $act = $cmd->action('say');
        $act->option('flag')->required()->prompt(true, 'Confirm')->bool()->def(false);
        $act->handler(static function (): void {});

        \ob_start();
        $kli->execute(['test', 'greet', 'say']);
        \ob_get_clean();

        self::assertStringContainsString('[y/N]', $kli->promptLog[0]);
    }

    public function testInteractivePromptBoolNoDefaultShowsYN(): void
    {
        $kli = new ScriptedKli('test', ['yes']);
        $cmd = $kli->command('greet');
        $act = $cmd->action('say');
        $act->option('flag')->required()->prompt(true, 'Confirm')->bool();
        $act->handler(static function (): void {});

        \ob_start();
        $kli->execute(['test', 'greet', 'say']);
        \ob_get_clean();

        self::assertStringContainsString('[y/n]', $kli->promptLog[0]);
    }

    public function testInteractivePromptDefaultAppearsInPromptText(): void
    {
        $kli = new ScriptedKli('test', ['']);
        $cmd = $kli->command('greet');
        $act = $cmd->action('say');
        $act->option('name')->required()->prompt(true, 'Enter name')->string()->def('World');
        $act->handler(static function (): void {});

        \ob_start();
        $kli->execute(['test', 'greet', 'say']);
        \ob_get_clean();

        self::assertStringContainsString('World', $kli->promptLog[0]);
    }

    // -----------------------------------------------------------------------
    // Interactive mode tests (via ScriptedKli)
    // -----------------------------------------------------------------------

    public function testInteractiveModeExecutesCommandAndQuits(): void
    {
        $result = [];
        $kli    = new ScriptedKli('test', ['greet say --name=Diana', 'quit'], true);
        $cmd    = $kli->command('greet');
        $act    = $cmd->action('say');
        $act->option('name', 'n')->string()->def('World');
        $act->handler(static function (KliArgs $args) use (&$result): void {
            $result[] = $args->get('name');
        });

        \ob_start();
        $kli->interactiveMode();
        \ob_get_clean();

        self::assertSame(['Diana'], $result);
    }

    public function testInteractiveModeExitKeywordStops(): void
    {
        $invocations = 0;
        $kli         = new ScriptedKli('test', ['exit'], true);
        $cmd         = $kli->command('noop');
        $act         = $cmd->action('run');
        $act->handler(static function () use (&$invocations): void {
            ++$invocations;
        });

        \ob_start();
        $kli->interactiveMode();
        \ob_get_clean();

        self::assertSame(0, $invocations);
    }

    public function testInteractiveModeIgnoresBlankInput(): void
    {
        $invocations = 0;
        // Two empty lines then quit
        $kli = new ScriptedKli('test', ['', '', 'quit'], true);
        $cmd = $kli->command('noop');
        $act = $cmd->action('run');
        $act->handler(static function () use (&$invocations): void {
            ++$invocations;
        });

        \ob_start();
        $kli->interactiveMode();
        \ob_get_clean();

        self::assertSame(0, $invocations);
    }

    // -----------------------------------------------------------------------
    // CLI construction
    // -----------------------------------------------------------------------

    private function buildCli(): void
    {
        // ---- command: greet -----------------------------------------------

        $greet = $this->kli->command('greet');

        // action: say -- all basic option types with defaults
        $say = $greet->action('say');
        $say->option('name', 'n')->string()->def('World');
        $say->option('count', 'c')->number()->def(1);
        $say->option('shout', 's')->bool()->def(false);
        $say->handler(function (KliArgs $args): void {
            $this->captured['say'] = [
                'name'      => $args->get('name'),
                'count'     => $args->get('count'),
                'shout'     => $args->get('shout'),
                'anonymous' => $args->getAnonymousArgs(),
            ];
        });

        // action: hello -- required option
        $hello = $greet->action('hello');
        $hello->option('email')->required()->string();
        $hello->option('to', 't')->string()->def('');
        $hello->handler(function (KliArgs $args): void {
            $this->captured['hello'] = [
                'email' => $args->get('email'),
                'to'    => $args->get('to'),
            ];
        });

        // action: pos -- positional offset options (single-position; Bug 2
        //    prevents range offsets)
        // NOTE: execute() passes array_slice($_argv, 2) to the parser, so
        //    $_argv[2] (the action name) occupies offset 0.  Real positional
        //    arguments therefore start at offset 1.
        $pos       = $greet->action('pos');
        $optFirst  = new KliOption('first');
        $optFirst->offsets(1);
        $optFirst->string()->def('');
        $pos->addOption($optFirst);

        $optSecond = new KliOption('second');
        $optSecond->offsets(2);
        $optSecond->string()->def('');
        $pos->addOption($optSecond);

        $pos->handler(function (KliArgs $args): void {
            $this->captured['pos'] = [
                'first'  => $args->get('first'),
                'second' => $args->get('second'),
            ];
        });

        // action: validate -- number range + string pattern
        $validate = $greet->action('validate');
        $validate->option('age', 'a')->number()->min(0.0)->max(120.0)->def(0);
        $validate->option('code', 'k')->string()->pattern('~^[A-Z]{3}$~')->def('ABC');
        $validate->handler(function (KliArgs $args): void {
            $this->captured['validate'] = [
                'age'  => $args->get('age'),
                'code' => $args->get('code'),
            ];
        });

        // action: withfile -- path option restricted to files only
        $withfile = $greet->action('withfile');
        $withfile->option('path', 'p')->path()->file()->def('');
        $withfile->handler(function (KliArgs $args): void {
            $this->captured['withfile'] = [
                'path' => $args->get('path'),
            ];
        });

        // ---- command: store -- command-level fallback handler ---------------

        $store = $this->kli->command('store');
        $store->action('save')->option('key', 'k')->string()->def('default-key');
        $store->action('load')->option('key', 'k')->string()->def('default-key');
        $store->handler(function (KliAction $action, KliArgs $args): void {
            $this->captured['store'] = [
                'action' => $action->getName(),
                'key'    => $args->get('key'),
            ];
        });
    }

    // -----------------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------------

    private function exec(string $cmd): string
    {
        \ob_start();
        $this->kli->executeString($cmd);

        return (string) \ob_get_clean();
    }

    private function execOn(Kli $kli, string $cmd): string
    {
        \ob_start();
        $kli->executeString($cmd);

        return (string) \ob_get_clean();
    }
}
