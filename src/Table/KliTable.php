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

/**
 * Class KliTable.
 */
class KliTable
{
	public const MIN_CELL_WIDTH   = 5;
	public const MIN_CELL_PADDING = 2;
	public const TRUNCATE_CHAR    = '…';

	/**
	 * @var string[]
	 */
	protected array $border_chars = [
		'top'          => '═',
		'top-mid'      => '╤',
		'top-left'     => '╔',
		'top-right'    => '╗',
		'bottom'       => '═',
		'bottom-mid'   => '╧',
		'bottom-left'  => '╚',
		'bottom-right' => '╝',
		'left'         => '║',
		'left-mid'     => '╟',
		'mid'          => '─',
		'mid-mid'      => '┼',
		'right'        => '║',
		'right-mid'    => '╢',
		'middle'       => '│',
	];

	/**
	 * @var KliTableHeader[]
	 */
	private array $headers = [];

	private array $rows = [];

	/**
	 * @var \Kli\KliStyle
	 */
	private KliStyle $border_style;

	/**
	 * KliTable constructor.
	 */
	public function __construct()
	{
		$this->border_style = new KliStyle();
	}

	/**
	 * Returns the table as a string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Sets the table border chars.
	 *
	 * @param array $chars
	 *
	 * @return $this
	 */
	public function setBorderChars(array $chars): self
	{
		$this->border_chars = \array_merge($this->border_chars, $chars);

		return $this;
	}

	/**
	 * Gets the table border style.
	 *
	 * @return \Kli\KliStyle
	 */
	public function borderStyle(): KliStyle
	{
		return $this->border_style;
	}

	/**
	 * Adds a new header to the table.
	 *
	 * @param string $label
	 * @param string $key
	 *
	 * @return \Kli\Table\KliTableHeader
	 */
	public function addHeader(string $label, string $key): KliTableHeader
	{
		$header = new KliTableHeader($label, $key);

		$this->headers[] = $header;

		return $header;
	}

	/**
	 * Adds a new row to the table.
	 *
	 * @param array $row
	 *
	 * @return $this
	 */
	public function addRow(array $row): self
	{
		$this->rows[] = $row;

		return $this;
	}

	/**
	 * Adds new rows to the table.
	 *
	 * @param array $rows
	 *
	 * @return $this
	 */
	public function addRows(array $rows): self
	{
		foreach ($rows as $row) {
			if (\is_array($row)) {
				$this->addRow($row);
			}
		}

		return $this;
	}

	/**
	 * Renders the table.
	 *
	 * @return string
	 */
	public function render(): string
	{
		$header_count = \count($this->headers);
		$top_line     = $this->getBorderChar('top-left');
		$bottom_line  = $this->getBorderChar('bottom-left');
		$mid_line     = $this->getBorderChar('left-mid');
		$header_cells = [];

		$formatted_rows = [];
		$max_widths     = [];
		foreach ($this->rows as $row) {
			$formatted_row = [];
			foreach ($this->headers as $header) {
				$key   = $header->getKey();
				$value = $row[$key] ?? '';

				$formatter           = $header->getCellFormatter();
				$formatted_row[$key] = $formatter ? $formatter->format($value, $header, $row) : (string) $value;
				$len                 = \strlen($formatted_row[$key]);
				if (!isset($max_widths[$key]) || $max_widths[$key] < $len) {
					$max_widths[$key] = $len;
				}
			}
			$formatted_rows[] = $formatted_row;
		}

		foreach ($this->headers as $header) {
			--$header_count;
			$key   = $header->getKey();
			$width = $header->getWidth();

			if ($width) {
				$width = \max($width, self::MIN_CELL_WIDTH) + self::MIN_CELL_PADDING;
			} else {
				$width = \max($max_widths[$key], \strlen($header->getLabel())) + self::MIN_CELL_PADDING;
			}

			$max_widths[$key] = $width;

			$top_line .= \str_repeat($this->getBorderChar('top'), $width);
			$mid_line .= \str_repeat($this->getBorderChar('mid'), $width);
			$bottom_line .= \str_repeat($this->getBorderChar('bottom'), $width);

			if ($header_count) {
				$top_line .= $this->getBorderChar('top-mid');
				$mid_line .= $this->getBorderChar('mid-mid');
				$bottom_line .= $this->getBorderChar('bottom-mid');
			} else {
				$top_line .= $this->getBorderChar('top-right');
				$mid_line .= $this->getBorderChar('right-mid');
				$bottom_line .= $this->getBorderChar('bottom-right');
			}
			$header_cells[] = $this->renderCell($header->getLabel(), $header, $width, true);
		}

		$top_line    = $this->border_style->apply($top_line);
		$mid_line    = $this->border_style->apply($mid_line);
		$bottom_line = $this->border_style->apply($bottom_line);

		$output[] = $top_line;
		$output[] = $this->getStyledBorderChar('left') . \implode($this->getStyledBorderChar('middle'), $header_cells) . $this->getStyledBorderChar('right');

		foreach ($formatted_rows as $index => $formatted_row) {
			$output[] = $mid_line;
			$cells    = [];
			foreach ($this->headers as $header) {
				$key   = $header->getKey();
				$value = $formatted_row[$key];
				$width = $max_widths[$key];

				$cells[] = $this->renderCell($value, $header, $width, false, $this->rows[$index]);
			}

			$output[] = $this->getStyledBorderChar('left') . \implode($this->getStyledBorderChar('middle'), $cells) . $this->getStyledBorderChar('right');
		}

		$output[] = $bottom_line;

		return \implode(\PHP_EOL, $output);
	}

	/**
	 * Renders a single cell.
	 *
	 * @param mixed                     $value
	 * @param \Kli\Table\KliTableHeader $header
	 * @param int                       $width
	 * @param bool                      $is_header
	 * @param array                     $row
	 *
	 * @return string
	 */
	public function renderCell(string $value, KliTableHeader $header, int $width, bool $is_header, array $row = []): string
	{
		$align = $header->getAlign();

		if ($is_header) {
			$style = $header->getStyle();
		} else {
			$formatter = $header->getCellFormatter();
			$style     = $formatter ? $formatter->getStyle($value, $header, $row) : null;
		}

		$value = \mb_strimwidth($value, 0, $width - self::MIN_CELL_PADDING, self::TRUNCATE_CHAR);

		$padding      = $width - \mb_strlen($value);
		$padding_left = $padding_right = 0;

		if ('center' === $align) {
			$padding_left  = (int) ($padding / 2);
			$padding_right = $padding - $padding_left;
		} elseif ('right' === $align) {
			$padding_left = $padding;
		} else {
			$padding_right = $padding;
		}

		if ($style) {
			$value = $style->apply($value);
		}

		return \str_repeat(' ', $padding_left) . $value . \str_repeat(' ', $padding_right);
	}

	/**
	 * Get border char.
	 *
	 * @param string $side
	 *
	 * @return string
	 */
	private function getBorderChar(string $side): string
	{
		return $this->border_chars[$side];
	}

	/**
	 * Get styled border char.
	 *
	 * @param string $side
	 *
	 * @return string
	 */
	private function getStyledBorderChar(string $side): string
	{
		return $this->border_style->apply($this->border_chars[$side]);
	}
}
