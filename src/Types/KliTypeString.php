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
 * Class KliTypeString.
 */
class KliTypeString extends KliType
{
	protected array $error_messages = [
		'msg_require_string'        => 'option "-%s" require a string as value.',
		'msg_length_lt_min'         => '"%s" -> fails on minlength=%s for option "-%s".',
		'msg_length_gt_max'         => '"%s" -> fails on maxlength=%s for option "-%s".',
		'msg_validator_check_fails' => '"%s" -> fails on validator function for option "-%s".',
		'msg_pattern_check_fails'   => '"%s" -> fails on regular expression for option "-%s".',
	];

	private ?int $opt_min = null;

	private ?int $opt_max = null;

	private string $reg = '';

	/**
	 * @var null|callable
	 */
	private $validator_fn;

	/**
	 * KliTypeString constructor.
	 *
	 * @param null|int $min the minimum string length
	 * @param null|int $max the maximum string length
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function __construct(?int $min = null, ?int $max = null)
	{
		if (isset($min)) {
			$this->min($min);
		}

		if (isset($max)) {
			$this->max($max);
		}
	}

	/**
	 * Sets minimum string length.
	 *
	 * @param int         $value   the minimum string length
	 * @param null|string $message the error message
	 *
	 * @return $this
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function min(int $value, ?string $message = null): self
	{
		if ($value < 1) {
			throw new KliException(\sprintf('"%s" is not a valid integer(>0).', $value));
		}

		if (isset($this->opt_max) && $value > $this->opt_max) {
			throw new KliException(\sprintf('min=%s and max=%s is not a valid condition.', $value, $this->opt_max));
		}

		$this->opt_min = $value;

		!empty($message) && $this->msg('msg_length_lt_min', $message);

		return $this;
	}

	/**
	 * Sets maximum string length.
	 *
	 * @param int         $value   the maximum string length
	 * @param null|string $message the error message
	 *
	 * @return $this
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function max(int $value, ?string $message = null): self
	{
		if ($value < 1) {
			throw new KliException(\sprintf('"%s" is not a valid integer(>0).', $value));
		}

		if (isset($this->opt_min) && $value < $this->opt_min) {
			throw new KliException(\sprintf('min=%s and max=%s is not a valid condition.', $this->opt_min, $value));
		}

		$this->opt_max = $value;

		!empty($message) && $this->msg('msg_length_gt_max', $message);

		return $this;
	}

	/**
	 * Sets the string pattern.
	 *
	 * @param string      $reg_expression the regular expression
	 * @param null|string $message        the error message
	 *
	 * @return $this
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function pattern(string $reg_expression, ?string $message = null): self
	{
		if (false === \preg_match($reg_expression, '')) {
			throw new KliException(\sprintf('invalid regular expression: %s', $reg_expression));
		}

		$this->reg = $reg_expression;

		!empty($message) && $this->msg('msg_pattern_check_fails', $message);

		return $this;
	}

	/**
	 * Sets a validator.
	 *
	 * @param callable    $fn
	 * @param null|string $message
	 *
	 * @return $this
	 */
	public function validator(callable $fn, ?string $message = null): self
	{
		$this->validator_fn = $fn;

		!empty($message) && $this->msg('msg_validator_check_fails', $message);

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate(string $opt_name, $value)
	{
		if (!\is_string($value)) {
			throw new KliInputException(\sprintf($this->msg('msg_require_string'), $opt_name));
		}

		if (isset($this->opt_min) && \strlen($value) < $this->opt_min) {
			throw new KliInputException(
				\sprintf($this->msg('msg_length_lt_min'), $value, $this->opt_min, $opt_name)
			);
		}

		if (isset($this->opt_max) && \strlen($value) > $this->opt_max) {
			throw new KliInputException(
				\sprintf($this->msg('msg_length_gt_max'), $value, $this->opt_max, $opt_name)
			);
		}

		if (!empty($this->reg) && !\preg_match($this->reg, $value)) {
			throw new KliInputException(
				\sprintf($this->msg('msg_pattern_check_fails'), $value, $opt_name)
			);
		}

		if (isset($this->validator_fn) && !($this->validator_fn)($value)) {
			throw new KliInputException(
				\sprintf($this->msg('msg_validator_check_fails'), $value, $opt_name)
			);
		}

		return $value;
	}
}
