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

	class Kli
	{
		private $enable_interactive = false;
		private $is_interactive     = false;
		private $commands           = [];
		private $title              = '';

		/**
		 * Kli constructor.
		 *
		 * @param string $title              the cli title to be used in interactive mode.
		 * @param bool   $enable_interactive to enable interactive cli.
		 */
		public function __construct($title = '', $enable_interactive = false)
		{
			global $argv;

			if (empty($title)) {
				$title = basename($argv[0]);
			}

			$this->enable_interactive = $enable_interactive;
			$this->setTitle($title);
		}

		/**
		 * Executes a command.
		 *
		 * @param array $_argv the command argv like array.
		 */
		final public function execute(array $_argv)
		{
			try {
				$c = count($_argv);

				if ($c <= 1) { // $ cli

					if ($this->enable_interactive) $this->interactiveMode(); else
						$this->showHelp();
				} elseif ($this->isHelp($_argv[1])) { // $ cli --help
					$this->showHelp();
				} elseif ($this->hasCommand($_argv[1])) { // $ cli command
					$a1  = $_argv[1];
					$cmd = $this->commands[$a1];

					if (isset($_argv[2])) {
						$a2 = $_argv[2];

						if ($this->isHelp($a2)) { // $ cli command --help

							$this->showHelp($a1);
						} elseif ($cmd->hasAction($a2)) {
							$action = $cmd->getAction($a2);

							if (isset($_argv[3]) AND $this->isHelp($_argv[3])) {
								// $ cli command action --help
								$this->showHelp($a1, $a2);
							} else { // $ cli command action [options]
								$opt_list = array_slice($_argv, 2);
								$parser   = new KliParser($this);
								$result   = $parser->parse($action, $opt_list);

								$cmd->execute($action, $result['options'], $result['anonymous']);
							}
						} else {
							$this->writeLn(sprintf('%s: action "%s" not recognized.', $a1, $a2));
						}
					} else {
						$action_list = implode(' , ', array_keys($cmd->getActions()));
						$this->writeLn(sprintf('actions available for the command "%s": %s', $a1, $action_list));
					}
				} else {
					$this->writeLn(sprintf('error: command "%s" not recognized.', $_argv[1]));
				}

				if (!$this->is_interactive) {
					$this->writeLn();
				}
			} catch (KliInputException $e) {
				$this->writeLn($e->getMessage())
					 ->writeLn();
			}
		}

		/**
		 * Enable interactive mode.
		 */
		public function interactiveMode()
		{
			global $argv;
			if (!$this->is_interactive) {
				$this->is_interactive = true;
				$this->welcome();
				$this->writeLn('Hint: type "quit" or "exit" to stop.' . PHP_EOL);

				while ($this->is_interactive) {
					$in = $this->readLine(sprintf('%s> ', $this->getTitle()));

					if (strlen($in)) {
						if ($in == 'quit' OR $in == 'exit') {
							$this->quit();
						} else {
							// construct command: exactly as if it was fully typed
							$absolute_cmd = $argv[0] . ' ' . $in;
							static::execute(KliUtils::stringToArgv($absolute_cmd));
						}
					}

					$this->writeLn();
				}
			}
		}

		/**
		 * Write welcome message.
		 *
		 * It is called once in interactive mode.
		 * Or when help is requested.
		 */
		public function welcome()
		{
			// silence is gold
		}

		/**
		 * Write string on a new line.
		 *
		 * @param string $str  the string to write
		 * @param bool   $wrap to wrap string or not
		 *
		 * @return \Kli\Kli
		 */
		public function writeLn($str = '', $wrap = true)
		{
			echo PHP_EOL . (($wrap AND strlen($str) > 80) ? KliUtils::wrap($str) : $str);

			return $this;
		}

		/**
		 * Read data from user input.
		 *
		 * @param string $prompt      the prompt string
		 * @param bool   $is_password should we hide user input
		 *
		 * @return string            user input
		 */
		public function readLine($prompt, $is_password = false)
		{
			if ($is_password) {
				$this->writeLn($prompt);
				$line = $this->readPass();
			} elseif (function_exists('readline_add_history')) {
				$this->writeLn();
				$line = readline($prompt);
				readline_add_history($line);
			} else {
				$this->writeLn($prompt);
				$line = fgets(STDIN);
			}

			return trim($line);
		}

		/**
		 * Read password.
		 *
		 * @return string user input
		 */
		protected function readPass()
		{
			return `stty -echo; head -n1; stty echo`;
		}

		/**
		 * Title getter.
		 *
		 * @return string
		 */
		public function getTitle()
		{
			return $this->title;
		}

		/**
		 * Title setter.
		 *
		 * @param string $title the cli title
		 *
		 * @return \Kli\Kli
		 */
		public function setTitle($title)
		{
			$this->title = $title;

			return $this;
		}

		/**
		 * Quit Kli.
		 */
		public function quit()
		{
			$this->is_interactive = false;
			$this->writeLn();// silence is gold
		}

		/**
		 * Show the help.
		 *
		 * @param string|null $cmd_name the command name
		 * @param string|null $act_name the action name
		 */
		public function showHelp($cmd_name = null, $act_name = null)
		{
			global $argv;

			if (!$this->is_interactive) {
				$this->welcome();
			}
			$head = basename($argv[0]);
			$h    = PHP_EOL . "Usage:"
					. PHP_EOL . "  > $head command action [options]"
					. PHP_EOL . "For interactive mode."
					. PHP_EOL . "  > $head"
					. PHP_EOL . "To show help message."
					. PHP_EOL . "  > $head [command [action]] -? or --help"
					. PHP_EOL . PHP_EOL;

			if (isset($cmd_name) AND $this->hasCommand($cmd_name)) {
				$cmd = $this->commands[$cmd_name];

				if (isset($act_name) AND $cmd->hasAction($act_name)) {
					$h .= sprintf('  %s %s', $cmd->getName(), $cmd->getAction($act_name));
				} else {
					$h .= $cmd;
				}
			} else {
				$h .= implode(PHP_EOL . PHP_EOL, $this->commands) . PHP_EOL;
			}

			$this->writeLn($h, false);
		}

		/**
		 * Checks if this cli has a given command.
		 *
		 * @param string $cmd_name the command name
		 *
		 * @return bool
		 */
		public function hasCommand($cmd_name)
		{
			return (is_string($cmd_name) AND isset($this->commands[$cmd_name]));
		}

		/**
		 * Checks if string is a help flag.
		 *
		 * @param string $str the string to check
		 *
		 * @return bool
		 */
		public function isHelp($str)
		{
			return ($str === '--help' OR $str === '-?');
		}

		/**
		 * Adds command to cli.
		 *
		 * @param \Kli\KliCommand $cmd the command to add
		 *
		 * @return \Kli\Kli
		 */
		public function addCommand(KliCommand $cmd)
		{
			$this->commands[$cmd->getName()] = $cmd;

			return $this;
		}

		/**
		 * Write string.
		 *
		 * @param string $str  the string to write
		 * @param bool   $wrap to wrap string or not
		 *
		 * @return \Kli\Kli
		 */
		public function write($str, $wrap = false)
		{
			echo $wrap ? KliUtils::wrap($str) : $str;

			return $this;
		}

		/**
		 * Creates log file or append to existing.
		 *
		 * @param mixed $log
		 *
		 * @return $this
		 */
		public function log($log)
		{
			$content = is_scalar($log) ? (string)$log : var_export($log, true);

			file_put_contents($content, getcwd() . DIRECTORY_SEPARATOR . "kli.log", FILE_APPEND);

			return $this;
		}
	}