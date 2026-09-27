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
use Kli\KliUtils;
use Kli\Table\Interfaces\KliTableCellFormatterInterface;
use Kli\Table\KliTable;
use Kli\Table\KliTableFormatter;
use Kli\Table\KliTableHeader;
use PHPUnit\Framework\TestCase;

/**
 * Class KliTableTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliTableTest extends TestCase
{
	public function testTable(): void
	{
		// Always generate and validate the plain (no-ANSI) snapshots.
		$this->renderAllSnapshots('', false);

		// Always generate and validate the ANSI snapshots (suffix .tty),
		// forcing ANSI codes regardless of whether STDOUT is a real TTY.
		$this->renderAllSnapshots('.tty', true);
	}

	/**
	 * A column is as wide as its widest text in terminal columns, not in bytes:
	 * 'é' is 1 column but 2 bytes in UTF-8.
	 */
	public function testTableColumnWidthCountsCharsNotBytes(): void
	{
		$table = (new KliTable())->setMaxWidth(null);
		$table->addHeader('X', 'x');
		$table->addRow(['x' => 'é']);

		$lines = \explode(\PHP_EOL, $table->render());

		// Line layout: top border [0], header row [1], mid border [2], data row [3], bottom [4]
		self::assertSame('║ é ║', $lines[3]);
	}

	public function testEveryLineOfATableIsAsWideAsItsBorders(): void
	{
		$table = (new KliTable())->setMaxWidth(null);
		$table->addHeader('Check', 'name');
		$table->addHeader('Status', 'status')->alignCenter();
		$table->addHeader('Detail', 'detail')->alignRight();
		$table->addRows([
			['name' => 'wide', 'status' => 'OK', 'detail' => '日本語テキスト'],
			['name' => 'emoji', 'status' => 'OK', 'detail' => 'ok ✅ done'],
			['name' => 'accents', 'status' => 'OK', 'detail' => 'Émile é à'],
			['name' => 'ansi', 'status' => "\033[31mFAIL\033[0m", 'detail' => 'a colored value'],
			['name' => 'tab', 'status' => 'OK', 'detail' => "a\tb"],
		]);

		$lines = \explode(\PHP_EOL, $table->render());

		self::assertSame([38], \array_values(\array_unique(\array_map(KliUtils::displayWidth(...), $lines))));
		self::assertSame('║ ansi    │  ' . "\033[31mFAIL\033[0m" . '  │ a colored value ║', $lines[9]);
		self::assertSame('║ tab     │   OK   │          a    b ║', $lines[11]);
	}

	public function testACellOnSeveralLinesMakesItsRowTaller(): void
	{
		$table = (new KliTable())->setMaxWidth(null);
		$table->addHeader('Check', 'name');
		$table->addHeader("Detail\n(why)", 'detail');
		$table->addRow(['name' => 'cache', 'detail' => "first line\r\nsecond, longer line\rthird"]);

		self::assertSame(
			[
				'╔═══════╤═════════════════════╗',
				'║ Check │ Detail              ║',
				'║       │ (why)               ║',
				'╟───────┼─────────────────────╢',
				'║ cache │ first line          ║',
				'║       │ second, longer line ║',
				'║       │ third               ║',
				'╚═══════╧═════════════════════╝',
			],
			\explode(\PHP_EOL, $table->render())
		);
	}

	public function testATableWiderThanItsMaximumWrapsItsWidestColumns(): void
	{
		$table = (new KliTable())->setMaxWidth(40);
		$table->addHeader('Check', 'name');
		$table->addHeader('Id', 'id')->alignRight()->setWidth(5);
		$table->addHeader('Detail', 'detail');
		$table->addRows([
			[
				'name'   => 'ext-intl',
				'id'     => 'abcdefgh',
				'detail' => 'The intl extension is not loaded: categories never match.',
			],
			['name' => 'url', 'id' => 1, 'detail' => 'see https://example.com/a/long/path'],
			['name' => 'wide', 'id' => 2, 'detail' => '日本語テキスト日本語テキスト'],
			['name' => 'ansi', 'id' => 3, 'detail' => "\033[32mgreen text that has to be wrapped here\033[0m"],
		]);

		self::assertSame(
			[
				'╔══════════╤═══════╤═══════════════════╗',
				'║ Check    │    Id │ Detail            ║',
				'╟──────────┼───────┼───────────────────╢',
				'║ ext-intl │ abcd… │ The intl          ║',
				'║          │       │ extension is not  ║',
				'║          │       │ loaded:           ║',
				'║          │       │ categories never  ║',
				'║          │       │ match.            ║',
				'╟──────────┼───────┼───────────────────╢',
				'║ url      │     1 │ see               ║',
				'║          │       │ https://example.c ║',
				'║          │       │ om/a/long/path    ║',
				'╟──────────┼───────┼───────────────────╢',
				'║ wide     │     2 │ 日本語テキスト日  ║',
				'║          │       │ 本語テキスト      ║',
				'╟──────────┼───────┼───────────────────╢',
				'║ ansi     │     3 │ green text that   ║',
				'║          │       │ has to be wrapped ║',
				'║          │       │ here              ║',
				'╚══════════╧═══════╧═══════════════════╝',
			],
			\explode(\PHP_EOL, $table->render())
		);
	}

	public function testATableThatCannotFitIsAsNarrowAsItGets(): void
	{
		$table = (new KliTable())->setMaxWidth(10);
		$table->addHeader('Name', 'name');
		$table->addHeader('Id', 'id')->setWidth(8);
		$table->addRow(['name' => 'a long name', 'id' => 1]);

		$lines = \explode(\PHP_EOL, $table->render());

		// Each column keeps MIN_CELL_WIDTH, and a fixed one its width: 1 + 7 + 1 + 10 + 1.
		self::assertSame(20, KliUtils::displayWidth($lines[0]));
		self::assertSame('║ a     │ 1        ║', $lines[3]);
		self::assertSame('║ long  │          ║', $lines[4]);
		self::assertSame('║ name  │          ║', $lines[5]);
	}

	public function testTheTerminalWidthIsTheDefaultMaximum(): void
	{
		$previous = \getenv('COLUMNS');

		\putenv('COLUMNS=20');

		try {
			$table = new KliTable();
			$table->addHeader('Detail', 'detail');
			$table->addRow(['detail' => 'a text longer than twenty columns']);

			self::assertSame(20, \max(\array_map(KliUtils::displayWidth(...), \explode(\PHP_EOL, $table->render()))));
			self::assertSame(20, KliUtils::terminalWidth());
		} finally {
			\putenv(false === $previous ? 'COLUMNS' : 'COLUMNS=' . $previous);
		}
	}

	/**
	 * Builds a fresh table with all headers, formatters and rows configured.
	 *
	 * @return array{0: KliTable, 1: KliTableHeader}
	 */
	private function buildTable(): array
	{
		// No width limit: the snapshots must not depend on the terminal the tests run in.
		$table = (new KliTable())->setMaxWidth(null);

		$table->addHeader('ID', 'id')
			->alignCenter();
		$table->addHeader('Name', 'name')
			->alignRight();
		$phone_header = $table->addHeader('Phone', 'phone')
			->alignCenter();
		$table->addHeader('Date', 'date')
			->setCellFormatter(new class implements KliTableCellFormatterInterface {
				/**
				 * {@inheritDoc}
				 */
				public function format($value, KliTableHeader $header, array $row): string
				{
					return \date('jS F Y, g:i a', $value);
				}

				/**
				 * {@inheritDoc}
				 */
				public function getStyle($value, KliTableHeader $header, array $row): ?KliStyle
				{
					return $row['color'] ? (new KliStyle())->yellow() : null;
				}
			});

		$table->addHeader('Color', 'color')
			->setCellFormatter(KliTableFormatter::bool())
			->alignCenter();

		$time = \mktime(13, 8, 35, 1, 1, 2023);
		$table->addRows([
			[
				'id'    => 1,
				'name'  => 'Kpèdétin',
				'phone' => '+229 01 00 01 02 03',
				'date'  => $time - 54500,
				'color' => false,
			],
			[
				'id'    => 2,
				'name'  => 'Yemboka',
				'phone' => '+229 01 00 02 03 04 05',
				'date'  => $time,
				'color' => false,
			],
			[
				'id'    => 3,
				'name'  => 'Iméla',
				'phone' => '+229 01 00 03 04 05 06',
				'date'  => $time + 3800,
				'color' => true,
			],
			[
				'id'    => 4,
				'name'  => 'Tehillah',
				'phone' => '+229 01 00 04 05 06 07',
				'date'  => $time - 3800,
				'color' => true,
			],
		]);

		return [$table, $phone_header];
	}

	/**
	 * Renders all four table-snapshot variants and asserts them against the golden files.
	 *
	 * @param string $suffix    Appended before ".txt" -- '' for plain text, '.tty' for ANSI.
	 * @param bool   $forceAnsi when true, forces ANSI codes via KliStyle::forceAnsi()
	 */
	private function renderAllSnapshots(string $suffix, bool $forceAnsi): void
	{
		$dir = __DIR__ . '/snapshots';

		KliStyle::forceAnsi($forceAnsi);
		KliStyle::disableAnsi(!$forceAnsi);

		try {
			[$table, $phone_header] = $this->buildTable();

			$content = $table->render();
			TestUtils::ensureSnapshotFile($dir . '/table' . $suffix . '.txt', $content);
			self::assertStringEqualsFile($dir . '/table' . $suffix . '.txt', $content);

			$table->borderStyle()
				->green();
			$content = $table->render();
			TestUtils::ensureSnapshotFile($dir . '/table.colored' . $suffix . '.txt', $content);
			self::assertStringEqualsFile($dir . '/table.colored' . $suffix . '.txt', $content);

			$table->borderStyle()
				->yellow();
			$phone_header->setWidth(15);
			$content = $table->render();
			TestUtils::ensureSnapshotFile($dir . '/table.fixed.width' . $suffix . '.txt', $content);
			self::assertStringEqualsFile($dir . '/table.fixed.width' . $suffix . '.txt', $content);

			$table->setBorderChars([
				'top'          => '+',
				'top-mid'      => '+',
				'top-left'     => '+',
				'top-right'    => '+',
				'bottom'       => '-',
				'bottom-mid'   => '+',
				'bottom-left'  => '+',
				'bottom-right' => '+',
				'left'         => '|',
				'left-mid'     => '+',
				'mid'          => '-',
				'mid-mid'      => '+',
				'right'        => '|',
				'right-mid'    => '+',
				'middle'       => '|',
			]);

			$table->borderStyle()
				->red();
			$phone_header->setWidth(null);
			$content = $table->render();
			TestUtils::ensureSnapshotFile($dir . '/table.custom.border' . $suffix . '.txt', $content);
			self::assertStringEqualsFile($dir . '/table.custom.border' . $suffix . '.txt', $content);
		} finally {
			KliStyle::forceAnsi(false);
			KliStyle::disableAnsi(false);
		}
	}
}
