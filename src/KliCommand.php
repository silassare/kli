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

use Kli\Exceptions\KliRuntimeException;

/**
 * Class KliCommand.
 */
class KliCommand
{
	public const NAME_REG = '~^[a-zA-Z0-9][a-zA-Z0-9-_]+$~';

	private string $name;

	private string $description = '';

	/**
	 * @var null|callable(KliAction, KliArgs): void
	 */
	private $fallback_handler;

	private Kli $cli;

	/**
	 * @var KliAction[]
	 */
	private array $actions = [];

	/**
	 * KliCommand constructor.
	 *
	 * @param string $name command name
	 * @param Kli    $cli  cli object to use
	 */
	public function __construct(string $name, Kli $cli)
	{
		if (!\preg_match(self::NAME_REG, $name)) {
			throw new KliRuntimeException(\sprintf('"%s" is not a valid command name.', $name));
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
	 * @param KliAction $action requested action object
	 * @param KliArgs   $args   the args object
	 */
	public function execute(KliAction $action, KliArgs $args): void
	{
		if (!$this->fallback_handler) {
			throw new KliRuntimeException('No handler defined for this command.');
		}

		($this->fallback_handler)($action, $args);
	}

	/**
	 * Set a handler for this command.
	 *
	 * @param callable(KliAction, KliArgs): void $handler
	 *
	 * @return $this
	 */
	public function handler(callable $handler): self
	{
		$this->fallback_handler = $handler;

		return $this;
	}

	/**
	 * Creates a new action.
	 *
	 * @param string $name
	 * @param string $description
	 *
	 * @return KliAction
	 */
	public function action(string $name, string $description = ''): KliAction
	{
		$action = new KliAction($name);

		$description && $action->description($description);

		$this->addAction($action);

		return $action;
	}

	/**
	 * Adds action(s) to this command.
	 *
	 * @param KliAction $action action object
	 *
	 * @return $this
	 */
	public function addAction(KliAction $action): self
	{
		/**
		 * @var KliAction[] $actions
		 */
		$actions = \func_get_args();

		foreach ($actions as $a) {
			$act_name = $a->getName();

			if (isset($this->actions[$act_name])) {
				throw new KliRuntimeException(\sprintf('action "%s" is already defined.', $act_name));
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
	 * @return KliAction
	 */
	public function getAction(string $name): KliAction
	{
		if (!isset($this->actions[$name])) {
			throw new KliRuntimeException(\sprintf('%s: unknown action "%s"', $this->getName(), $name));
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
	 * @return KliAction[]
	 */
	public function getActions(): array
	{
		return $this->actions;
	}

	/**
	 * Command cli getter.
	 *
	 * @return Kli
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
