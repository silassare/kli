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

	interface KliType
	{
		/**
		 * Called to validate an option value.
		 *
		 * @param string $opt_name the option name
		 * @param string $value    the value to validate
		 *
		 * @return mixed    the cleaned value to use.
		 *
		 * @throws \Kli\Exceptions\KliInputException    when user input is invalid
		 */
		public function validate($opt_name, $value);
	}