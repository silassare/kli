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

use Kli\Exceptions\KliException;

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
	 *
	 * @param KliAction $action
	 * @param array     $named
	 * @param array     $anonymous
	 */
	public function __construct(KliAction $action, array $named, array $anonymous)
	{
		$this->anonymous = $anonymous;
		$this->named     = $named;
		$this->action    = $action;
	}

	/**
	 * Gets a named arg value.
	 *
	 * @param string $name name or alias
	 *
	 * @return null|mixed
	 *
	 * @throws KliException
	 */
	public function get(string $name)
	{
		$name = $this->action->getOption($name)
			->getName();

		return $this->named[$name] ?? null;
	}

	/**
	 * Returns argument passed after the first '--'.
	 *
	 * @param int $index
	 *
	 * @return null|mixed
	 */
	public function getAnonymousAt(int $index)
	{
		return $this->anonymous[$index] ?? null;
	}

	/**
	 * Gets all named args.
	 *
	 * @return array
	 */
	public function getNamedArgs(): array
	{
		return $this->named;
	}

	/**
	 * Gets all args passed after the first '--'.
	 *
	 * @return array
	 */
	public function getAnonymousArgs(): array
	{
		return $this->anonymous;
	}
}
