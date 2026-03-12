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

use Kli\KliUtils;
use PHPUnit\Framework\TestCase;

/**
 * Class KliUtilsTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliUtilsTest extends TestCase
{
	// stringToArgv

	public function testStringToArgvSimple(): void
	{
		self::assertSame(['hello', 'world'], KliUtils::stringToArgv('hello world'));
	}

	public function testStringToArgvDoubleQuotes(): void
	{
		self::assertSame(['hello world'], KliUtils::stringToArgv('"hello world"'));
	}

	public function testStringToArgvSingleQuotes(): void
	{
		self::assertSame(['hello world'], KliUtils::stringToArgv("'hello world'"));
	}

	public function testStringToArgvMixed(): void
	{
		self::assertSame(['cmd', '--name=John Doe', '--age=25'], KliUtils::stringToArgv('cmd "--name=John Doe" --age=25'));
	}

	public function testStringToArgvEmpty(): void
	{
		self::assertSame([''], KliUtils::stringToArgv(''));
	}

	// argvToString

	public function testArgvToStringPositional(): void
	{
		$result = KliUtils::argvToString(['foo', 'bar baz']);

		self::assertStringContainsString('foo', $result);
		self::assertStringContainsString('bar baz', $result);
	}

	public function testArgvToStringNamedLong(): void
	{
		$result = KliUtils::argvToString(['name' => 'John', 'age' => '25']);

		self::assertStringContainsString('--name=', $result);
		self::assertStringContainsString('--age=', $result);
	}

	public function testArgvToStringFlag(): void
	{
		// single char key -> flag
		$result = KliUtils::argvToString(['n' => 'John']);

		self::assertStringContainsString('-n=', $result);
	}

	public function testArgvToStringBoolTrue(): void
	{
		$result = KliUtils::argvToString(['verbose' => true]);

		self::assertStringContainsString('--verbose=true', $result);
	}

	public function testArgvToStringBoolFalse(): void
	{
		$result = KliUtils::argvToString(['verbose' => false]);

		self::assertStringContainsString('--verbose=false', $result);
	}

	// wrap

	public function testWrapBasic(): void
	{
		$text   = 'Hello World';
		$result = KliUtils::wrap($text, 5);

		self::assertStringContainsString("\n", $result);
	}

	public function testWrapNormalisesCarriageReturns(): void
	{
		// \r\n (Windows) and \r (old Mac) are normalised to \n; Unix \n is preserved.
		self::assertSame("line1\nline2", KliUtils::wrap("line1\r\nline2", 80));
		self::assertSame("line1\nline2", KliUtils::wrap("line1\rline2", 80));
		self::assertStringNotContainsString("\r", KliUtils::wrap("line1\nline2", 80));
	}

	// indent

	public function testIndentAddsLeadingSpaces(): void
	{
		$result = KliUtils::indent('hello', 4);

		self::assertStringStartsWith('    ', $result);
	}

	public function testIndentCustomChar(): void
	{
		$result = KliUtils::indent('hello', 3, '-');

		self::assertStringStartsWith('---', $result);
	}

	// paddings

	public function testPaddingsLeft(): void
	{
		$result = KliUtils::paddings('hi', ['left' => 2]);

		self::assertStringStartsWith('  hi', $result);
	}

	public function testPaddingsTopBottom(): void
	{
		$result = KliUtils::paddings('hi', ['top' => 1, 'bottom' => 1]);
		$lines  = \explode(\PHP_EOL, $result);

		// top/bottom padding lines are filled with spaces to match the max line width
		self::assertEmpty(\trim($lines[0]));
		self::assertSame('hi', $lines[1]);
		self::assertEmpty(\trim($lines[2]));
	}

	// shorten

	public function testShortenUnderLimit(): void
	{
		self::assertSame('hello', KliUtils::shorten('hello', 10));
	}

	public function testShortenOverLimit(): void
	{
		$result = KliUtils::shorten('hello world', 5);

		self::assertSame('hello...', $result);
	}

	public function testShortenExactLimit(): void
	{
		self::assertSame('hello', KliUtils::shorten('hello', 5));
	}

	/**
	 * BUG: KliUtils::wrap() strips ALL newlines before word-wrapping, so intentional
	 * line breaks in the input are permanently lost. 'line1\nline2' becomes 'line1line2',
	 * merging separate paragraphs into a single line.
	 *
	 * The sister function KliUtils::paddings() correctly normalises: it replaces '\n'
	 * with PHP_EOL rather than deleting it. wrap() should do the same before handing
	 * off to wordwrap(), i.e. use preg_replace('~\r\n?~', '\n', ...) to normalise
	 * Windows/old-Mac line endings without destroying UNIX ones.
	 */
	public function testWrapPreservesLineBreaks(): void
	{
		// Explicit line break in the input -- both chunks are short, so no
		// re-wrapping is triggered. The newline must survive.
		$result = KliUtils::wrap("line1\nline2", 80);

		self::assertSame("line1\nline2", $result);
	}

	/**
	 * BUG: KliUtils::shorten() uses strlen() (byte count) and substr() (byte slice)
	 * instead of mb_strlen() and mb_substr().
	 * A 2-character string 'he' (h + e-acute U+00E9) has strlen=3.
	 * With max_length=2, the current code treats it as over-length and truncates
	 * it at byte offset 2 (splitting the multibyte char), producing garbled output,
	 * when it should return the unchanged 2-character string.
	 */
	public function testShortenWithMultibyteChars(): void
	{
		// h + e-acute (U+00E9, 2 bytes in UTF-8) = 2 characters, 3 bytes
		$str = "h\xC3\xA9"; // 'hé'

		// 2 chars should fit within max_length=2 without any truncation
		self::assertSame($str, KliUtils::shorten($str, 2));
	}

	/**
	 * BUG: KliUtils::stringToArgv() uses mb_strlen() for the loop bound but
	 * indexes characters with $command[$i] (byte indexing, not character indexing).
	 * For a string with multibyte chars, mb_strlen() returns the character count
	 * (smaller than byte count), so the loop under-counts and the last characters
	 * of the final argument are silently dropped.
	 */
	public function testStringToArgvMultibyteChars(): void
	{
		// 'hé' = 2 chars but 3 bytes; 'a hé' has mb_strlen=4 but byte len=5
		$result = KliUtils::stringToArgv("a h\xC3\xA9"); // 'a hé'

		self::assertSame(['a', "h\xC3\xA9"], $result); // 'hé' must not be truncated
	}
}
