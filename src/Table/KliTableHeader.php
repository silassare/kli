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
 */
class KliTableHeader
{
	private string    $label;
	private string    $key;
	private string    $align = 'left';
	private ?KliStyle $style = null;
	private ?int      $width = null;

	/**
	 * @var null|\Kli\Table\Interfaces\KliTableCellFormatterInterface
	 */
	private ?KliTableCellFormatterInterface $cell_formatter = null;

	/**
	 * KliTableHeader constructor.
	 */
	public function __construct(string $label, string $key)
	{
		$this->label = $label;
		$this->key   = $key;
	}

	/**
	 * Gets the header label.
	 *
	 * @return string
	 */
	public function getLabel(): string
	{
		return $this->label;
	}

	/**
	 * Gets the header key.
	 *
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * Sets the header width.
	 *
	 * @param null|int $width
	 *
	 * @return $this
	 */
	public function setWidth(?int $width): self
	{
		$this->width = $width;

		return $this;
	}

	/**
	 * Gets the header width.
	 *
	 * @return null|int
	 */
	public function getWidth(): ?int
	{
		return $this->width;
	}

	/**
	 * Gets the header alignment.
	 *
	 * @return string
	 */
	public function getAlign(): string
	{
		return $this->align;
	}

	/**
	 * Aligns the header to the left.
	 *
	 * @return $this
	 */
	public function alignLeft(): self
	{
		$this->align = 'left';

		return $this;
	}

	/**
	 * Aligns the header to the right.
	 *
	 * @return $this
	 */
	public function alignRight(): self
	{
		$this->align = 'right';

		return $this;
	}

	/**
	 * Aligns the header to the center.
	 *
	 * @return $this
	 */
	public function alignCenter(): self
	{
		$this->align = 'center';

		return $this;
	}

	/**
	 * Gets the header style.
	 *
	 * @return null|\Kli\KliStyle
	 */
	public function getStyle(): ?KliStyle
	{
		return $this->style;
	}

	/**
	 * Sets the cell formatter.
	 *
	 * @param \Kli\Table\Interfaces\KliTableCellFormatterInterface $formatter
	 *
	 * @return $this
	 */
	public function setCellFormatter(KliTableCellFormatterInterface $formatter): self
	{
		$this->cell_formatter = $formatter;

		return $this;
	}

	/**
	 * Returns the cell formatter.
	 *
	 * @return null|\Kli\Table\Interfaces\KliTableCellFormatterInterface
	 */
	public function getCellFormatter(): ?KliTableCellFormatterInterface
	{
		return $this->cell_formatter;
	}
}
