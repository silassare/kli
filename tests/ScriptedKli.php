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

use Kli\Kli;

/**
 * A Kli subclass that feeds pre-scripted responses to readLine() so that
 * interactive-prompt and interactive-mode features can be exercised in tests
 * without blocking on real user input.
 *
 * @internal
 */
final class ScriptedKli extends Kli
{
	/** @var list<string> Prompts shown to the user, in order. */
	public array $promptLog = [];

	/** @var list<string> */
	private array $script;

	/**
	 * @param string       $name                   CLI title
	 * @param list<string> $script                 Responses returned by readLine(), in order.
	 *                                             Once exhausted, '' is returned.
	 * @param bool         $allow_interactive_mode pass true when testing switchToInteractiveMode()
	 */
	public function __construct(string $name, array $script, bool $allow_interactive_mode = false)
	{
		parent::__construct($name, $allow_interactive_mode);
		$this->script = $script;
	}

	/**
	 * {@inheritDoc}
	 */
	public function readLine(string $prompt, bool $is_password = false): string
	{
		$this->promptLog[] = $prompt;

		return \array_shift($this->script) ?? '';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Throws KliTerminateCalledException instead of calling exit() so that
	 * tests can assert the requested exit code without killing the PHPUnit
	 * process.
	 */
	public function terminate(int $code = 0): never
	{
		throw new KliTerminateCalledException($code);
	}
}
