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

namespace Kli\Tests;

use RuntimeException;

/**
 * Thrown by ScriptedKli::terminate() in place of exit() so that tests can
 * assert the requested exit code without killing the PHPUnit process.
 *
 * @internal
 */
final class KliTerminateCalledException extends RuntimeException
{
    public function __construct(public readonly int $exitCode)
    {
        parent::__construct(\sprintf('terminate(%d) called', $exitCode));
    }
}
