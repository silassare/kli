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

use Kli\KliColor;
use PHPUnit\Framework\TestCase;

/**
 * Class KliColorTest.
 */
class KliColorTest extends TestCase
{
	public function testAnsiColors(): void
	{
		$color = new KliColor();

		self::assertSame(' ', $color->string(' '));

		$color = new KliColor();
		$color->red()
			  ->backgroundYellow();

		self::assertSame("\033[31;43m \033[39;49m", $color->string(' '));

		$color = new KliColor();
		$color->red()
			  ->backgroundYellow()
			  ->underline();

		self::assertSame("\033[31;43;4m \033[39;49;24m", $color->string(' '));
	}
}
