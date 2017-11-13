<?php
	/**
	 * Copyright (c) Emile Silas Sare <emile.silas@gmail.com>
	 *
	 * This file is part of the Kli package.
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
			'msg_length_lt_min'       => 'input min=%s for option "-%s".',
			'msg_length_gt_max'       => 'input max=%s for option "-%s".',
			'msg_pattern_check_fails' => '"%s" fails on regular expression for option "-%s".'
		];

		/**
		 * KliTypeString constructor.
		 *
		 * @param int|null $min the minimum string length
		 * @param int|null $max the maximum string length
		 */
		public function __construct($min = null, $max = null)
		{
			if (isset($min)) $this->min($min);
			if (isset($max)) $this->max($max);
		}

		/**
		 * Sets the string pattern.
		 *
		 * @param string      $pattern       the pattern (regular expression)
		 * @param string|null $error_message the error message
		 *
		 * @return $this
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function pattern($pattern, $error_message = null)
		{
			if (false === preg_match($pattern, null))
				throw new KliException(sprintf('invalid regular expression: %s', $pattern));

			$this->reg = $pattern;

			return $this->customErrorMessage('msg_pattern_check_fails', $error_message);
		}

		/**
		 * Sets maximum string length.
		 *
		 * @param int         $value         the maximum string length
		 * @param string|null $error_message the error message
		 *
		 * @return $this
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function max($value, $error_message = null)
		{
			if (!is_int($value) OR $value < 1)
				throw new KliException(sprintf('"%s" is not a valid integer(>0).', $value));
			if (isset($this->min) AND $value < $this->min)
				throw new KliException(sprintf('min=%s and max=%s is not a valid condition.', $this->min, $value));

			$this->max = $value;

			return $this->customErrorMessage('msg_length_gt_max', $error_message);
		}

		/**
		 * Sets minimum string length.
		 *
		 * @param int         $value         the minimum string length
		 * @param string|null $error_message the error message
		 *
		 * @return $this
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function min($value, $error_message = null)
		{
			if (!is_int($value) OR $value < 1)
				throw new KliException(sprintf('"%s" is not a valid integer(>0).', $value));
			if (isset($this->max) AND $value > $this->max)
				throw new KliException(sprintf('min=%s and max=%s is not a valid condition.', $value, $this->max));

			$this->min = $value;

			return $this->customErrorMessage('msg_length_lt_min', $error_message);
		}

		/**
		 * {@inheritdoc}
		 */
		public function validate($opt_name, $value)
		{
			if (!is_string($value))
				throw new KliInputException(sprintf($this->error_messages['msg_require_string'], $opt_name));

			if (isset($this->min) AND strlen($value) < $this->min)
				throw new KliInputException(sprintf($this->error_messages['msg_length_lt_min'], $value, $this->min, $opt_name));

			if (isset($this->max) AND strlen($value) > $this->max)
				throw new KliInputException(sprintf($this->error_messages['msg_length_gt_max'], $value, $this->max, $opt_name));

			if (isset($this->reg) AND !preg_match($this->reg, $value))
				throw new KliInputException(sprintf($this->error_messages['msg_pattern_check_fails'], $value, $opt_name));

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