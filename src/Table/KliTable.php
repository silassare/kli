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
use Kli\KliUtils;

/**
 * Class KliTable.
 *
 * Renders a Unicode box-drawing table to a string. Define columns with
 * addHeader(), populate data with addRow() / addRows(), then cast to string
 * (via __toString()) or call render() directly. Column widths auto-size from
 * content and header labels unless fixed with KliTableHeader::setWidth().
 * Border characters can be overridden per-key with setBorderChars() (merges
 * into the defaults). ANSI colour can be applied to borders via borderStyle()
 * and to individual cells via KliTableCellFormatterInterface.
 *
 * Widths are terminal columns (KliUtils::displayWidth()): ANSI sequences take
 * none, a wide character or an emoji two, and a tab is written as TAB_SPACES
 * spaces. A cell may hold several lines, and its row grows to hold them. A
 * table wider than its maximum width (setMaxWidth(), the terminal's by
 * default) shrinks its widest columns and wraps their text; a column with a
 * fixed width truncates instead. Print the rendered table without wrapping it
 * again (Kli::writeLn($table, false)).
 */
class KliTable
{
	/** Minimum content width (excluding padding) for any cell, in characters. */
	public const MIN_CELL_WIDTH   = 5;

	/** Horizontal space reserved for padding on each side of cell content. */
	public const MIN_CELL_PADDING = 2;

	/** Character appended to cell content that exceeds the available width. */
	public const TRUNCATE_CHAR    = '…';

	/** Spaces a tab is written as, since a terminal would draw it up to its next stop. */
	public const TAB_SPACES       = 4;

	/**
	 * @var array<string,string>
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

	private KliStyle $border_style;

	/** The widest the table may be, borders included; null for no limit. */
	private ?int $max_width = null;

	/** Whether setMaxWidth() was called: until then, the terminal's width is the limit. */
	private bool $max_width_set = false;

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
	 * @return static
	 */
	public function setBorderChars(array $chars): static
	{
		$this->border_chars = \array_merge($this->border_chars, $chars);

		return $this;
	}

	/**
	 * Gets the table border style.
	 *
	 * @return KliStyle
	 */
	public function borderStyle(): KliStyle
	{
		return $this->border_style;
	}

	/**
	 * Sets the widest the table may be, in terminal columns, borders included.
	 *
	 * Null removes the limit. Until this is called, the limit is the terminal's
	 * width (KliUtils::terminalWidth()), when it is known.
	 *
	 * @param null|int $width
	 *
	 * @return static
	 */
	public function setMaxWidth(?int $width): static
	{
		$this->max_width     = null === $width ? null : \max(1, $width);
		$this->max_width_set = true;

		return $this;
	}

	/**
	 * Adds a new header to the table.
	 *
	 * @param string $label
	 * @param string $key
	 *
	 * @return KliTableHeader
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
	 * @return static
	 */
	public function addRow(array $row): static
	{
		$this->rows[] = $row;

		return $this;
	}

	/**
	 * Adds new rows to the table.
	 *
	 * @param array $rows
	 *
	 * @return static
	 */
	public function addRows(array $rows): static
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
		$labels = [];
		$texts  = [];
		$widths = [];

		foreach ($this->headers as $i => $header) {
			$labels[$i] = self::linesOf($header->getLabel());
			$widths[$i] = self::widthOf($labels[$i]);
		}

		foreach ($this->rows as $r => $row) {
			foreach ($this->headers as $i => $header) {
				$value     = $row[$header->getKey()] ?? '';
				$formatter = $header->getCellFormatter();

				$texts[$r][$i] = $formatter ? $formatter->format($value, $header, $row) : (string) $value;
				$widths[$i]    = \max($widths[$i], self::widthOf(self::linesOf($texts[$r][$i])));
			}
		}

		foreach ($this->headers as $i => $header) {
			$fixed = $header->getWidth();

			if (null !== $fixed) {
				$widths[$i] = \max($fixed, self::MIN_CELL_WIDTH);
			}
		}

		$widths = $this->fit($widths);

		$top    = [];
		$mid    = [];
		$bottom = [];

		foreach ($widths as $width) {
			$top[]    = \str_repeat($this->getBorderChar('top'), $width + self::MIN_CELL_PADDING);
			$mid[]    = \str_repeat($this->getBorderChar('mid'), $width + self::MIN_CELL_PADDING);
			$bottom[] = \str_repeat($this->getBorderChar('bottom'), $width + self::MIN_CELL_PADDING);
		}

		$mid_line = $this->border_style->apply(
			$this->getBorderChar('left-mid') . \implode($this->getBorderChar('mid-mid'), $mid)
			. $this->getBorderChar('right-mid')
		);

		$output   = [];
		$output[] = $this->border_style->apply(
			$this->getBorderChar('top-left') . \implode($this->getBorderChar('top-mid'), $top)
			. $this->getBorderChar('top-right')
		);

		$cells = [];

		foreach ($this->headers as $i => $header) {
			$cells[$i] = [
				'lines' => $this->fitLines($labels[$i], $widths[$i], null !== $header->getWidth()),
				'style' => $header->getStyle(),
			];
		}

		\array_push($output, ...$this->renderLines($cells, $widths));

		foreach ($this->rows as $r => $row) {
			$output[] = $mid_line;
			$cells    = [];

			foreach ($this->headers as $i => $header) {
				$text      = $texts[$r][$i];
				$cells[$i] = [
					'lines' => $this->fitLines(self::linesOf($text), $widths[$i], null !== $header->getWidth()),
					'style' => $header->getCellFormatter()?->getStyle($text, $header, $row),
				];
			}

			\array_push($output, ...$this->renderLines($cells, $widths));
		}

		$output[] = $this->border_style->apply(
			$this->getBorderChar('bottom-left') . \implode($this->getBorderChar('bottom-mid'), $bottom)
			. $this->getBorderChar('bottom-right')
		);

		return \implode(\PHP_EOL, $output);
	}

	/**
	 * Renders a single line of a cell to a fixed-width padded string.
	 *
	 * Truncates content wider than the available width (width minus padding)
	 * using TRUNCATE_CHAR, then pads it for its alignment: one space on each
	 * side at least, and the rest on the side the alignment leaves free.
	 *
	 * @param string         $value     pre-formatted cell content, on one line
	 * @param KliTableHeader $header    column definition (alignment, formatter, style)
	 * @param int            $width     total column width including padding
	 * @param bool           $is_header true when rendering the header row
	 * @param array          $row       the full data row (used for conditional styling)
	 *
	 * @return string
	 */
	public function renderCell(
		string $value,
		KliTableHeader $header,
		int $width,
		bool $is_header,
		array $row = []
	): string {
		$style = $is_header ? $header->getStyle() : $header->getCellFormatter()?->getStyle($value, $header, $row);
		$lines = $this->fitLines(self::linesOf($value), $width - self::MIN_CELL_PADDING, true);

		return $this->pad($lines[0] ?? '', $header->getAlign(), $width - self::MIN_CELL_PADDING, $style);
	}

	/**
	 * The lines of a text: its line breaks split it, and its tabs become spaces.
	 *
	 * @return list<string>
	 */
	private static function linesOf(string $text): array
	{
		$text = \str_replace("\t", \str_repeat(' ', self::TAB_SPACES), $text);

		return \explode("\n", \str_replace(["\r\n", "\r"], "\n", $text));
	}

	/**
	 * The width of the widest line, in terminal columns.
	 *
	 * @param list<string> $lines
	 */
	private static function widthOf(array $lines): int
	{
		$width = 0;

		foreach ($lines as $line) {
			$width = \max($width, KliUtils::displayWidth($line));
		}

		return $width;
	}

	/**
	 * Shrinks the widest columns, one column at a time, until the table fits its
	 * maximum width; a column with a fixed width, or already at MIN_CELL_WIDTH,
	 * is not shrunk. A table that cannot fit is left as wide as it gets.
	 *
	 * @param array<int, int> $widths content width of each column
	 *
	 * @return array<int, int>
	 */
	private function fit(array $widths): array
	{
		$max = $this->max_width_set ? $this->max_width : KliUtils::terminalWidth();

		if (null === $max) {
			return $widths;
		}

		// Borders: one before each column and one after the last, then each column's padding.
		$total = \count($widths) + 1 + \array_sum($widths) + \count($widths) * self::MIN_CELL_PADDING;

		while ($total > $max) {
			$widest = null;

			foreach ($this->headers as $i => $header) {
				if (
					null === $header->getWidth()
					&& $widths[$i] > self::MIN_CELL_WIDTH
					&& (null === $widest || $widths[$i] > $widths[$widest])
				) {
					$widest = $i;
				}
			}

			if (null === $widest) {
				break;
			}

			--$widths[$widest];
			--$total;
		}

		return $widths;
	}

	/**
	 * The lines of a cell fitted to its width: wrapped, or truncated with
	 * TRUNCATE_CHAR when the column has a fixed width. A line that has to be
	 * cut loses its ANSI sequences, which cannot be cut safely.
	 *
	 * @param list<string> $lines
	 *
	 * @return list<string>
	 */
	private function fitLines(array $lines, int $width, bool $truncate): array
	{
		$out = [];

		foreach ($lines as $line) {
			if (KliUtils::displayWidth($line) <= $width) {
				$out[] = $line;

				continue;
			}

			$line = KliUtils::stripAnsi($line);

			if ($truncate) {
				$out[] = \mb_strimwidth($line, 0, $width, self::TRUNCATE_CHAR, 'UTF-8');
			} else {
				\array_push($out, ...\explode("\n", KliUtils::wrap($line, $width, true)));
			}
		}

		return $out;
	}

	/**
	 * Renders the lines of one row: as many as its tallest cell has.
	 *
	 * @param array<int, array{lines: list<string>, style: null|KliStyle}> $cells
	 * @param array<int, int>                                              $widths
	 *
	 * @return list<string>
	 */
	private function renderLines(array $cells, array $widths): array
	{
		$height = 1;

		foreach ($cells as $cell) {
			$height = \max($height, \count($cell['lines']));
		}

		$out = [];

		for ($l = 0; $l < $height; ++$l) {
			$parts = [];

			foreach ($this->headers as $i => $header) {
				$parts[] = $this->pad(
					$cells[$i]['lines'][$l] ?? '',
					$header->getAlign(),
					$widths[$i],
					$cells[$i]['style']
				);
			}

			$out[] = $this->getStyledBorderChar('left')
				. \implode($this->getStyledBorderChar('middle'), $parts)
				. $this->getStyledBorderChar('right');
		}

		return $out;
	}

	/**
	 * A line padded to its column: one space on each side, the rest on the
	 * side its alignment leaves free.
	 */
	private function pad(string $line, string $align, int $width, ?KliStyle $style): string
	{
		$free  = \max(0, $width - KliUtils::displayWidth($line));
		$left  = 0;
		$right = $free;

		if ('center' === $align) {
			$left  = (int) ($free / 2);
			$right = $free - $left;
		} elseif ('right' === $align) {
			$left  = $free;
			$right = 0;
		}

		if ($style && '' !== $line) {
			$line = $style->apply($line);
		}

		return ' ' . \str_repeat(' ', $left) . $line . \str_repeat(' ', $right) . ' ';
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
