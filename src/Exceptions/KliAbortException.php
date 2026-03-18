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

namespace Kli\Exceptions;

/**
 * Class KliAbortException.
 *
 * Thrown by error(), warn(), and success() when $exit is non-null and the
 * CLI is running in interactive mode. In script mode those methods call
 * exit() directly; in interactive mode throwing this exception lets the REPL
 * loop absorb the abort cleanly without killing the whole process.
 *
 * Callers should never need to catch this directly -- execute() handles it.
 *
 * @internal
 */
final class KliAbortException extends KliRuntimeException
{
	public function __construct()
	{
		parent::__construct('Command aborted.');
	}
}
