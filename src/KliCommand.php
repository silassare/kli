<?php
	/**
	 * Copyright (c) Emile Silas Sare <emile.silas@gmail.com>
	 *
	 * This file is part of the Kli package.
	 *
	 * For the full copyright and license information, please view the LICENSE
	 * file that was distributed with this source code.
	 */

	namespace Kli;

	use Kli\Exceptions\KliException;

	abstract class KliCommand
	{
		private $name;
		private $description = "";
		private $cli;
		private $actions     = [];

		/**
		 * KliCommand constructor.
		 *
		 * @param string            $name command name.
		 * @param \Kli\Kli $cli  cli object to use.
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		protected function __construct($name, Kli $cli)
		{
			if (!preg_match("/^[a-zA-Z0-9][a-zA-Z0-9-_]+$/", $name)) throw new KliException(sprintf('"%s" is not a valid command name.', $name));

			$this->name = $name;
			$this->cli  = $cli;
		}

		/**
		 * execute command.
		 *
		 * @param \Kli\KliAction $action            requested action object.
		 * @param array                   $options           key value pairs options.
		 * @param array                   $anonymous_options indexed unused anonymous options.
		 */
		abstract public function execute(KliAction $action, array $options, array $anonymous_options);

		/**
		 * add action to command.
		 *
		 * @param \Kli\KliAction $action action object.
		 *
		 * @return \Kli\KliCommand
		 *
		 * @throws \Kli\Exceptions\KliException    when action is already defined
		 */
		public function addAction(KliAction $action)
		{
			$act_name = $action->getName();

			if (isset($this->actions[$act_name])) {
				throw new KliException(sprintf('action "%s" is already defined.', $act_name));
			}

			$this->actions[$act_name] = $action;

			return $this;
		}

		/**
		 * define this command description.
		 *
		 * @param string $description command description
		 *
		 * @return \Kli\KliCommand
		 */
		public function description($description)
		{
			$this->description = trim($description);

			return $this;
		}

		/**
		 * does this command has a given action.
		 *
		 * @param string $act_name the action name
		 *
		 * @return bool
		 */
		public function hasAction($act_name)
		{
			return (is_string($act_name) AND isset($this->actions[$act_name]));
		}

		/**
		 * get action.
		 *
		 * @param string $act_name the action name
		 *
		 * @return \Kli\KliAction
		 *
		 * @throws \Kli\Exceptions\KliException    when the action is not defined for this command
		 */
		public function getAction($act_name)
		{
			if (!isset($this->actions[$act_name])) {
				throw new KliException(sprintf('"%s" - unrecognized action: "%s"', $this->getName(), $act_name));
			}

			return $this->actions[$act_name];
		}

		/**
		 * get this command actions list.
		 *
		 * @return array
		 */
		public function getActions()
		{
			return $this->actions;
		}

		/**
		 * command description getter.
		 *
		 * @return string
		 */
		public function getDescription()
		{
			return $this->description;
		}

		/**
		 * command cli getter.
		 *
		 * @return \Kli\Kli
		 */
		public function getCli()
		{
			return $this->cli;
		}

		/**
		 * command name getter.
		 *
		 * @return string
		 */
		public function getName()
		{
			return $this->name;
		}

		/**
		 * command to string routine used as help.
		 *
		 * @return string
		 */
		public function __toString()
		{
			$text = $this->getName();
			$text .= PHP_EOL . KliUtils::indent($this->getDescription(), 2);
			$sep  = PHP_EOL . PHP_EOL . '  ' . $this->getName() . ' ';
			$text .= $sep . implode($sep . ' ', $this->actions);

			return $text;
		}
	}