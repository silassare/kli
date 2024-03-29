<?php

/**
 * Copyright (c) 2017-present, Emile Silas Sare
 *
 * This file is part of Kli package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kli\Types;

use Kli\Exceptions\KliException;
use Kli\Exceptions\KliInputException;

class KliTypeString implements KliType
{
	private $min;

	private $max;

	private $reg;

	private $error_messages = [
		'msg_require_string'      => 'option "-%s" require a string as value.',
		'msg_length_lt_min'       => '"%s" -> fails on minlength=%s for option "-%s".',
		'msg_length_gt_max'       => '"%s" -> fails on maxlength=%s for option "-%s".',
		'msg_pattern_check_fails' => '"%s" -> fails on regular expression for option "-%s".',
	];

	/**
	 * KliTypeString constructor.
	 *
	 * @param null|int $min the minimum string length
	 * @param null|int $max the maximum string length
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function __construct($min = null, $max = null)
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
	 * @param int         $value         the minimum string length
	 * @param null|string $error_message the error message
	 *
	 * @throws \Kli\Exceptions\KliException
	 *
	 * @return $this
	 */
	public function min($value, $error_message = null)
	{
		if (!\is_int($value) || $value < 1) {
			throw new KliException(\sprintf('"%s" is not a valid integer(>0).', $value));
		}

		if (isset($this->max) && $value > $this->max) {
			throw new KliException(\sprintf('min=%s and max=%s is not a valid condition.', $value, $this->max));
		}

		$this->min = $value;

		return $this->customErrorMessage('msg_length_lt_min', $error_message);
	}

	/**
	 * Sets maximum string length.
	 *
	 * @param int         $value         the maximum string length
	 * @param null|string $error_message the error message
	 *
	 * @throws \Kli\Exceptions\KliException
	 *
	 * @return $this
	 */
	public function max($value, $error_message = null)
	{
		if (!\is_int($value) || $value < 1) {
			throw new KliException(\sprintf('"%s" is not a valid integer(>0).', $value));
		}

		if (isset($this->min) && $value < $this->min) {
			throw new KliException(\sprintf('min=%s and max=%s is not a valid condition.', $this->min, $value));
		}

		$this->max = $value;

		return $this->customErrorMessage('msg_length_gt_max', $error_message);
	}

	/**
	 * Sets the string pattern.
	 *
	 * @param string      $pattern       the pattern (regular expression)
	 * @param null|string $error_message the error message
	 *
	 * @throws \Kli\Exceptions\KliException
	 *
	 * @return $this
	 */
	public function pattern($pattern, $error_message = null)
	{
		if (false === \preg_match($pattern, '')) {
			throw new KliException(\sprintf('invalid regular expression: %s', $pattern));
		}

		$this->reg = $pattern;

		return $this->customErrorMessage('msg_pattern_check_fails', $error_message);
	}

	/**
	 * @inheritdoc
	 */
	public function validate($opt_name, $value)
	{
		if (!\is_string($value)) {
			throw new KliInputException(\sprintf($this->error_messages['msg_require_string'], $opt_name));
		}

		if (isset($this->min) && \strlen($value) < $this->min) {
			throw new KliInputException(
				\sprintf($this->error_messages['msg_length_lt_min'], $value, $this->min, $opt_name)
			);
		}

		if (isset($this->max) && \strlen($value) > $this->max) {
			throw new KliInputException(
				\sprintf($this->error_messages['msg_length_gt_max'], $value, $this->max, $opt_name)
			);
		}

		if (isset($this->reg) && !\preg_match($this->reg, $value)) {
			throw new KliInputException(
				\sprintf($this->error_messages['msg_pattern_check_fails'], $value, $opt_name)
			);
		}

		return $value;
	}

	/**
	 * Sets custom error message
	 *
	 * @param string $key     the error key
	 * @param string $message the error message
	 *
	 * @return $this
	 */
	private function customErrorMessage($key, $message)
	{
		if (!empty($message)) {
			$this->error_messages[$key] = $message;
		}

		return $this;
	}
}
