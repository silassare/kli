<?php

/**
 * Copyright (c) 2017-present, Emile Silas Sare
 *
 * This file is part of Kli package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kli;

use Kli\Exceptions\KliException;

abstract class KliCommand
{
	private $name;

	private $description = '';

	private $cli;

	private $actions     = [];

	/**
	 * KliCommand constructor.
	 *
	 * @param string   $name command name
	 * @param \Kli\Kli $cli  cli object to use
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	protected function __construct($name, Kli $cli)
	{
		if (!\preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-_]+$/', $name)) {
			throw new KliException(\sprintf('"%s" is not a valid command name.', $name));
		}

		$this->name = $name;
		$this->cli  = $cli;
	}

	/**
	 * Executes command.
	 *
	 * @param \Kli\KliAction $action            requested action object
	 * @param array          $options           key value pairs options
	 * @param array          $anonymous_options indexed unused anonymous options
	 */
	abstract public function execute(KliAction $action, array $options, array $anonymous_options);

	/**
	 * Adds action(s) to this command.
	 *
	 * @param \Kli\KliAction $action action object
	 *
	 * @throws \Kli\Exceptions\KliException when action is already defined
	 *
	 * @return \Kli\KliCommand
	 */
	public function addAction(KliAction $action)
	{
		/**
		 * @var \Kli\KliAction[]
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
	 * @return \Kli\KliCommand
	 */
	public function description($description)
	{
		$this->description = \trim($description);

		return $this;
	}

	/**
	 * Doe this command has a given action.
	 *
	 * @param string $name the action name
	 *
	 * @return bool
	 */
	public function hasAction($name)
	{
		return \is_string($name) && isset($this->actions[$name]);
	}

	/**
	 * Gets action with a given name.
	 *
	 * @param string $name the action name
	 *
	 * @throws \Kli\Exceptions\KliException when the action is not defined for this command
	 *
	 * @return \Kli\KliAction
	 */
	public function getAction($name)
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
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Gets this command actions list.
	 *
	 * @return array
	 */
	public function getActions()
	{
		return $this->actions;
	}

	/**
	 * Command cli getter.
	 *
	 * @return \Kli\Kli
	 */
	public function getCli()
	{
		return $this->cli;
	}

	/**
	 * Command description getter.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
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
}
