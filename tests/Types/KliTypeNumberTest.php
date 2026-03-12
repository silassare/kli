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
use Kli\Types\KliTypeNumber;
use PHPUnit\Framework\TestCase;

/**
 * Class KliTypeNumberTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliTypeNumberTest extends TestCase
{
	public function testValidateInteger(): void
	{
		$type = new KliTypeNumber();

		self::assertSame(42, $type->validate('opt', '42'));
		self::assertSame(0, $type->validate('opt', '0'));
		self::assertSame(-5, $type->validate('opt', '-5'));
	}

	public function testValidateFloat(): void
	{
		$type = new KliTypeNumber();

		self::assertSame(3.14, $type->validate('opt', '3.14'));
		self::assertSame(-1.5, $type->validate('opt', '-1.5'));
	}

	public function testValidateRejectsNonNumeric(): void
	{
		$type = new KliTypeNumber();

		$this->expectException(KliInputException::class);
		$type->validate('opt', 'abc');
	}

	public function testMinConstraint(): void
	{
		$type = new KliTypeNumber();
		$type->min(10.0);

		self::assertSame(10, $type->validate('opt', '10'));

		$this->expectException(KliInputException::class);
		$type->validate('opt', '9');
	}

	public function testMaxConstraint(): void
	{
		$type = new KliTypeNumber();
		$type->max(100.0);

		self::assertSame(100, $type->validate('opt', '100'));

		$this->expectException(KliInputException::class);
		$type->validate('opt', '101');
	}

	public function testMinMaxBothSet(): void
	{
		$type = new KliTypeNumber();
		$type->min(1.0)->max(10.0);

		self::assertSame(5, $type->validate('opt', '5'));
	}

	public function testMinGreaterThanMaxThrows(): void
	{
		$type = new KliTypeNumber();
		$type->min(10.0);

		$this->expectException(KliRuntimeException::class);
		$type->max(5.0);
	}

	public function testIntegerMode(): void
	{
		$type = new KliTypeNumber();
		$type->integer();

		self::assertSame(7, $type->validate('opt', '7'));

		$this->expectException(KliInputException::class);
		$type->validate('opt', '7.5');
	}

	public function testConstructorWithMinMax(): void
	{
		$type = new KliTypeNumber(0.0, 100.0);

		self::assertSame(50, $type->validate('opt', '50'));

		$this->expectException(KliInputException::class);
		$type->validate('opt', '150');
	}

	public function testDefaultValue(): void
	{
		$type = new KliTypeNumber();
		$type->def(42);

		self::assertTrue($type->hasDefault());
		self::assertSame(42, $type->getDefault());
	}

	public function testCustomErrorMessages(): void
	{
		$type = new KliTypeNumber();
		$type->min(10.0, 'value too small');

		try {
			$type->validate('opt', '1');
			self::fail('Expected KliInputException');
		} catch (KliInputException $e) {
			self::assertStringContainsString('value too small', $e->getMessage());
		}
	}
}
