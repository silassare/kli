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
use Kli\Exceptions\KliInputException;
use Kli\Table\KliTable;

/**
 * Class Kli.
 */
class Kli
{
	private bool $enable_interactive;

	private bool $is_interactive = false;

	private ?string $log_file;

	/**
	 * @var KliCommand[]
	 */
	private array $commands = [];

	private string $title = '';

	/**
	 * Kli constructor.
	 *
	 * @param string      $title              the cli title to be used in interactive mode
	 * @param bool        $enable_interactive to enable interactive cli
	 * @param null|string $log_file           path to log file
	 */
	public function __construct(string $title = '', bool $enable_interactive = false, ?string $log_file = null)
	{
		if (empty($title)) {
			$title = \basename($this->getCliEntryPoint());
		}

		$this->enable_interactive = $enable_interactive;
		$this->log_file           = $log_file;
		$this->setTitle($title);
	}

	/**
	 * Executes a command.
	 *
	 * @param array $_argv the command argv like array
	 *
	 * @throws KliException
	 */
	final public function execute(array $_argv): void
	{
		try {
			$c = \count($_argv);

			if ($c <= 1) { // $ cli
				if ($this->enable_interactive) {
					$this->interactiveMode();
				} else {
					$this->showHelp();
				}
			} elseif ($this->isHelp($_argv[1])) { // $ cli --help
				$this->showHelp();
			} elseif ($this->isVersion($_argv[1])) { // $ cli --version
				$this->showVersion();
			} elseif ($this->hasCommand($_argv[1])) { // $ cli command
				$a1  = $_argv[1];
				$cmd = $this->commands[$a1];

				if (isset($_argv[2])) {
					$a2 = $_argv[2];

					if ($this->isHelp($a2)) { // $ cli command --help
						$this->showHelp($a1);
					} elseif ($cmd->hasAction($a2)) {
						$action = $cmd->getAction($a2);

						if (isset($_argv[3]) && $this->isHelp($_argv[3])) {
							// $ cli command action --help
							$this->showHelp($a1, $a2);
						} else { // $ cli command action [options]
							$opt_list = \array_slice($_argv, 2);
							$parser   = new KliParser($this);
							$kli_args = $parser->parse($action, $opt_list);
							$handler  = $action->getHandler();

							if ($handler) {
								$handler($kli_args);
							} else {
								$cmd->execute($action, $kli_args);
							}
						}
					} else {
						$this->error(\sprintf('%s: unknown action "%s"', $a1, $a2));
					}
				} else {
					$action_list = \implode(' , ', \array_keys($cmd->getActions()));
					$this->info(\sprintf('actions available for the command "%s": %s', $a1, $action_list));
				}
			} else {
				$this->error(\sprintf('unknown command: %s', $_argv[1]));
			}

			if (!$this->is_interactive) {
				$this->writeLn();
			}
		} catch (KliInputException $e) {
			$this->error($e->getMessage())
				->writeLn();
		}
	}

	/**
	 * Executes a command.
	 *
	 * @param string $cmd the command line string
	 *
	 * @throws KliException
	 */
	final public function executeString(string $cmd): void
	{
		$this->execute(KliUtils::stringToArgv($this->getCliEntryPoint() . ' ' . $cmd));
	}

	/**
	 * Gets the cli entry point.
	 *
	 * @return string
	 */
	public function getCliEntryPoint(): string
	{
		global $argv;

		return $argv[0];
	}

	/**
	 * Gets the cli executable.
	 *
	 * @return string
	 */
	public function getExecutable(): string
	{
		$entry_point = $this->getCliEntryPoint();

		if (\str_ends_with($entry_point, '.php') && \is_file($entry_point)) {
			return \PHP_BINARY . ' ' . $entry_point;
		}

		return $entry_point;
	}

	/**
	 * Builds command line string.
	 *
	 * @param string $command the command
	 * @param string $action  the action
	 * @param array  $args    the arguments
	 *
	 * @return string
	 */
	public function buildCommand(string $command, string $action, array $args): string
	{
		return $this->getExecutable() . ' ' . $command . ' ' . $action . ' ' . KliUtils::argvToString($args);
	}

	/**
	 * Enable interactive mode.
	 *
	 * @throws KliException
	 */
	public function interactiveMode(): void
	{
		if (!$this->is_interactive) {
			$this->is_interactive = true;
			$this->welcome();
			$this->info('Hint: type "quit" or "exit" to stop.' . \PHP_EOL);

			while ($this->is_interactive) {
				$in = $this->readLine(\sprintf('%s> ', $this->getTitle()));

				if ('' !== $in) {
					if ('quit' === $in || 'exit' === $in) {
						$this->quit();
					} else {
						// construct command: exactly as if it was fully typed
						$this->executeString($in);
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
	public function welcome(): void
	{
		// silence is gold
	}

	/**
	 * Write string on a new line.
	 *
	 * @param string $str  the string to write
	 * @param bool   $wrap to wrap string or not
	 *
	 * @return $this
	 */
	public function writeLn(string $str = '', bool $wrap = true): self
	{
		echo \PHP_EOL . ($wrap ? KliUtils::wrap($str) : $str);

		return $this;
	}

	/**
	 * Read data from user input.
	 *
	 * @param string $prompt      the prompt string
	 * @param bool   $is_password should we hide user input
	 *
	 * @return string user input
	 */
	public function readLine(string $prompt, bool $is_password = false): string
	{
		if ($is_password) {
			$this->writeLn($prompt);
			$line = $this->readPass();
			if (null === $line || false === $line) {
				$line = '';
			}
		} elseif (\function_exists('readline_add_history')) {
			$this->writeLn();
			$line = \readline($prompt);
			\readline_add_history($line);
		} else {
			$this->writeLn($prompt);
			$line = \fgets(\STDIN);
		}

		return \trim($line);
	}

	/**
	 * Title getter.
	 *
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * Title setter.
	 *
	 * @param string $title the cli title
	 *
	 * @return $this
	 */
	public function setTitle(string $title): self
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * Quit Kli.
	 */
	public function quit(): void
	{
		$this->is_interactive = false;
		$this->writeLn(); // silence is gold
	}

	/**
	 * Show the help.
	 *
	 * @param null|string $command_name the command name
	 * @param null|string $action_name  the action name
	 */
	public function showHelp(?string $command_name = null, ?string $action_name = null): void
	{
		if (!$this->is_interactive) {
			$this->welcome();
		}
		$head = \basename($this->getCliEntryPoint());

		$h = \PHP_EOL . 'Usage:'
			. \PHP_EOL . "  > {$head} command action [options]"
			. \PHP_EOL . 'For interactive mode.'
			. \PHP_EOL . "  > {$head}"
			. \PHP_EOL . 'To show help message.'
			. \PHP_EOL . "  > {$head} [command [action]] -?|--help"
			. \PHP_EOL . 'To show version.'
			. \PHP_EOL . "  > {$head} -v|--version"
			. \PHP_EOL . \PHP_EOL;

		if (isset($command_name) && $this->hasCommand($command_name)) {
			$cmd = $this->commands[$command_name];

			if (isset($action_name) && $cmd->hasAction($action_name)) {
				$h .= \sprintf('  %s %s', $cmd->getName(), $cmd->getAction($action_name));
			} else {
				$h .= $cmd;
			}
		} else {
			$h .= \implode(\PHP_EOL . \PHP_EOL, $this->commands) . \PHP_EOL;
		}

		$this->showVersion();
		$this->writeLn($h, false);
	}

	/**
	 * Show the version string.
	 */
	public function showVersion(): void
	{
		$this->writeLn($this->getVersion(true));
	}

	/**
	 * Gets the cli version.
	 */
	public function getVersion(bool $full = false): string
	{
		$version = '1.0.0';

		if ($full) {
			$head    = \basename($this->getCliEntryPoint());
			$version = $head . ' v' . $version;
		}

		return $version;
	}

	/**
	 * Checks if this cli has a given command.
	 *
	 * @param string $command_name the command name
	 *
	 * @return bool
	 */
	public function hasCommand(string $command_name): bool
	{
		return isset($this->commands[$command_name]);
	}

	/**
	 * Checks if string is a help flag.
	 *
	 * @param string $str the string to check
	 *
	 * @return bool
	 */
	public function isHelp(string $str): bool
	{
		return '--help' === $str || '-?' === $str;
	}

	/**
	 * Checks if string is a version flag.
	 *
	 * @param string $str the string to check
	 *
	 * @return bool
	 */
	public function isVersion(string $str): bool
	{
		return '--version' === $str || '-v' === $str;
	}

	/**
	 * Adds command to cli.
	 *
	 * @param KliCommand $command the command to add
	 *
	 * @return $this
	 */
	public function addCommand(KliCommand $command): self
	{
		$this->commands[$command->getName()] = $command;

		return $this;
	}

	/**
	 * Write string.
	 *
	 * @param string $str  the string to write
	 * @param bool   $wrap to wrap string or not
	 *
	 * @return $this
	 */
	public function write(string $str, bool $wrap = false): self
	{
		echo $wrap ? KliUtils::wrap($str) : $str;

		return $this;
	}

	/**
	 * Plays a bell sound in console (if available).
	 *
	 * @param int $count Bell play count
	 *
	 * @return $this
	 */
	public function bell(int $count = 1): self
	{
		if (\posix_isatty(\STDOUT)) {
			return $this->write(\str_repeat("\007", $count));
		}

		return $this;
	}

	/**
	 * Creates log file or append to existing.
	 *
	 * @param mixed $msg  the message to log
	 * @param bool  $wrap to wrap string or not
	 *
	 * @return $this
	 */
	public function log(mixed $msg, bool $wrap = true): self
	{
		if (\is_string($msg) && $this->log_file) {
			if ($wrap) {
				$msg = KliUtils::wrap($msg);
			}

			\file_put_contents($this->log_file, $msg . \PHP_EOL, \FILE_APPEND);
		}

		return $this;
	}

	/**
	 * Print error message.
	 *
	 * @param string $msg  the message
	 * @param bool   $wrap to wrap string or not
	 *
	 * @return $this
	 */
	public function error(string $msg, bool $wrap = true): self
	{
		$msg = '✖ ' . $msg;

		if ($wrap) {
			$msg = KliUtils::wrap($msg);
		}

		return $this->writeLn($this->style()
			->red()
			->apply($msg), false);
	}

	/**
	 * Gets color instance.
	 *
	 * @return KliStyle
	 */
	public function style(): KliStyle
	{
		return new KliStyle();
	}

	/**
	 * Gets table instance.
	 *
	 * @return KliTable
	 */
	public function table(): KliTable
	{
		return new KliTable();
	}

	/**
	 * Print success message.
	 *
	 * @param string $msg  the message
	 * @param bool   $wrap to wrap string or not
	 *
	 * @return $this
	 */
	public function success(string $msg, bool $wrap = true): self
	{
		$msg = '✔ ' . $msg;

		if ($wrap) {
			$msg = KliUtils::wrap($msg);
		}

		return $this->writeLn($this->style()
			->green()
			->apply($msg), false);
	}

	/**
	 * Print info message.
	 *
	 * @param string $msg  the message
	 * @param bool   $wrap to wrap string or not
	 *
	 * @return $this
	 */
	public function info(string $msg, bool $wrap = true): self
	{
		$msg = 'ℹ ' . $msg;

		if ($wrap) {
			$msg = KliUtils::wrap($msg);
		}

		return $this->writeLn($this->style()
			->cyan()
			->apply($msg), false);
	}

	/**
	 * Create a new instance of Kli.
	 *
	 * @param string      $name               the cli title
	 * @param bool        $enable_interactive to enable interactive cli
	 * @param null|string $log_file           path to log file
	 *
	 * @return self
	 */
	public static function new(
		string $name,
		bool $enable_interactive = false,
		?string $log_file = null
	): self {
		return new self($name, $enable_interactive, $log_file);
	}

	/**
	 * Create a new command.
	 *
	 * @param string $name the command name
	 */
	public function command(string $name): KliCommand
	{
		$cmd = new KliCommand($name, $this);
		$this->addCommand($cmd);

		return $cmd;
	}

	/**
	 * Read password.
	 *
	 * @return null|false|string user input
	 */
	protected function readPass(): null|bool|string
	{
		/** @psalm-suppress ForbiddenCode */
		return \shell_exec('stty -echo; head -n1; stty echo');
	}
}
