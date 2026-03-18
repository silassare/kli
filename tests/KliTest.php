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

use Kli\Exceptions\KliException;
use Kli\Exceptions\KliRuntimeException;
use Kli\Kli;
use Kli\KliAction;
use Kli\KliArgs;
use Kli\KliStyle;
use PHPUnit\Framework\TestCase;

/**
 * Class KliTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliTest extends TestCase
{
	/**
	 * @throws KliException
	 */
	public function testExecute(): void
	{
		$kli = self::kliInstance();

		$this->expectOutputString(\PHP_EOL . 'Hello John Doe, you are 18 years old.' . \PHP_EOL);

		$kli->execute(['kli', 'hello', 'say']);
	}

	/**
	 * @throws KliException
	 */
	public function testExecuteString(): void
	{
		$kli = self::kliInstance();

		$this->expectOutputString(\PHP_EOL . 'Hello Harry, you are 25 years old.' . \PHP_EOL);

		$kli->executeString('hello say --name=Harry --age=25');
	}

	/**
	 * @throws KliException
	 */
	public function testUnknownCommand(): void
	{
		$kli  = self::kliInstance();
		$icon = $kli->style()->red()->bold()->apply('✖');

		\ob_start();

		try {
			$kli->execute(['kli', 'hey', 'talk']);
		} catch (KliTerminateCalledException) {
			// terminate(1) was called after the error was printed
		}

		$output = (string) \ob_get_clean();

		self::assertSame(\PHP_EOL . '  ' . $icon . '  unknown command: hey', $output);
	}

	/**
	 * @throws KliException
	 */
	public function testUnknownAction(): void
	{
		$kli  = self::kliInstance();
		$icon = $kli->style()->red()->bold()->apply('✖');

		\ob_start();

		try {
			$kli->execute(['kli', 'hello', 'talk']);
		} catch (KliTerminateCalledException) {
			// terminate(1) was called after the error was printed
		}

		$output = (string) \ob_get_clean();

		self::assertSame(\PHP_EOL . '  ' . $icon . '  hello: unknown action "talk"', $output);
	}

	/**
	 * @throws KliException
	 */
	public function testHelp(): void
	{
		$kli = self::kliInstance();
		$dir = __DIR__ . '/snapshots';

		foreach ([['', false], ['.tty', true]] as [$suffix, $tty]) {
			KliStyle::disableAnsi(!$tty);
			KliStyle::forceAnsi($tty);

			try {
				\ob_start();
				$kli->execute(['kli', '--help']);
				$content = (string) \ob_get_clean();
			} finally {
				KliStyle::disableAnsi(false);
				KliStyle::forceAnsi(false);
			}

			$path = $dir . '/help' . $suffix . '.txt';
			TestUtils::ensureSnapshotFile($path, $content);
			self::assertStringEqualsFile($path, $content);
		}
	}

	/**
	 * @throws KliException
	 */
	public function testExecuteStringWithShortFlags(): void
	{
		$kli = self::kliInstance();

		$this->expectOutputString(\PHP_EOL . 'Hello Alice, you are 30 years old.' . \PHP_EOL);

		// short flags require = syntax: -n=value
		$kli->executeString('hello say -n=Alice -a=30');
	}

	/**
	 * @throws KliException
	 */
	public function testExecuteNoArgTriggersHelp(): void
	{
		$kli = self::kliInstance();

		\ob_start();
		$kli->execute(['kli']);
		$output = \ob_get_clean();

		// no-arg shows help (contains the command name)
		self::assertStringContainsString('hello', $output);
	}

	public function testCommandInvalidNameThrows(): void
	{
		$kli = Kli::new('test');

		$this->expectException(KliRuntimeException::class);
		$kli->command('x'); // single char - too short
	}

	public function testCommandHandlerReceivesActionAndArgs(): void
	{
		$kli      = Kli::new('test');
		$received = [];

		$cmd = $kli->command('do');
		$cmd->handler(static function (KliAction $action, KliArgs $args) use (&$received): void {
			$received['action'] = $action->getName();
			$received['val']    = $args->get('val');
		});

		$act = $cmd->action('it');
		$act->option('val', 'v')->string()->def('ok');

		$kli->execute(['test', 'do', 'it', '--val=check']);

		self::assertSame('it', $received['action']);
		self::assertSame('check', $received['val']);
	}

	public function testPerActionHandlerOverridesCommandHandler(): void
	{
		$kli         = Kli::new('test');
		$cmdInvoked  = false;
		$actInvoked  = false;

		$cmd = $kli->command('do');
		$cmd->handler(static function () use (&$cmdInvoked): void {
			$cmdInvoked = true;
		});

		$act = $cmd->action('it');
		$act->option('val', 'v')->string()->def('x');
		$act->handler(static function (KliArgs $args) use (&$actInvoked): void {
			$actInvoked = true;
		});

		$kli->execute(['test', 'do', 'it']);

		self::assertTrue($actInvoked);
		self::assertFalse($cmdInvoked);
	}

	/**
	 * @throws KliException
	 */
	public function testInvalidInputShowsError(): void
	{
		$kli = new ScriptedKli('test', []);
		$cmd = $kli->command('go');
		$cmd->handler(static function (): void {});
		$act = $cmd->action('run');
		$act->option('count', 'c')->number()->min(1.0);

		\ob_start();

		try {
			$kli->execute(['test', 'go', 'run', '--count=0']);
		} catch (KliTerminateCalledException) {
			// terminate(1) was called after the error was printed
		}

		$output = (string) \ob_get_clean();

		// KliInputException is caught and shown via error()
		self::assertStringContainsString('0', $output);
	}

	public function testKliVersionOutput(): void
	{
		$kli = Kli::new('test');

		\ob_start();
		$kli->execute(['test', '--version']);
		$output = \ob_get_clean();

		self::assertNotEmpty($output);
	}

	public function testBuildCommand(): void
	{
		$kli    = Kli::new('test');
		$result = $kli->buildCommand('greet', 'say', ['--name' => 'Alice', '--age' => '25']);

		self::assertStringContainsString('greet', $result);
		self::assertStringContainsString('say', $result);
	}

	// -----------------------------------------------------------------------
	// warn()
	// -----------------------------------------------------------------------

	public function testWarnOutputsIconAndMessage(): void
	{
		$kli = new ScriptedKli('test', []);

		KliStyle::disableAnsi(true);

		try {
			\ob_start();
			$kli->warn('something dangerous', false);
			$output = (string) \ob_get_clean();
		} finally {
			KliStyle::disableAnsi(false);
		}

		self::assertStringContainsString('⚠', $output);
		self::assertStringContainsString('something dangerous', $output);
	}

	public function testWarnWithNullExitReturnsInstance(): void
	{
		$kli = new ScriptedKli('test', []);

		\ob_start();
		$result = $kli->warn('heads up', false, null);
		\ob_end_clean();

		self::assertSame($kli, $result);
	}

	public function testWarnWithExitTerminates(): void
	{
		$kli = new ScriptedKli('test', []);

		\ob_start();

		try {
			$kli->warn('danger', false, 3);
			self::fail('KliTerminateCalledException was not thrown');
		} catch (KliTerminateCalledException $e) {
			self::assertSame(3, $e->exitCode);
		} finally {
			\ob_end_clean();
		}
	}

	// -----------------------------------------------------------------------
	// error() -- exit parameter
	// -----------------------------------------------------------------------

	public function testErrorWithNullExitReturnsInstance(): void
	{
		$kli = new ScriptedKli('test', []);

		\ob_start();
		$result = $kli->error('oops', false, null);
		\ob_end_clean();

		self::assertSame($kli, $result);
	}

	public function testErrorDefaultExitTerminatesWithCode1(): void
	{
		$kli = new ScriptedKli('test', []);

		\ob_start();

		try {
			$kli->error('oops');
			self::fail('KliTerminateCalledException was not thrown');
		} catch (KliTerminateCalledException $e) {
			self::assertSame(1, $e->exitCode);
		} finally {
			\ob_end_clean();
		}
	}

	public function testErrorWithCustomExitCode(): void
	{
		$kli = new ScriptedKli('test', []);

		\ob_start();

		try {
			$kli->error('oops', false, 2);
			self::fail('KliTerminateCalledException was not thrown');
		} catch (KliTerminateCalledException $e) {
			self::assertSame(2, $e->exitCode);
		} finally {
			\ob_end_clean();
		}
	}

	// -----------------------------------------------------------------------
	// success() -- exit parameter
	// -----------------------------------------------------------------------

	public function testSuccessWithNullExitReturnsInstance(): void
	{
		$kli = new ScriptedKli('test', []);

		\ob_start();
		$result = $kli->success('done', false);
		\ob_end_clean();

		self::assertSame($kli, $result);
	}

	public function testSuccessWithExitTerminates(): void
	{
		$kli = new ScriptedKli('test', []);

		\ob_start();

		try {
			$kli->success('done', false, 0);
			self::fail('KliTerminateCalledException was not thrown');
		} catch (KliTerminateCalledException $e) {
			self::assertSame(0, $e->exitCode);
		} finally {
			\ob_end_clean();
		}
	}

	// -----------------------------------------------------------------------
	// terminate()
	// -----------------------------------------------------------------------

	public function testTerminateWithCode(): void
	{
		$kli = new ScriptedKli('test', []);

		try {
			$kli->terminate(42);
			self::fail('KliTerminateCalledException was not thrown');
		} catch (KliTerminateCalledException $e) {
			self::assertSame(42, $e->exitCode);
		}
	}

	public function testTerminateDefaultCodeIsZero(): void
	{
		$kli = new ScriptedKli('test', []);

		try {
			$kli->terminate();
			self::fail('KliTerminateCalledException was not thrown');
		} catch (KliTerminateCalledException $e) {
			self::assertSame(0, $e->exitCode);
		}
	}

	// -----------------------------------------------------------------------
	// isInteractiveMode()
	// -----------------------------------------------------------------------

	public function testIsInteractiveModeDefaultFalse(): void
	{
		$kli = new ScriptedKli('test', []);

		self::assertFalse($kli->isInteractiveMode());
	}

	public function testIsInteractiveModeIsTrueDuringRepl(): void
	{
		$capturedMode = null;
		$kli          = new ScriptedKli('test', ['do run', 'quit'], true);
		$cmd          = $kli->command('do');
		$act          = $cmd->action('run');
		$act->handler(static function () use ($kli, &$capturedMode): void {
			$capturedMode = $kli->isInteractiveMode();
		});

		\ob_start();
		$kli->switchToInteractiveMode();
		\ob_get_clean();

		self::assertTrue($capturedMode);
	}

	// -----------------------------------------------------------------------
	// Interactive mode: error() throws KliAbortException, REPL continues
	// -----------------------------------------------------------------------

	public function testInteractiveReplContinuesAfterError(): void
	{
		// 'nope action' -> unknown command -> error() -> KliAbortException
		// (caught by execute() internally); REPL loop continues.
		// 'do run' executes successfully; 'quit' ends the REPL.
		$ran = [];
		$kli = new ScriptedKli('test', ['nope action', 'do run', 'quit'], true);
		$cmd = $kli->command('do');
		$act = $cmd->action('run');
		$act->handler(static function () use (&$ran): void {
			$ran[] = 'run';
		});

		\ob_start();
		$kli->switchToInteractiveMode();
		\ob_get_clean();

		self::assertSame(['run'], $ran);
		// 3 prompts: 'nope action', 'do run', 'quit'
		self::assertCount(3, $kli->promptLog);
	}

	private static function kliInstance(): ScriptedKli
	{
		$kli = new ScriptedKli('kli', []);

		$hello = $kli->command('hello')
			->description('Say hello to someone.')
			->handler(static function (KliAction $action, KliArgs $args) use ($kli) {
				if ('say' === $action->getName()) {
					$name = $args->get('name');
					$age  = $args->get('age');

					$kli->writeLn("Hello {$name}, you are {$age} years old.");
				}
			});

		$hello->description('Say hello to someone.');

		$say = $hello->action('say', 'Say hello to someone.');
		$say->option('name', 'n')
			->string()
			->def('John Doe');

		$say->option('age', 'a')
			->number()
			->def(18);

		return $kli;
	}
}
