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

use Kli\Exceptions\KliInputException;

/**
 * Interface KliTypeInterface.
 */
interface KliTypeInterface
{
	/**
	 * Explicitly set the default value.
	 *
	 * @param mixed $value
	 *
	 * @return static
	 */
	public function def(mixed $value): static;

	/**
	 * Checks if we have a default value.
	 *
	 * @return bool
	 */
	public function hasDefault(): bool;

	/**
	 * Gets the default value.
	 *
	 * @return mixed
	 */
	public function getDefault(): mixed;

	/**
	 * Called to validate an option value.
	 *
	 * @param string $opt_name the option name may be as provided by the user prefixed with "--" or "-"
	 * @param mixed  $value    the value to validate
	 *
	 * @return mixed the cleaned value to use
	 *
	 * @throws KliInputException when user input is invalid
	 */
	public function validate(string $opt_name, mixed $value): mixed;
}
