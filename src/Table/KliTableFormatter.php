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
 * Class KliTableFormatter.
 *
 * Built-in KliTableCellFormatterInterface implementations for common value
 * types. Use the static factory methods to create an instance, then assign it
 * to a column via KliTableHeader::setCellFormatter().
 *
 * - bool()   -- renders true as "Yes" and false as "No"
 * - number() -- formats a numeric value with number_format()
 * - date()   -- formats a Unix timestamp with date()
 */
class KliTableFormatter implements KliTableCellFormatterInterface
{
	private string $type;

	/**
	 * @var null|KliStyle
	 */
	private ?KliStyle $style;
	private array $params;

	/**
	 * KliTableFormatter constructor.
	 *
	 * @param string        $type   internal format type: 'bool', 'number', or 'date'
	 * @param null|KliStyle $style  optional cell style returned by getStyle()
	 * @param array         $params type-specific configuration (decimals, format, etc.)
	 */
	protected function __construct(string $type, ?KliStyle $style = null, array $params = [])
	{
		$this->type   = $type;
		$this->style  = $style;
		$this->params = $params;
	}

	/**
	 * {@inheritDoc}
	 */
	public function format($value, KliTableHeader $header, array $row): string
	{
		return match ($this->type) {
			'bool'   => $value ? 'Yes' : 'No',
			'number' => \number_format(
				$value,
				$this->params['decimals'] ?? 0,
				$this->params['decimal_point'] ?? '.',
				$this->params['thousands_sep'] ?? ','
			),
			'date'   => $value ? \date($this->params['format'], $value) : 'N/A',
			default  => (string) $value,
		};
	}

	/**
	 * {@inheritDoc}
	 */
	public function getStyle($value, KliTableHeader $header, array $row): ?KliStyle
	{
		return $this->style;
	}

	/**
	 * Creates a boolean formatter.
	 *
	 * @param null|KliStyle $style
	 *
	 * @return KliTableFormatter
	 */
	public static function bool(?KliStyle $style = null): self
	{
		return new self('bool', $style);
	}

	/**
	 * Creates a number formatter.
	 *
	 * @param int           $decimals
	 * @param string        $decimal_point
	 * @param string        $thousands_sep
	 * @param null|KliStyle $style
	 *
	 * @return KliTableFormatter
	 */
	public static function number(
		int $decimals = 0,
		string $decimal_point = '.',
		string $thousands_sep = ',',
		?KliStyle $style = null
	): self {
		return new self('number', $style, [
			'decimals'      => $decimals,
			'decimal_point' => $decimal_point,
			'thousands_sep' => $thousands_sep,
		]);
	}

	/**
	 * Creates a date formatter.
	 *
	 * @param string        $format
	 * @param null|KliStyle $style
	 *
	 * @return KliTableFormatter
	 */
	public static function date(string $format = 'Y-m-d H:i:s', ?KliStyle $style = null): self
	{
		return new self('date', $style, ['format' => $format]);
	}
}
