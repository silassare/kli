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

namespace Kli;

/**
 * Class KliArgs.
 */
class KliArgs
{
	protected KliAction $action;
	protected array $named;
	protected array $anonymous;

	/**
	 * KliArgs constructor.
	 */
	public function __construct(KliAction $action, array $named, array $anonymous)
	{
		$this->anonymous = $anonymous;
		$this->named     = $named;
		$this->action    = $action;
	}

	/**
	 * Gets a named option value by option name, alias, or single-char flag.
	 *
	 * @param string $name the option name, alias, or flag character
	 *
	 * @return null|mixed
	 */
	public function get(string $name): mixed
	{
		$name = $this->action->getOption($name)
			->getName();

		return $this->named[$name] ?? null;
	}

	/**
	 * Returns argument passed after the first '--'.
	 *
	 * @return null|mixed
	 */
	public function getAnonymousAt(int $index): mixed
	{
		return $this->anonymous[$index] ?? null;
	}

	/**
	 * Gets all named args.
	 */
	public function getNamedArgs(): array
	{
		return $this->named;
	}

	/**
	 * Gets all args passed after the first '--'.
	 */
	public function getAnonymousArgs(): array
	{
		return $this->anonymous;
	}
}
