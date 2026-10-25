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

namespace Kli\Table;

use Kli\KliStyle;
use Kli\Table\Interfaces\KliTableCellFormatterInterface;

/**
 * Class KliTableHeader.
 *
 * Describes a single column in a KliTable: the display label shown in the
 * header row, the row-array key used to retrieve cell data, text alignment
 * (left / right / center), an optional fixed column width, and an optional
 * KliTableCellFormatterInterface for value formatting and per-cell styling.
 */
class KliTableHeader
{
	private string $label;
	private string $key;
	private string $align    = 'left';
	private ?KliStyle $style = null;
	private ?int $width      = null;

	private ?KliTableCellFormatterInterface $cell_formatter = null;

	/**
	 * KliTableHeader constructor.
	 *
	 * @param string $label the column heading text displayed in the header row
	 * @param string $key   the key used to look up a cell value in each row array
	 */
	public function __construct(string $label, string $key)
	{
		$this->label = $label;
		$this->key   = $key;
	}

	/**
	 * Gets the header label.
	 */
	public function getLabel(): string
	{
		return $this->label;
	}

	/**
	 * Gets the header key.
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * Sets the header width.
	 */
	public function setWidth(?int $width): static
	{
		$this->width = $width;

		return $this;
	}

	/**
	 * Gets the header width.
	 */
	public function getWidth(): ?int
	{
		return $this->width;
	}

	/**
	 * Gets the header alignment.
	 */
	public function getAlign(): string
	{
		return $this->align;
	}

	/**
	 * Aligns the header to the left.
	 */
	public function alignLeft(): static
	{
		$this->align = 'left';

		return $this;
	}

	/**
	 * Aligns the header to the right.
	 */
	public function alignRight(): static
	{
		$this->align = 'right';

		return $this;
	}

	/**
	 * Aligns the header to the center.
	 */
	public function alignCenter(): static
	{
		$this->align = 'center';

		return $this;
	}

	/**
	 * Gets the header style.
	 */
	public function getStyle(): ?KliStyle
	{
		return $this->style;
	}

	/**
	 * Sets the cell formatter.
	 */
	public function setCellFormatter(KliTableCellFormatterInterface $formatter): static
	{
		$this->cell_formatter = $formatter;

		return $this;
	}

	/**
	 * Returns the cell formatter.
	 */
	public function getCellFormatter(): ?KliTableCellFormatterInterface
	{
		return $this->cell_formatter;
	}
}
