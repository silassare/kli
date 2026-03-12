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
use Kli\KliOption;
use Kli\Types\KliTypeBool;
use Kli\Types\KliTypeNumber;
use Kli\Types\KliTypePath;
use Kli\Types\KliTypeString;
use PHPUnit\Framework\TestCase;

/**
 * Class KliOptionTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliOptionTest extends TestCase
{
	public function testNameAutoAlias(): void
	{
		$opt = new KliOption('name');

		self::assertContains('name', $opt->getAliases());
		self::assertNull($opt->getFlag());
	}

	public function testSingleCharNameAutoFlag(): void
	{
		$opt = new KliOption('n');

		self::assertSame('n', $opt->getFlag());
		self::assertEmpty($opt->getAliases());
	}

	public function testInvalidNameThrows(): void
	{
		$this->expectException(KliRuntimeException::class);
		new KliOption('!invalid');
	}

	public function testSetFlag(): void
	{
		$opt = new KliOption('name');
		$opt->flag('x');

		self::assertSame('x', $opt->getFlag());
	}

	public function testInvalidFlagThrows(): void
	{
		$opt = new KliOption('name');

		$this->expectException(KliRuntimeException::class);
		$opt->flag('ab'); // flags must be exactly 1 char
	}

	public function testAddAlias(): void
	{
		$opt = new KliOption('name');
		$opt->alias('nm');

		self::assertContains('nm', $opt->getAliases());
	}

	public function testInvalidAliasThrows(): void
	{
		$opt = new KliOption('name');

		$this->expectException(KliRuntimeException::class);
		$opt->alias('x'); // aliases must be 2+ chars
	}

	public function testTypeString(): void
	{
		$opt  = new KliOption('name');
		$type = $opt->string();

		self::assertInstanceOf(KliTypeString::class, $type);
		self::assertInstanceOf(KliTypeString::class, $opt->getType());
	}

	public function testTypeBool(): void
	{
		$opt  = new KliOption('flag');
		$type = $opt->bool();

		self::assertInstanceOf(KliTypeBool::class, $type);
	}

	public function testTypeNumber(): void
	{
		$opt  = new KliOption('count');
		$type = $opt->number();

		self::assertInstanceOf(KliTypeNumber::class, $type);
	}

	public function testTypePath(): void
	{
		$opt  = new KliOption('path');
		$type = $opt->path();

		self::assertInstanceOf(KliTypePath::class, $type);
	}

	public function testDescription(): void
	{
		$opt = new KliOption('name');
		$opt->description('The name option');

		self::assertSame('The name option', $opt->getDescription());
	}

	public function testRequired(): void
	{
		$opt = new KliOption('name');

		self::assertFalse($opt->isRequired());
		$opt->required();
		self::assertTrue($opt->isRequired());
	}

	public function testPrompt(): void
	{
		$opt = new KliOption('name');

		self::assertFalse($opt->promptEnabled());
		$opt->prompt(true, 'Enter your name');
		self::assertTrue($opt->promptEnabled());
		self::assertSame('Enter your name', $opt->getPrompt());
	}

	public function testPromptDefaultMessage(): void
	{
		$opt = new KliOption('myopt');
		$opt->prompt();

		self::assertStringContainsString('myopt', $opt->getPrompt());
	}

	public function testOffsets(): void
	{
		$opt = new KliOption('name');
		$opt->offsets(0);

		self::assertSame([0, 0], $opt->getOffsets());
	}

	public function testOffsetSinglePosition(): void
	{
		$opt = new KliOption('name');
		$opt->offsets(1);

		self::assertSame([1, 1], $opt->getOffsets());
	}

	public function testNegativeOffsetThrows(): void
	{
		$opt = new KliOption('name');

		$this->expectException(KliRuntimeException::class);
		$opt->offsets(-1);
	}

	public function testLockedOptionOffsetThrows(): void
	{
		$opt = new KliOption('name');
		$opt->lock();

		$this->expectException(KliRuntimeException::class);
		$opt->offsets(0);
	}

	public function testToStringContainsAlias(): void
	{
		$opt = new KliOption('name');
		$opt->description('A name option');

		$str = (string) $opt;

		self::assertStringContainsString('--name', $str);
		self::assertStringContainsString('A name option', $str);
	}

	public function testToStringWithFlag(): void
	{
		$opt = new KliOption('name');
		$opt->flag('n');

		$str = (string) $opt;

		self::assertStringContainsString('-n', $str);
	}

	// duplicate alias is silently ignored
	public function testDuplicateAliasIgnored(): void
	{
		$opt = new KliOption('myname');
		$opt->alias('myname'); // already added automatically

		self::assertCount(1, $opt->getAliases());
	}

	/**
	 * BUG: KliOption::offsets(int $at, ?int $to) types $to as ?int but the docblock
	 * says "$to could be set to infinity (INF)". The validation
	 * condition '!is_infinite($to)' is ALWAYS true for any int (since integers are
	 * never infinite), so any non-null $to immediately throws KliRuntimeException.
	 * Range offsets like offsets(0, 2) are therefore completely broken.
	 */
	public function testOffsetsWithRangeDoesNotThrow(): void
	{
		$opt = new KliOption('files');
		$opt->offsets(0, 2);

		self::assertSame([0, 2], $opt->getOffsets());
	}
}
