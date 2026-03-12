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
use Kli\Types\KliTypePath;
use PHPUnit\Framework\TestCase;

/**
 * Class KliTypePathTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliTypePathTest extends TestCase
{
	private string $dir;

	private string $file;

	protected function setUp(): void
	{
		$this->dir  = \sys_get_temp_dir();
		$this->file = __FILE__;
	}

	public function testValidateExistingFile(): void
	{
		$type = new KliTypePath();

		self::assertSame(\realpath($this->file), $type->validate('opt', $this->file));
	}

	public function testValidateExistingDirectory(): void
	{
		$type = new KliTypePath();

		self::assertSame(\realpath($this->dir), $type->validate('opt', $this->dir));
	}

	public function testInvalidPathThrows(): void
	{
		$type = new KliTypePath();

		$this->expectException(KliInputException::class);
		$type->validate('opt', '/nonexistent/path/xyz');
	}

	public function testFileOnlyAcceptsFile(): void
	{
		$type = new KliTypePath();
		$type->file();

		self::assertSame(\realpath($this->file), $type->validate('opt', $this->file));
	}

	public function testFileOnlyRejectsDirectory(): void
	{
		$type = new KliTypePath();
		$type->file();

		$this->expectException(KliInputException::class);
		$type->validate('opt', $this->dir);
	}

	public function testDirOnlyAcceptsDirectory(): void
	{
		$type = new KliTypePath();
		$type->dir();

		self::assertSame(\realpath($this->dir), $type->validate('opt', $this->dir));
	}

	public function testDirOnlyRejectsFile(): void
	{
		$type = new KliTypePath();
		$type->dir();

		$this->expectException(KliInputException::class);
		$type->validate('opt', $this->file);
	}

	public function testWritableAcceptsWritablePath(): void
	{
		$type = new KliTypePath();
		$type->writable();

		// sys_get_temp_dir() is writable
		self::assertSame(\realpath($this->dir), $type->validate('opt', $this->dir));
	}

	public function testPatternFiltersResults(): void
	{
		// pattern is matched against the resolved path of an existing file
		$type = new KliTypePath();
		$type->pattern('~\.php$~');

		$result = $type->validate('opt', __FILE__);

		self::assertStringEndsWith('.php', $result);
	}

	public function testMultipleReturnsSingleItemArray(): void
	{
		// multiple() makes validate() return an array even for a single path
		$type = new KliTypePath();
		$type->multiple();

		$result = $type->validate('opt', __FILE__);

		self::assertIsArray($result);
		self::assertCount(1, $result);
		self::assertSame(\realpath(__FILE__), $result[0]);
	}

	public function testMinPathCount(): void
	{
		// min=2 with a single path should fail
		$type = new KliTypePath();
		$type->multiple()->min(2);

		$this->expectException(KliInputException::class);
		$type->validate('opt', __FILE__);
	}

	public function testMaxPathCount(): void
	{
		// max=1 with a single path present: should pass
		$type = new KliTypePath();
		$type->multiple()->max(1);

		$result = $type->validate('opt', __FILE__);

		self::assertIsArray($result);
		self::assertCount(1, $result);
	}

	public function testMinZeroThrows(): void
	{
		$type = new KliTypePath();

		$this->expectException(KliRuntimeException::class);
		$type->min(0);
	}

	public function testMaxZeroThrows(): void
	{
		$type = new KliTypePath();

		$this->expectException(KliRuntimeException::class);
		$type->max(0);
	}

	public function testMaxLessThanMinThrows(): void
	{
		$type = new KliTypePath();
		$type->min(3);

		$this->expectException(KliRuntimeException::class);
		$type->max(2);
	}

	public function testConstructorWithMinMax(): void
	{
		$type = new KliTypePath(1, 10);
		$type->multiple();

		$result = $type->validate('opt', __FILE__);

		self::assertIsArray($result);
		self::assertCount(1, $result);
	}

	public function testDefaultValue(): void
	{
		$type = new KliTypePath();
		$type->def('/tmp');

		self::assertTrue($type->hasDefault());
		self::assertSame('/tmp', $type->getDefault());
	}
}
