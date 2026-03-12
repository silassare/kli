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

namespace Kli\Tests\Types;

use Kli\Exceptions\KliInputException;
use Kli\Exceptions\KliRuntimeException;
use Kli\Types\KliTypeString;
use PHPUnit\Framework\TestCase;

/**
 * Class KliTypeStringTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliTypeStringTest extends TestCase
{
	public function testValidateReturnsString(): void
	{
		$type = new KliTypeString();

		self::assertSame('hello', $type->validate('opt', 'hello'));
	}

	public function testValidateRejectsNonString(): void
	{
		$type = new KliTypeString();

		$this->expectException(KliInputException::class);
		$type->validate('opt', 42);
	}

	public function testMinLength(): void
	{
		$type = new KliTypeString();
		$type->min(3);

		self::assertSame('abc', $type->validate('opt', 'abc'));

		$this->expectException(KliInputException::class);
		$type->validate('opt', 'ab');
	}

	public function testMaxLength(): void
	{
		$type = new KliTypeString();
		$type->max(5);

		self::assertSame('hello', $type->validate('opt', 'hello'));

		$this->expectException(KliInputException::class);
		$type->validate('opt', 'toolong');
	}

	public function testMinMaxBothSet(): void
	{
		$type = new KliTypeString();
		$type->min(2)->max(4);

		self::assertSame('hi', $type->validate('opt', 'hi'));
		self::assertSame('test', $type->validate('opt', 'test'));
	}

	public function testMinGreaterThanMaxThrows(): void
	{
		$type = new KliTypeString();
		$type->min(5);

		$this->expectException(KliRuntimeException::class);
		$type->max(2);
	}

	public function testMinZeroThrows(): void
	{
		$type = new KliTypeString();

		$this->expectException(KliRuntimeException::class);
		$type->min(0);
	}

	public function testMaxZeroThrows(): void
	{
		$type = new KliTypeString();

		$this->expectException(KliRuntimeException::class);
		$type->max(0);
	}

	public function testPattern(): void
	{
		$type = new KliTypeString();
		$type->pattern('~^\d+$~');

		self::assertSame('123', $type->validate('opt', '123'));

		$this->expectException(KliInputException::class);
		$type->validate('opt', 'abc');
	}

	public function testCustomValidator(): void
	{
		$type = new KliTypeString();
		$type->validator(static fn (string $v) => $v === \strtoupper($v));

		self::assertSame('HELLO', $type->validate('opt', 'HELLO'));

		$this->expectException(KliInputException::class);
		$type->validate('opt', 'hello');
	}

	public function testDefaultValue(): void
	{
		$type = new KliTypeString();
		$type->def('default');

		self::assertTrue($type->hasDefault());
		self::assertSame('default', $type->getDefault());
	}

	public function testConstructorWithMinMax(): void
	{
		$type = new KliTypeString(2, 10);

		self::assertSame('ok', $type->validate('opt', 'ok'));

		$this->expectException(KliInputException::class);
		$type->validate('opt', 'x');
	}

	public function testCustomErrorMessage(): void
	{
		$type = new KliTypeString();
		$type->min(5, 'too short!');

		try {
			$type->validate('opt', 'hi');
			self::fail('Expected KliInputException');
		} catch (KliInputException $e) {
			self::assertStringContainsString('too short!', $e->getMessage());
		}
	}

	/**
	 * BUG: KliTypeString::validate() uses strlen() (byte count) instead of
	 * mb_strlen() (character count) for min-length checks.
	 * A 2-character multibyte string like 'hé' has strlen=3.
	 * With min(3), the check 'strlen < 3' is '3 < 3' = false, so the
	 * 2-character string incorrectly passes the 3-character minimum.
	 */
	public function testMinLengthCountsCharsNotBytes(): void
	{
		$type = new KliTypeString();
		$type->min(3);

		// 'hé' is 2 chars (3 bytes) and should FAIL min=3 (too short)
		$this->expectException(KliInputException::class);
		$type->validate('opt', "h\xC3\xA9"); // 'hé'
	}

	/**
	 * BUG: KliTypeString::validate() uses strlen() (byte count) instead of
	 * mb_strlen() (character count) for max-length checks.
	 * A 2-character multibyte string like 'hé' has strlen=3.
	 * With max(2), the check 'strlen > 2' is '3 > 2' = true, so the
	 * 2-character string is incorrectly rejected by a max=2 constraint.
	 */
	public function testMaxLengthCountsCharsNotBytes(): void
	{
		$type = new KliTypeString();
		$type->max(2);

		// 'hé' is 2 chars (3 bytes) and should PASS max=2 (exactly at limit)
		$result = $type->validate('opt', "h\xC3\xA9"); // 'hé'

		self::assertSame("h\xC3\xA9", $result);
	}
}
