<?php

/**
 * Copyright (c) 2017-present, Emile Silas Sare
 *
 * This file is part of OZone (O'Zone) package.
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
     * @throws \Kli\Exceptions\KliInputException when user input is invalid
     *
     * @return mixed the cleaned value to use
     */
    public function validate($opt_name, $value);
}
