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

/**
 * Class TestUtils.
 *
 * @internal
 */
final class TestUtils
{
	/**
	 * @param string $snapshot_path
	 * @param string $content
	 */
	public static function ensureSnapshotFile(string $snapshot_path, string $content): void
	{
		if (!\file_exists($snapshot_path)) {
			$dir = \dirname($snapshot_path);

			if (!\is_dir($dir)) {
				\mkdir($dir, 0777, true);
			}

			\file_put_contents($snapshot_path, $content);
		}
	}

	/**
	 * Strip ANSI escape codes from a string so that snapshot comparisons
	 * remain stable regardless of whether stdout is a TTY.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public static function stripAnsi(string $str): string
	{
		return (string) \preg_replace('/\033\[[0-9;]*m/', '', $str);
	}
}
