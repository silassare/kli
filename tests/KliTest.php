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
use Kli\Kli;
use Kli\KliAction;
use Kli\KliArgs;
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
		$kli = self::kliInstance();

		$error = $kli->style()
			->red()
			->apply('✖ unknown command: hey');

		$this->expectOutputString(\PHP_EOL . $error . \PHP_EOL);

		$kli->execute(['kli', 'hey', 'talk']);
	}

	/**
	 * @throws KliException
	 */
	public function testUnknownAction(): void
	{
		$kli = self::kliInstance();

		$error = $kli->style()
			->red()
			->apply('✖ hello: unknown action "talk"');

		$this->expectOutputString(\PHP_EOL . $error . \PHP_EOL);

		$kli->execute(['kli', 'hello', 'talk']);
	}

	/**
	 * @throws KliException
	 */
	public function testHelp(): void
	{
		$kli = self::kliInstance();

		$path = __DIR__ . '/snapshots/help.txt';

		\ob_start();

		$kli->execute(['kli', '--help']);

		$content = \ob_get_clean();

		TestUtils::ensureSnapshotFile($path, $content);
		self::assertStringEqualsFile($path, $content);
	}

	private static function kliInstance(): Kli
	{
		$kli = Kli::new('kli');

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
