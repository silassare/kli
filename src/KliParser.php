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

	use Kli\Exceptions\KliInputException;

	final class KliParser
	{
		private $cli;

		/**
		 * KliParser constructor.
		 *
		 * @param \Kli\Kli $cli the cli object to use
		 */
		public function __construct(Kli $cli)
		{
			$this->cli = $cli;
		}

		/**
		 * Parse command arg list for a specific action.
		 *
		 * @param \Kli\KliAction $action   action object.
		 * @param array          $opt_list option list.
		 *
		 * @return array
		 *
		 * @throws \Kli\Exceptions\KliInputException
		 */
		public function parse(KliAction $action, array $opt_list)
		{
			$in_options   = [];
			$anonymous    = [];
			$stop_parsing = false;

			foreach ($opt_list as $item) {
				if ($stop_parsing) {
					$anonymous[] = $item;
				} /* tmp the first -- and stop parsing */ elseif ($item == '--') {
					$stop_parsing = true;
					continue;
				} /* alias first */ elseif (substr($item, 0, 2) == '--') {
					$pos   = strpos($item, '=');
					$name  = substr($item, 2);
					$value = true;

					if (is_int($pos)) {
						// valid: --abc=value
						// invalid: --a=value
						if ($pos > 3) {
							$name  = substr($item, 2, $pos - 2);
							$value = substr($item, $pos + 1);
						} else {
							throw new KliInputException(sprintf('invalid option: "%s"', substr($item, 0, $pos)));
						}
					}

					$name              = $this->checkOption($action, $name, true);
					$in_options[$name] = $value;
				} /* short after */ elseif (substr($item, 0, 1) == '-') {
					$pos = strpos($item, '=');

					if (is_int($pos)) {
						// valid: -a=value
						// invalid: -ab=value
						if ($pos === 2) {
							$name              = $this->checkOption($action, substr($item, 1, 1));
							$value             = substr($item, 3);
							$in_options[$name] = $value;
						} else {
							throw new KliInputException(sprintf('invalid option: "%s"', substr($item, 0, $pos)));
						}
					} else {
						$flags = substr($item, 1);
						// single flag: -a
						// combined flag: -abcdef
						if (strlen($flags)) {
							$flags = str_split($flags);
							foreach ($flags as $flag) {
								$flag              = $this->checkOption($action, $flag);
								$in_options[$flag] = true;
							}
						} else {
							throw new KliInputException(sprintf('invalid option: "%s"', $item));
						}
					}
				} else {
					$anonymous[] = $item;
				}
			}

			if (count($anonymous)) {
				$o_list = $action->getOptions();

				/** @var \Kli\KliOption $opt */
				foreach ($o_list as $opt) {
					$opt_name = $opt->getName();

					if (!isset($in_options[$opt_name])) {
						$offsets = $opt->getOffsets();

						if (is_array($offsets)) {
							$at = $offsets[0];
							$to = $offsets[1];

							if ($at === $to) {
								if (isset($anonymous[$at])) {
									$in_options[$opt_name] = $anonymous[$at];
									unset($anonymous[$at]);
								}
							} else {
								while (isset($anonymous[$at]) AND $at <= $to) {
									$in_options[$opt_name][] = $anonymous[$at];
									$at++;
									unset($anonymous[$at]);
								}
							}
						}
					}
				}
			}

			$this->validateOptions($action, $in_options);

			return ['anonymous' => $anonymous, 'options' => $in_options];
		}

		/**
		 * Checks if a given action contains a given option.
		 *
		 * @param \Kli\KliAction $action   action object.
		 * @param string         $opt_name option name defined by user.
		 * @param bool           $is_alias is it an alias.
		 *
		 * @return string                  option short name.
		 *
		 * @throws \Kli\Exceptions\KliInputException
		 */
		private function checkOption(KliAction $action, $opt_name, $is_alias = false)
		{
			if ($is_alias) {
				$real_name = $action->getRealName($opt_name);

				if (empty($real_name)) {
					throw new KliInputException(sprintf('unrecognized option "%s" for action "%s"', $opt_name, $action->getName()));
				}

				$opt_name = $real_name;
			} elseif (!$action->hasOption($opt_name)) {
				throw new KliInputException(sprintf('unrecognized option "%s" for action "%s"', $opt_name, $action->getName()));
			}

			return $opt_name;
		}

		/**
		 * Validate a list of parsed options for a given action.
		 *
		 * prompt user to define value: when an option is missing
		 * and prompt is enabled for that option
		 *
		 * @param \Kli\KliAction  $action         action object.
		 * @param array          &$parsed_options parsed options.
		 *
		 * @throws \Kli\Exceptions\KliInputException
		 */
		private function validateOptions(KliAction $action, array &$parsed_options)
		{
			$options = $action->getOptions();

			/** @var \Kli\KliOption $option */
			foreach ($options as $index => $option) {
				$value    = null;
				$opt_name = $option->getName();

				if (isset($parsed_options[$opt_name])) {
					$value = $option->getType()
									->validate($opt_name, $parsed_options[$opt_name]);
				} elseif ($option->isRequired()) {
					/* first tentative */
					if ($option->promptEnabled()) {
						$value = $this->interactivePrompt($option, $option->getDefault());
					} /* second tentative */ elseif ($option->hasDefault()) {
						$value = $option->getDefault();
					}

					if ($value === null) {
						throw new KliInputException(sprintf('"%s" require option: -%s', $action->getName(), $opt_name));
					}
				} else {
					$value = $option->getDefault();
				}

				$parsed_options[$opt_name] = $value;
			}
		}

		/**
		 * Prompt user to provide value for a given option.
		 *
		 * @param \Kli\KliOption $option  option object.
		 * @param mixed          $default default value.
		 *
		 * @return mixed
		 */
		public function interactivePrompt(KliOption $option, $default = null)
		{
			$prompt = $option->getPrompt();

			if (!empty($default)) {
				$prompt = sprintf('%s (%s): ', $prompt, $default);
			} else {
				$prompt = sprintf('%s: ', $prompt);
			}

			$in = null;

			while (empty($in)) {
				$in = $this->cli->readLine($prompt, $option->promptForPassword());

				if (empty($in)) {
					$in = $default;
				} else {
					try {
						$in = $option->getType()
									 ->validate($option->getName(), $in);
					} catch (KliInputException $e) {
						$in = null;
						$this->cli->error($e->getMessage());
					}
				}
			}

			return $in;
		}
	}
