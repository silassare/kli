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

	final class KliAction
	{
		private $name;
		private $description  = 'no description';
		private $options      = [];
		private $offsets_lock = [];

		/**
		 * KliAction constructor.
		 *
		 * @param string $name action name
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function __construct($name)
		{
			if (!is_string($name) OR !preg_match("/^[a-zA-Z0-9](?:[a-zA-Z0-9-_]+)$/", $name)) throw new KliException(sprintf('%s is not a valid action name.', $name));

			$this->name = $name;
		}

		/**
		 * add option to this action.
		 *
		 * @param \Kli\KliOption $option the option to be added
		 *
		 * @return \Kli\KliAction
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function addOption(KliOption $option)
		{
			$opt_name = $option->getName();

			if (isset($this->options[$opt_name])) {
				throw new KliException(sprintf('option "-%s" is already defined in action "%s".', $opt_name, $this->getName()));
			}

			$offsets = $option->getOffsets();

			if (!empty($offsets)) {
				$a = $offsets[0];
				$b = $offsets[1];

				foreach ($this->offsets_lock as $locker => $lock) {
					$c  = $lock[0];
					$d  = $lock[1];
					$ok = ($a > $d OR $b < $c);// some math lol

					if (!$ok) {
						throw new KliException(sprintf('all or parts of offsets(%s,%s) is used by option "-%s" of action "%s".', $a, $b, $locker, $this->getName()));
					}
				}

				// lock offsets
				$this->offsets_lock[$opt_name] = $offsets;
			}

			$this->options[$opt_name] = $option->lock();

			return $this;
		}

		/**
		 * define this action description.
		 *
		 * @param string $description action description
		 *
		 * @return \Kli\KliAction
		 */
		public function description($description)
		{
			$this->description = trim($description);

			return $this;
		}

		/**
		 * action description getter.
		 *
		 * @return string
		 */
		public function getDescription()
		{
			return $this->description;
		}

		/**
		 * action name getter.
		 *
		 * @return string
		 */
		public function getName()
		{
			return $this->name;
		}

		/**
		 * does this action has a given option.
		 *
		 * @param string $opt_name the option name
		 *
		 * @return bool
		 */
		public function hasOption($opt_name)
		{
			return (is_string($opt_name) AND isset($this->options[$opt_name]));
		}

		/**
		 * get option.
		 *
		 * @param string $opt_name the option name
		 *
		 * @return \Kli\KliOption
		 *
		 * @throws \Kli\Exceptions\KliException    when the option is not defined for this action
		 */
		public function getOption($opt_name)
		{
			if (!isset($this->options[$opt_name])) {
				throw new KliException(sprintf('"%s" - unrecognized option: "%s"', $this->getName(), $opt_name));
			}

			return $this->options[$opt_name];
		}

		/**
		 * get this action options list.
		 *
		 * @return array
		 */
		public function getOptions()
		{
			return $this->options;
		}

		/**
		 * action to string routine used as help.
		 *
		 * @return string
		 */
		public function __toString()
		{
			$text = $this->getName();
			$text .= PHP_EOL . KliUtils::indent($this->getDescription(), 4);
			$sep  = PHP_EOL . PHP_EOL;
			$text .= $sep . implode($sep, $this->options);

			return $text;
		}
	}