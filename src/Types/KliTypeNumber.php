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

namespace Kli\Types;

use Kli\Exceptions\KliException;
use Kli\Exceptions\KliInputException;

/**
 * Class KliTypeNumber.
 */
class KliTypeNumber extends KliType
{
	protected array $error_messages = [
		'msg_require_number'  => 'option "-%s" require a number as value.',
		'msg_require_integer' => '"%s" is not a valid integer for option "-%s".',
		'msg_number_lt_min'   => '"%s" -> fails on min=%s for option "-%s".',
		'msg_number_gt_max'   => '"%s" -> fails on max=%s for option "-%s".',
	];

	private ?float $opt_min;

	private ?float $opt_max;

	private bool $is_int         = false;

	/**
	 * KliTypeNumber constructor.
	 *
	 * @param null|float $min the minimum number
	 * @param null|float $max the maximum number
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function __construct(?float $min = null, ?float $max = null)
	{
		if (isset($min)) {
			$this->min($min);
		}

		if (isset($max)) {
			$this->max($max);
		}
	}

	/**
	 * Sets number min value.
	 *
	 * @param float       $value   the minimum
	 * @param null|string $message the error message
	 *
	 * @throws \Kli\Exceptions\KliException
	 *
	 * @return $this
	 */
	public function min(float $value, ?string $message = null): self
	{
		if (isset($this->opt_max) && $value > $this->opt_max) {
			throw new KliException(\sprintf('min=%s and max=%s is not a valid condition.', $value, $this->opt_max));
		}

		$this->opt_min = $value;

		!empty($message) && $this->msg('msg_number_lt_min', $message);

		return $this;
	}

	/**
	 * Sets number max value.
	 *
	 * @param float       $value   the maximum
	 * @param null|string $message the error message
	 *
	 * @throws \Kli\Exceptions\KliException
	 *
	 * @return $this
	 */
	public function max(float $value, ?string $message = null): self
	{
		if (isset($this->opt_min) && $value < $this->opt_min) {
			throw new KliException(\sprintf('min=%s and max=%s is not a valid condition.', $this->opt_min, $value));
		}

		$this->opt_max = $value;

		!empty($message) && $this->msg('msg_number_gt_max', $message);

		return $this;
	}

	/**
	 * Sets number type as integer.
	 *
	 * @param null|string $message the error message
	 *
	 * @return $this
	 */
	public function integer(?string $message = null): self
	{
		$this->is_int = true;

		!empty($message) && $this->msg('msg_require_integer', $message);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function validate(string $opt_name, $value): int
	{
		if (!\is_numeric($value)) {
			throw new KliInputException(\sprintf($this->msg('msg_require_number'), $opt_name));
		}

		$_value = $value + 0;

		if ($this->is_int === true && !\is_int($_value)) {
			throw new KliInputException(
				\sprintf($this->msg('msg_require_integer'), $value, $opt_name)
			);
		}

		if (isset($this->opt_min) && $_value < $this->opt_min) {
			throw new KliInputException(
				\sprintf($this->msg('msg_number_lt_min'), $value, $this->opt_min, $opt_name)
			);
		}

		if (isset($this->opt_max) && $_value > $this->opt_max) {
			throw new KliInputException(
				\sprintf($this->msg('msg_number_gt_max'), $value, $this->opt_max, $opt_name)
			);
		}

		return $_value;
	}
}
