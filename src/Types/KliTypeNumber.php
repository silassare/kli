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

	class KliTypeNumber implements KliType
	{
		private $min;
		private $max;
		private $is_int         = false;
		private $error_messages = [
			'msg_require_number'  => 'option "-%s" require a number as value.',
			'msg_require_integer' => '"%s" is not a valid integer for option "-%s".',
			'msg_number_lt_min'   => '"%s" -> fails on min=%s for option "-%s".',
			'msg_number_gt_max'   => '"%s" -> fails on max=%s for option "-%s".'
		];

		/**
		 * KliTypeNumber constructor.
		 *
		 * @param int|null $min the minimum number
		 * @param int|null $max the maximum number
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function __construct($min = null, $max = null)
		{
			if (isset($min)) $this->min($min);
			if (isset($max)) $this->max($max);
		}

		/**
		 * Sets number min value.
		 *
		 * @param int         $value         the minimum
		 * @param string|null $error_message the error message
		 *
		 * @return $this
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function min($value, $error_message = null)
		{
			if (!is_numeric($value))
				throw new KliException(sprintf('"%s" is not a valid number.', $value));

			$_value = $value + 0;

			if (isset($this->max) AND $_value > $this->max)
				throw new KliException(sprintf('min=%s and max=%s is not a valid condition.', $value, $this->max));

			$this->min = $_value;

			return $this->customErrorMessage('msg_number_lt_min', $error_message);
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

		/**
		 * Sets number max value.
		 *
		 * @param int         $value         the maximum
		 * @param string|null $error_message the error message
		 *
		 * @return $this
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function max($value, $error_message = null)
		{
			if (!is_numeric($value))
				throw new KliException(sprintf('"%s" is not a valid number.', $value));

			$_value = $value + 0;

			if (isset($this->min) AND $_value < $this->min)
				throw new KliException(sprintf('min=%s and max=%s is not a valid condition.', $this->min, $value));

			$this->max = $_value;

			return $this->customErrorMessage('msg_number_gt_max', $error_message);
		}

		/**
		 * Sets number type as integer.
		 *
		 * @param string|null $error_message the error message
		 *
		 * @return $this
		 */
		public function integer($error_message = null)
		{
			$this->is_int = true;

			return $this->customErrorMessage('msg_require_integer', $error_message);
		}

		/**
		 * @inheritdoc
		 */
		public function validate($opt_name, $value)
		{
			if (!is_numeric($value))
				throw new KliInputException(sprintf($this->error_messages['msg_require_number'], $opt_name));

			$_value = $value + 0;

			if ($this->is_int === true AND !is_int($_value))
				throw new KliInputException(sprintf($this->error_messages['msg_require_integer'], $value, $opt_name));

			if (isset($this->min) AND $_value < $this->min)
				throw new KliInputException(sprintf($this->error_messages['msg_number_lt_min'], $value, $this->min, $opt_name));

			if (isset($this->max) AND $_value > $this->max)
				throw new KliInputException(sprintf($this->error_messages['msg_number_gt_max'], $value, $this->max, $opt_name));

			return $_value;
		}
	}
