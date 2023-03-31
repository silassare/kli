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
 * Class KliCommand.
 */
abstract class KliCommand
{
	private string $name;

	private string $description = '';

	private Kli $cli;

	/**
	 * @var \Kli\KliAction[]
	 */
	private array $actions     = [];

	/**
	 * KliCommand constructor.
	 *
	 * @param string   $name command name
	 * @param \Kli\Kli $cli  cli object to use
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	protected function __construct(string $name, Kli $cli)
	{
		if (!\preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-_]+$/', $name)) {
			throw new KliException(\sprintf('"%s" is not a valid command name.', $name));
		}

		$this->name = $name;
		$this->cli  = $cli;
	}

	/**
	 * Command to string routine used as help.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$text = '█ ' . $this->getName();
		$text .= \PHP_EOL . KliUtils::indent($this->getDescription(), 1, '█ ');
		$sep  = \PHP_EOL . \PHP_EOL . '  > ' . $this->getName() . ' ';
		$text .= $sep . \implode($sep, $this->actions);

		return $text;
	}

	/**
	 * Executes command.
	 *
	 * @param \Kli\KliAction $action requested action object
	 * @param \Kli\KliArgs   $args   the args object
	 */
	abstract public function execute(KliAction $action, KliArgs $args): void;

	/**
	 * Adds action(s) to this command.
	 *
	 * @param \Kli\KliAction $action action object
	 *
	 * @return $this
	 *
	 * @throws \Kli\Exceptions\KliException when action is already defined
	 */
	public function addAction(KliAction $action): self
	{
		/**
		 * @var \Kli\KliAction[] $actions
		 */
		$actions = \func_get_args();

		foreach ($actions as $a) {
			$act_name = $a->getName();

			if (isset($this->actions[$act_name])) {
				throw new KliException(\sprintf('action "%s" is already defined.', $act_name));
			}

			$this->actions[$act_name] = $a;
		}

		return $this;
	}

	/**
	 * Define this command description.
	 *
	 * @param string $description command description
	 *
	 * @return $this
	 */
	public function description(string $description): self
	{
		$this->description = \trim($description);

		return $this;
	}

	/**
	 * Does this command has a given action.
	 *
	 * @param string $name the action name
	 *
	 * @return bool
	 */
	public function hasAction(string $name): bool
	{
		return isset($this->actions[$name]);
	}

	/**
	 * Gets action with a given name.
	 *
	 * @param string $name the action name
	 *
	 * @return \Kli\KliAction
	 *
	 * @throws \Kli\Exceptions\KliException when the action is not defined for this command
	 */
	public function getAction(string $name): KliAction
	{
		if (!isset($this->actions[$name])) {
			throw new KliException(\sprintf('"%s" - unrecognized action: "%s"', $this->getName(), $name));
		}

		return $this->actions[$name];
	}

	/**
	 * Command name getter.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Gets this command actions list.
	 *
	 * @return \Kli\KliAction[]
	 */
	public function getActions(): array
	{
		return $this->actions;
	}

	/**
	 * Command cli getter.
	 *
	 * @return \Kli\Kli
	 */
	public function getCli(): Kli
	{
		return $this->cli;
	}

	/**
	 * Command description getter.
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->description;
	}
}
