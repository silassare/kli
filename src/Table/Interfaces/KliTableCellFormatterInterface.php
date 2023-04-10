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

namespace Kli\Table\Interfaces;

use Kli\KliStyle;
use Kli\Table\KliTableHeader;

/**
 * Interface KliTableCellFormatterInterface.
 */
interface KliTableCellFormatterInterface
{
	/**
	 * Formats a cell value.
	 *
	 * @param mixed                     $value  the cell value
	 * @param \Kli\Table\KliTableHeader $header the header
	 * @param array                     $row    the row
	 *
	 * @return string
	 */
	public function format($value, KliTableHeader $header, array $row): string;

	/**
	 * Gets the style of a cell.
	 *
	 * @param mixed                     $value  the cell value
	 * @param \Kli\Table\KliTableHeader $header the header
	 * @param array                     $row    the row
	 *
	 * @return null|\Kli\KliStyle
	 */
	public function getStyle($value, KliTableHeader $header, array $row): ?KliStyle;
}
