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
use Kli\Types\KliTypeBool;
use PHPUnit\Framework\TestCase;

/**
 * Class KliTypeBoolTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliTypeBoolTest extends TestCase
{
	public function testValidateTrueLiterals(): void
	{
		$type = new KliTypeBool();

		self::assertTrue($type->validate('opt', true));
		self::assertTrue($type->validate('opt', 'y'));
		self::assertTrue($type->validate('opt', 'yes'));
		self::assertTrue($type->validate('opt', 'Y'));
		self::assertTrue($type->validate('opt', 'YES'));
	}

	public function testValidateFalseLiterals(): void
	{
		$type = new KliTypeBool();

		self::assertFalse($type->validate('opt', false));
		self::assertFalse($type->validate('opt', 'n'));
		self::assertFalse($type->validate('opt', 'no'));
		self::assertFalse($type->validate('opt', 'N'));
		self::assertFalse($type->validate('opt', 'NO'));
	}

	public function testNonStrictAcceptsExtended(): void
	{
		$type = new KliTypeBool(false);

		self::assertTrue($type->validate('opt', '1'));
		self::assertTrue($type->validate('opt', 1));
		self::assertTrue($type->validate('opt', 'true'));
		self::assertFalse($type->validate('opt', '0'));
		self::assertFalse($type->validate('opt', 0));
		self::assertFalse($type->validate('opt', 'false'));
	}

	public function testStrictRejectsExtended(): void
	{
		$type = new KliTypeBool(true);

		$this->expectException(KliInputException::class);
		$type->validate('opt', '1');
	}

	public function testStrictRejectsNumericZero(): void
	{
		$type = new KliTypeBool(true);

		$this->expectException(KliInputException::class);
		$type->validate('opt', 0);
	}

	public function testInvalidValueThrows(): void
	{
		$type = new KliTypeBool();

		$this->expectException(KliInputException::class);
		$type->validate('opt', 'maybe');
	}

	public function testCustomErrorMessage(): void
	{
		$type = new KliTypeBool(false, 'not a valid bool!');

		try {
			$type->validate('opt', 'maybe');
			self::fail('Expected KliInputException');
		} catch (KliInputException $e) {
			self::assertStringContainsString('not a valid bool!', $e->getMessage());
		}
	}

	public function testDefaultValue(): void
	{
		$type = new KliTypeBool();
		$type->def(true);

		self::assertTrue($type->hasDefault());
		self::assertTrue($type->getDefault());
	}

	/**
	 * BUG: KliTypeBool::validate() uses sprintf($format, $value, $opt_name) but the
	 * format string 'option "%s" require a boolean.' only has one placeholder.
	 * The invalid $value (not $opt_name) fills the placeholder, so the error message
	 * says 'option "badval" require a boolean.' instead of
	 * 'option "myoption" require a boolean.'.
	 */
	public function testErrorMessageMentionsOptionName(): void
	{
		$type = new KliTypeBool();

		try {
			$type->validate('myoption', 'badval');
			self::fail('Expected KliInputException');
		} catch (KliInputException $e) {
			self::assertStringContainsString('myoption', $e->getMessage());
		}
	}
}
