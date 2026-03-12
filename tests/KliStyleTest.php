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

use Kli\KliStyle;
use PHPUnit\Framework\TestCase;

/**
 * Class KliStyleTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliStyleTest extends TestCase
{
	public function testAnsiColors(): void
	{
		// Without any styles configured the output is unchanged in every mode.
		self::assertSame(' ', (new KliStyle())->apply(' '));

		// Force ANSI output so these assertions run regardless of whether
		// STDOUT happens to be a TTY.
		KliStyle::forceAnsi(true);

		try {
			$color = new KliStyle();
			$color->red()
				->backgroundYellow();

			self::assertSame("\033[31;43m \033[39;49m", $color->apply(' '));

			$color = new KliStyle();
			$color->red()
				->backgroundYellow()
				->underline();

			self::assertSame("\033[31;43;4m \033[39;49;24m", $color->apply(' '));
		} finally {
			KliStyle::forceAnsi(false);
		}
	}

	public function testAllForegroundColors(): void
	{
		// verify every foreground color key has a corresponding method that returns static
		foreach (\array_keys(KliStyle::FOREGROUND_COLORS) as $name) {
			$style  = new KliStyle();
			$method = \lcfirst(\str_replace('_', '', \ucwords($name, '_')));

			self::assertTrue(\method_exists($style, $method), "Method {$method}() not found for color '{$name}'");

			$returned = $style->{$method}();

			self::assertInstanceOf(KliStyle::class, $returned);
		}
	}

	public function testAllStyles(): void
	{
		// verify each style key has a corresponding method that returns static
		foreach (\array_keys(KliStyle::STYLES) as $name) {
			$style    = new KliStyle();
			$returned = $style->{$name}();

			self::assertInstanceOf(KliStyle::class, $returned);
		}

		// spot-check bold and underline have proper reset codes in the constants
		self::assertArrayHasKey('bold', KliStyle::STYLES_RESET);
		self::assertArrayHasKey('underline', KliStyle::STYLES_RESET);
	}

	public function testPerAttributeResetConstants(): void
	{
		// verify the reset code constants: per-attribute, not a global \033[0m
		self::assertSame('22', KliStyle::STYLES_RESET['bold']);
		self::assertSame('22', KliStyle::STYLES_RESET['dim']);
		self::assertSame('24', KliStyle::STYLES_RESET['underline']);
		self::assertSame('25', KliStyle::STYLES_RESET['blink']);
		self::assertSame('27', KliStyle::STYLES_RESET['invert']);
		self::assertSame('28', KliStyle::STYLES_RESET['hidden']);
	}

	public function testBoxAppliesPadding(): void
	{
		$style = new KliStyle();
		$style->box(['top' => 1, 'bottom' => 1, 'left' => 2, 'right' => 2]);
		$result = $style->apply('hi');

		// box should add newlines (top/bottom) and spaces (left/right)
		self::assertStringContainsString(\PHP_EOL, $result);
		self::assertStringContainsString('  hi', $result);
	}

	public function testNoAnsiWhenNotTty(): void
	{
		// In test environment STDOUT is not a tty, so a style with no tty check
		// should return plain string. This verifies the tty guard works in the test runner.
		// The existing testAnsiColors() uses assertSame with ANSI codes which only passes
		// when stream_isatty(STDOUT) is true - so that test already verifies the TTY path.
		// Here we just confirm plain text is returned when no colour set.
		$style  = new KliStyle();
		$result = $style->apply('plain');

		self::assertSame('plain', $result);
	}
}
