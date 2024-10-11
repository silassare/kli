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
			\file_put_contents($snapshot_path, $content);
		}
	}
}
