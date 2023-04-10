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
		$table = new KliTable();

		$table->addHeader('ID', 'id')
			->alignCenter();
		$table->addHeader('Name', 'name')
			->alignRight();
		$phone_header = $table->addHeader('Phone', 'phone')
			->alignCenter();
		$table->addHeader('Date', 'date')
			->setCellFormatter(new class() implements KliTableCellFormatterInterface {
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

		$time = \mktime(13, 8, 35, 1, 1, 2023);
		$table->addRows([
			[
				'id'    => 1,
				'name'  => 'Kpèdétin',
				'phone' => '+229 01 02 03 04',
				'date'  => $time - 54500,
				'color' => false,
			],
			[
				'id'    => 2,
				'name'  => 'Yemboka',
				'phone' => '+229 00 00 00 00',
				'date'  => $time,
				'color' => false,
			],
			[
				'id'    => 3,
				'name'  => 'Iméla',
				'phone' => '+229 11 22 33 44',
				'date'  => $time + 3800,
				'color' => true,
			],
		]);

		$dir     = __DIR__ . '/snapshots';
		$path    = $dir . '/table.txt';
		$content = $table->render();

		self::ensureSnapshotFile($path, $content);
		static::assertStringEqualsFile($path, $content);
		echo $content . \PHP_EOL;

		$table->borderStyle()
			->green();

		$path    = $dir . '/table.colored.txt';
		$content = $table->render();
		self::ensureSnapshotFile($path, $content);
		static::assertStringEqualsFile($path, $content);
		echo $content . \PHP_EOL;

		$table->borderStyle()
			->yellow();
		$phone_header->setWidth(15);
		$path    = $dir . '/table.fixed.width.txt';
		$content = $table->render();
		self::ensureSnapshotFile($path, $content);
		static::assertStringEqualsFile($path, $content);
		echo $content . \PHP_EOL;

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
		$path    = $dir . '/table.custom.border.txt';
		$content = $table->render();
		self::ensureSnapshotFile($path, $content);
		static::assertStringEqualsFile($path, $content);
		echo $content;
	}

	/**
	 * @param $file
	 * @param $content
	 */
	public static function ensureSnapshotFile($file, $content): void
	{
		if (!\file_exists($file)) {
			\file_put_contents($file, $content);
		}
	}
}
