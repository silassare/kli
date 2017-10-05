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

	use Kli\Types\KliType;
	use Kli\Exceptions\KliException;

	final class KliOption
	{
		private $name;
		private $description         = 'no description';
		private $prompt              = false;
		private $prompt_msg;
		private $prompt_for_password = false;
		private $aliases             = [];
		private $required            = false;
		private $type;
		private $default             = null;
		private $has_default         = false;
		private $locked              = false;
		private $offsets             = null;

		/**
		 * KliOption constructor.
		 *
		 * @param string $name option name
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function __construct($name)
		{
			if (!is_string($name) OR !preg_match("/^[a-zA-Z0-9?]$/", $name)) throw new KliException(sprintf('"%s" is not a valid option name.', $name));

			$this->name = $name;
		}

		/**
		 * Adds option alias.
		 *
		 * @param string $alias option alias to add
		 *
		 * @return \Kli\KliOption
		 * @throws \Kli\Exceptions\KliException
		 */
		public function alias($alias)
		{
			if (!is_string($alias) OR !preg_match("/^[a-zA-Z0-9][a-zA-Z0-9-_]+$/", $alias)) throw new KliException(sprintf('"%s" is not a valid alias.', $alias));

			if (!in_array($alias, $this->aliases)) {
				$this->aliases[] = $alias;
			}

			return $this;
		}

		/**
		 * Define argument offset to use as own anonymous value.
		 *
		 * when $to is set, any anonymous argument in offset range($at,$to) will be used.
		 * NOTE: $to could be set to infinity (INF)
		 *
		 * @param int      $at
		 * @param null|int $to
		 *
		 * @return \Kli\KliOption
		 *
		 * @throws \Kli\Exceptions\KliException
		 */
		public function offsets($at, $to = null)
		{
			if ($this->locked) {
				throw new KliException("can't define offsets, option is locked.");
			}

			if (!is_int($at) OR $at < 0) {
				throw new KliException(sprintf('"%s" is not a valid arg offset.', $at));
			}

			if (isset($to) AND ((!is_int($to) AND !is_infinite($to)) OR $to < $at)) {
				throw new KliException(sprintf('from=%s to=%s is not a valid arg offset range.', $at, $to));
			}

			$range = [$at, (isset($to) ? $to : $at)];

			$this->offsets = $range;

			return $this;
		}

		/**
		 * Lock the option.
		 *
		 * options are locked when added to an action.
		 *
		 * @return \Kli\KliOption
		 */
		public function lock()
		{
			$this->locked = true;

			return $this;
		}

		/**
		 * Explicitly set the default value.
		 *
		 * @param mixed $value the value to use as default
		 *
		 * @return \Kli\KliOption
		 */
		public function def($value)
		{
			// the default should comply with all rules or not ?
			$this->default     = $value;
			$this->has_default = true;

			return $this;
		}

		/**
		 * Define this option description.
		 *
		 * @param string $description option description
		 *
		 * @return \Kli\KliOption
		 */
		public function description($description)
		{
			$this->description = trim($description);

			return $this;
		}

		/**
		 * Set this option value type.
		 *
		 * @param \Kli\Types\KliType $type the type of the value
		 *
		 * @return \Kli\KliOption
		 */
		public function type(KliType $type)
		{
			$this->type = $type;

			return $this;
		}

		/**
		 * Marks this option as required.
		 *
		 * @return \Kli\KliOption
		 */
		public function required()
		{
			$this->required = true;

			return $this;
		}

		/**
		 * Define the prompt capability and even prompt message.
		 *
		 * @param bool   $prompt              prompt enable/disable
		 * @param string $prompt_msg          prompt message
		 * @param bool   $prompt_for_password prompt is for password
		 *
		 * @return \Kli\KliOption
		 * @throws \Kli\Exceptions\KliException
		 */
		public function prompt($prompt = true, $prompt_msg = null, $prompt_for_password = false)
		{
			if ($prompt AND isset($prompt_msg)) {
				if (is_string($prompt_msg) AND strlen(trim($prompt_msg))) {
					$this->prompt_msg = trim($prompt_msg);
				} else {
					throw new KliException(sprintf('the prompt for "-%s" should be a string.', $this->getName()));
				}
			}

			$this->prompt              = $prompt;
			$this->prompt_for_password = $prompt_for_password;

			return $this;
		}

		/**
		 * Does this option enable prompt.
		 *
		 * @return bool
		 */
		public function promptEnabled()
		{
			return $this->prompt;
		}

		/**
		 * does this option prompt is for password.
		 *
		 * @return bool
		 */
		public function promptForPassword()
		{
			return $this->prompt_for_password;
		}

		/**
		 * Option prompt message getter.
		 *
		 * @return string
		 */
		public function getPrompt()
		{
			if (!isset($this->prompt_msg)) {
				return sprintf('Please provide -%s', $this->getName());
			}

			return $this->prompt_msg;
		}

		/**
		 * Option name getter.
		 *
		 * @return string
		 */
		public function getName()
		{
			return $this->name;
		}

		/**
		 * option default value getter.
		 *
		 * @return mixed
		 */
		public function getDefault()
		{
			return $this->default;
		}

		/**
		 * Option offsets value getter.
		 *
		 * @return null|array
		 */
		public function getOffsets()
		{
			return $this->offsets;
		}

		/**
		 * Does the default value was explicitly set.
		 *
		 * @return bool
		 */
		public function hasDefault()
		{
			return $this->has_default;
		}

		/**
		 * Does this option is required or not.
		 *
		 * @return bool
		 */
		public function isRequired()
		{
			return $this->required;
		}

		/**
		 * Option type getter.
		 *
		 * @return \Kli\Types\KliType
		 */
		public function getType()
		{
			return $this->type;
		}

		/**
		 * Option aliases getter.
		 *
		 * @return array
		 */
		public function getAliases()
		{
			return $this->aliases;
		}

		/**
		 * Option description getter.
		 *
		 * @return string
		 */
		public function getDescription()
		{
			return $this->description;
		}

		/**
		 * Option to string routine used as help.
		 *
		 * @return string
		 */
		public function __toString()
		{
			$text = '-' . $this->getName();

			if (count($this->aliases)) {
				$text .= ' , --' . implode(' --', $this->aliases);
			}

			$text .= "\t".$this->getDescription();

			return KliUtils::indent($text, 6);;
		}
	}