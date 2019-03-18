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
		private $used_aliases = [];

		/**
		 * KliAction constructor.
		 *
		 * @param string $name action name
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function __construct($name)
		{
			if (!is_string($name) OR !preg_match("/^[a-zA-Z0-9](?:[a-zA-Z0-9-_]+)$/", $name)) throw new KliException(sprintf('"%s" is not a valid action name.', $name));

			$this->name = $name;
		}

		/**
		 * Adds option(s) to this action.
		 *
		 * @param \Kli\KliOption $option the option to be added
		 *
		 * @return \Kli\KliAction
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function addOption(KliOption $option)
		{
			/** @var \Kli\KliOption[] $options */
			$options = func_get_args();
			foreach ($options as $o) {
				$opt_name = $o->getName();

				if (isset($this->options[$opt_name])) {
					throw new KliException(sprintf('option "-%s" is already defined in action "%s".', $opt_name, $this->getName()));
				}

				$aliases = $o->getAliases();

				foreach ($aliases as $alias) {
					if (array_key_exists($alias, $this->used_aliases)) {
						throw new KliException(sprintf('alias "--%s" is already defined for option "-%s" in action "%s"', $alias, $this->used_aliases[$alias], $this->name));
					}

					$this->used_aliases[$alias] = $opt_name;
				}

				$offsets = $o->getOffsets();

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

				$this->options[$opt_name] = $o->lock();
			}

			return $this;
		}

		/**
		 * Action name getter.
		 *
		 * @return string
		 */
		public function getName()
		{
			return $this->name;
		}

		/**
		 * Define this action description.
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
		 * Does this action has a given option.
		 *
		 * @param string $name the option name
		 *
		 * @return bool
		 */
		public function hasOption($name)
		{
			return (is_string($name) AND isset($this->options[$name]));
		}

		/**
		 * Gets option with a given name.
		 *
		 * @param string $name the option name
		 *
		 * @return \Kli\KliOption
		 *
		 * @throws \Kli\Exceptions\KliException    when the option is not defined for this action
		 */
		public function getOption($name)
		{
			if (!isset($this->options[$name])) {
				throw new KliException(sprintf('"%s" - unrecognized option: "%s"', $this->getName(), $name));
			}

			return $this->options[$name];
		}

		/**
		 * Gets this action options list.
		 *
		 * @return array
		 */
		public function getOptions()
		{
			return $this->options;
		}

		/**
		 * Gets option name for a specific alias.
		 *
		 * @param string $alias
		 *
		 * @return null|string
		 */
		public function getRealName($alias)
		{
			if (isset($this->used_aliases[$alias])) {
				return $this->used_aliases[$alias];
			}

			return null;
		}

		/**
		 * Action to string routine used as help.
		 *
		 * @return string
		 */
		public function __toString()
		{
			$text = $this->getName() . ' [options]';
			$text .= PHP_EOL . KliUtils::indent($this->getDescription(), 4);
			$sep  = PHP_EOL . PHP_EOL;
			$text .= $sep . implode($sep, $this->options);

			return $text;
		}

		/**
		 * Action description getter.
		 *
		 * @return string
		 */
		public function getDescription()
		{
			return $this->description;
		}
	}