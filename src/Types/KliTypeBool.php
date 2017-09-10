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

	use Kli\Exceptions\KliInputException;

	class KliTypeBool implements KliType
	{
		private        $strict;
		private static $list           = [true, false, 'true', 'false'];
		private static $extended_list  = [true, false, 1, 0, '1', '0', 'true', 'false', 'yes', 'no', 'y', 'n'];
		private static $map            = [
			'1'     => true,
			'0'     => false,
			'true'  => true,
			'false' => false,
			'yes'   => true,
			'no'    => false,
			'y'     => true,
			'n'     => false
		];
		private        $error_messages = [
			'msg_require_bool' => 'option "-%s" require a boolean.'
		];

		/**
		 * KliTypeBool Constructor.
		 *
		 * @param bool        $strict        whether to limit bool value to (true,false,'true','false')
		 * @param string|null $error_message the error message
		 */
		public function __construct($strict = true, $error_message = null)
		{
			$this->strict = (bool)$strict;
			$this->customErrorMessage('msg_require_bool', $error_message);
		}

		/**
		 * {@inheritdoc}
		 */
		public function validate($opt_name, $value)
		{
			if (!in_array($value, ($this->strict ? self::$list : self::$extended_list)))
				throw new KliInputException(sprintf($this->error_messages['msg_require_bool'], $value, $opt_name));

			return (is_string($value) ? self::$map[strtolower($value)] : (bool)$value);
		}

		/**
		 * set custom error message
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