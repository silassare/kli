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

use Kli\Exceptions\KliRuntimeException;
use Kli\KliAction;
use Kli\KliArgs;
use Kli\KliOption;
use PHPUnit\Framework\TestCase;

/**
 * Class KliActionTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliActionTest extends TestCase
{
	public function testValidName(): void
	{
		$action = new KliAction('create');

		self::assertSame('create', $action->getName());
	}

	public function testColonInName(): void
	{
		$action = new KliAction('create:user');

		self::assertSame('create:user', $action->getName());
	}

	public function testInvalidNameThrows(): void
	{
		$this->expectException(KliRuntimeException::class);
		new KliAction('x'); // must be 2+ chars matching the regex
	}

	public function testDescription(): void
	{
		$action = new KliAction('create');
		$action->description('Creates something');

		self::assertSame('Creates something', $action->getDescription());
	}

	public function testAddOptionAndHasOption(): void
	{
		$action = new KliAction('create');
		$opt    = new KliOption('name');
		$action->addOption($opt);

		self::assertTrue($action->hasOption('name'));
		self::assertTrue($action->hasOption('name')); // by alias auto-added
	}

	public function testGetOptionByAlias(): void
	{
		$action = new KliAction('create');
		$opt    = new KliOption('name');
		$action->addOption($opt);

		// 'name' itself is auto-registered as alias
		$resolved = $action->getOption('name');

		self::assertSame('name', $resolved->getName());
	}

	public function testGetOptionByFlag(): void
	{
		$action = new KliAction('create');
		$opt    = new KliOption('name');
		$opt->flag('n');
		$action->addOption($opt);

		$resolved = $action->getOption('n');

		self::assertSame('name', $resolved->getName());
	}

	public function testGetUnknownOptionThrows(): void
	{
		$action = new KliAction('create');

		$this->expectException(KliRuntimeException::class);
		$action->getOption('unknown');
	}

	public function testDuplicateOptionNameThrows(): void
	{
		$action = new KliAction('create');
		$opt1   = new KliOption('name');
		$action->addOption($opt1);

		$opt2 = new KliOption('name');

		$this->expectException(KliRuntimeException::class);
		$action->addOption($opt2);
	}

	public function testDuplicateFlagThrows(): void
	{
		$action = new KliAction('create');
		$opt1   = new KliOption('first');
		$opt1->flag('x');
		$action->addOption($opt1);

		$opt2 = new KliOption('second');
		$opt2->flag('x');

		$this->expectException(KliRuntimeException::class);
		$action->addOption($opt2);
	}

	public function testDuplicateAliasThrows(): void
	{
		$action = new KliAction('create');
		$opt1   = new KliOption('name');
		$action->addOption($opt1);

		// opt2 will auto-register 'other' as alias, but also try to register 'name' via explicit alias
		$opt2 = new KliOption('other');
		$opt2->alias('name');

		$this->expectException(KliRuntimeException::class);
		$action->addOption($opt2);
	}

	public function testOverlappingOffsetsThrow(): void
	{
		// offsets() only accepts a single index; two options cannot claim the same index
		$action = new KliAction('create');
		$opt1   = new KliOption('first');
		$opt1->offsets(0);
		$action->addOption($opt1);

		$opt2 = new KliOption('second');
		$opt2->offsets(0); // same offset as first

		$this->expectException(KliRuntimeException::class);
		$action->addOption($opt2);
	}

	public function testNonOverlappingOffsetsOk(): void
	{
		$action = new KliAction('create');
		$opt1   = new KliOption('first');
		$opt1->offsets(0);
		$action->addOption($opt1);

		$opt2 = new KliOption('second');
		$opt2->offsets(1);
		$action->addOption($opt2);

		self::assertTrue($action->hasOption('first'));
		self::assertTrue($action->hasOption('second'));
	}

	public function testOptionIsLockedAfterAdd(): void
	{
		$action = new KliAction('create');
		$opt    = new KliOption('name');
		$action->addOption($opt);

		$this->expectException(KliRuntimeException::class);
		$opt->offsets(0); // should throw because it's locked
	}

	public function testHandlerAndGetHandler(): void
	{
		$action  = new KliAction('create');
		$invoked = false;

		$action->handler(static function (KliArgs $args) use (&$invoked): void {
			$invoked = true;
		});

		self::assertNotNull($action->getHandler());

		($action->getHandler())(new KliArgs($action, [], []));

		self::assertTrue($invoked);
	}

	public function testGetHandlerNullWhenNotSet(): void
	{
		$action = new KliAction('create');

		self::assertNull($action->getHandler());
	}

	public function testGetOptions(): void
	{
		$action = new KliAction('create');
		$opt    = new KliOption('name');
		$action->addOption($opt);

		self::assertCount(1, $action->getOptions());
	}

	public function testConvenienceOptionFactory(): void
	{
		$action = new KliAction('create');
		$opt    = $action->option('name', 'n');

		self::assertInstanceOf(KliOption::class, $opt);
		self::assertTrue($action->hasOption('name'));
		self::assertTrue($action->hasOption('n'));
	}

	public function testToString(): void
	{
		$action = new KliAction('create');
		$action->description('Create something');
		$action->option('name', 'n');

		$str = (string) $action;

		self::assertStringContainsString('create', $str);
		self::assertStringContainsString('Create something', $str);
	}
}
