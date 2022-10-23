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

namespace Kli\Types\Interfaces;

/**
 * Interface KliTypeInterface.
 */
interface KliTypeInterface
{
	/**
	 * Called to validate an option value.
	 *
	 * @param string $opt_name the option name
	 * @param mixed  $value    the value to validate
	 *
	 * @return mixed the cleaned value to use
	 *
	 * @throws \Kli\Exceptions\KliInputException when user input is invalid
	 */
	public function validate(string $opt_name, $value);
}
