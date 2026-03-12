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
	 * BUG: KliTable::render() uses strlen() (byte count) to compute max cell width
	 * and header label width, but KliTable::renderCell() uses mb_strlen() (char count)
	 * when calculating padding. For multibyte characters the byte count is larger than
	 * the char count, so the column becomes wider than it needs to be and every cell
	 * in that column gets one extra trailing space per additional byte beyond the char.
	 *
	 * Example: 'é' is 1 char but 2 bytes.
	 *   strlen('é')    = 2  -> column width = max(2, label) + MIN_CELL_PADDING
	 *   mb_strlen('é') = 1  -> padding in renderCell = (column_width) - 1  (over-padded)
	 *
	 * Fix: replace the two strlen() calls in render() with mb_strlen().
	 */
	public function testTableColumnWidthCountsCharsNotBytes(): void
	{
		$table = new KliTable();
		$table->addHeader('X', 'x'); // label 'X': 1 char, 1 byte
		$table->addRow(['x' => 'é']); // value 'é': 1 char, 2 bytes in UTF-8

		$rendered = $table->render();
		$lines    = \explode(\PHP_EOL, $rendered);
		// Line layout: top border [0], header row [1], mid border [2], data row [3], bottom [4]
		$data_row = $lines[3];

		// Column must be max(1 char, 1 char) + 2 padding = 3 chars wide.
		// With left-align (default) the cell is 'é  ' (é + 2 trailing spaces).
		// With the strlen bug the column would be 4 chars wide: 'é   ' (3 trailing spaces).
		self::assertStringContainsString('║é  ║', $data_row);
	}

	/**
	 * Builds a fresh table with all headers, formatters and rows configured.
	 *
	 * @return array{0: KliTable, 1: KliTableHeader}
	 */
	private function buildTable(): array
	{
		$table = new KliTable();

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
