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

use Kli\Exceptions\KliAbortException;
use Kli\Exceptions\KliException;
use Kli\Exceptions\KliInputException;
use Kli\Table\KliTable;

/**
 * Class Kli.
 *
 * Entry point and orchestrator for the CLI application. Holds the command
 * registry, dispatches argv to the appropriate KliAction handler, manages
 * the interactive REPL loop, and exposes I/O helpers (writeLn, error, info,
 * success, table, style).
 *
 * Typical usage:
 *
 * ```php
 * $kli = Kli::new('my-tool');
 * $cmd = $kli->command('greet');
 * $act = $cmd->action('say');
 * $act->option('name', 'n')->string()->def('World');
 * $act->handler(function (KliArgs $args) use ($kli): void {
 *     $kli->success('Hello, ' . $args->get('name') . '!');
 * });
 * $kli->execute($argv);
 * ```
 */
class Kli
{
	private bool $allow_interactive_mode;

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
	 * @param string      $title                  the cli title to be used in interactive mode
	 * @param bool        $allow_interactive_mode to allow interactive cli
	 * @param null|string $log_file               path to log file
	 */
	public function __construct(string $title = '', bool $allow_interactive_mode = false, ?string $log_file = null)
	{
		if (empty($title)) {
			$title = \basename($this->getCliEntryPoint());
		}

		$this->allow_interactive_mode = $allow_interactive_mode;
		$this->log_file               = $log_file;
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
				if ($this->allow_interactive_mode) {
					$this->switchToInteractiveMode();
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
		} catch (KliAbortException) {
			// error/warn/success with a non-null $exit threw this in interactive
			// mode instead of calling exit(); the REPL loop simply continues.
		} catch (KliInputException $e) {
			// error() with default exit=1 will terminate in script mode;
			// in interactive mode it throws KliAbortException, caught above.
			$this->error($e->getMessage());
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
	 * Returns true when the REPL interactive loop is active.
	 *
	 * Use this to decide whether to exit after an error or to simply
	 * print and continue (i.e. suppress the $exit parameter).
	 */
	public function isInteractiveMode(): bool
	{
		return $this->is_interactive;
	}

	/**
	 * Switches to interactive mode.
	 *
	 * @throws KliException
	 */
	public function switchToInteractiveMode(): void
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
	 * @return static
	 */
	public function writeLn(string $str = '', bool $wrap = true): static
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
	 * @return static
	 */
	public function setTitle(string $title): static
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
		$s    = $this->style();
		$out  = '  ' . $s->bold()->apply($this->getVersion(true)) . \PHP_EOL;
		$out .= \PHP_EOL;

		if (isset($command_name) && $this->hasCommand($command_name)) {
			$cmd = $this->commands[$command_name];

			if (isset($action_name) && $cmd->hasAction($action_name)) {
				// Action-level help
				$action  = $cmd->getAction($action_name);
				$options = $action->getOptions();
				$sig     = $cmd->getName() . ' ' . $action->getName();

				if (\count($options) > 0) {
					$sig .= ' [options]';
				}

				$out .= '  ' . $s->bold()->apply($sig) . \PHP_EOL;
				$out .= '  ' . $action->getDescription() . \PHP_EOL;

				if (\count($options) > 0) {
					$out .= \PHP_EOL;
					$out .= '  ' . $s->bold()->apply('Options') . \PHP_EOL;
					$out .= \PHP_EOL;

					foreach ($options as $opt) {
						$out .= $this->renderOptionHelp($opt, 4);
					}
				}
			} else {
				// Command-level help
				$out .= '  ' . $s->bold()->apply($cmd->getName()) . \PHP_EOL;

				if ('' !== $cmd->getDescription()) {
					$out .= '  ' . $cmd->getDescription() . \PHP_EOL;
				}

				$out .= \PHP_EOL;
				$out .= '  ' . $s->bold()->apply('Actions') . \PHP_EOL;

				foreach ($cmd->getActions() as $action) {
					$options = $action->getOptions();
					$sig     = $action->getName();

					if (\count($options) > 0) {
						$sig .= ' [options]';
					}

					$out .= \PHP_EOL;
					$out .= '    ' . $s->cyan()->apply($sig) . \PHP_EOL;
					$out .= '      ' . $action->getDescription() . \PHP_EOL;

					foreach ($options as $opt) {
						$out .= $this->renderOptionHelp($opt, 6);
					}
				}
			}
		} else {
			// Top-level help
			$out .= '  ' . $s->bold()->apply('Usage') . \PHP_EOL;
			$out .= \PHP_EOL;

			$usages = [
				'$ ' . $head . ' <command> <action> [options]' => 'Run a command',
				'$ ' . $head                                   => 'Start interactive mode',
				'$ ' . $head . ' [command [action]] --help'    => 'Show help',
				'$ ' . $head . ' --version'                    => 'Show version',
			];

			$max_usage_len = 0;

			foreach ($usages as $usage => $_) {
				$len = \mb_strlen($usage);

				if ($len > $max_usage_len) {
					$max_usage_len = $len;
				}
			}

			foreach ($usages as $usage => $desc) {
				$gap  = \str_repeat(' ', $max_usage_len - \mb_strlen($usage) + 4);
				$out .= '    ' . $s->dim()->apply($usage) . $gap . $desc . \PHP_EOL;
			}

			$out .= \PHP_EOL;
			$out .= '  ' . $s->bold()->apply('Commands') . \PHP_EOL;
			$out .= \PHP_EOL;

			$max_cmd_len = 0;

			foreach ($this->commands as $cmd) {
				$len = \mb_strlen($cmd->getName());

				if ($len > $max_cmd_len) {
					$max_cmd_len = $len;
				}
			}

			foreach ($this->commands as $cmd) {
				$gap  = \str_repeat(' ', $max_cmd_len - \mb_strlen($cmd->getName()) + 4);
				$desc = $cmd->getDescription();
				$out .= '    ' . $s->cyan()->apply($cmd->getName()) . $gap . $desc . \PHP_EOL;
			}
		}

		$this->writeLn($out, false);
	}

	/**
	 * Show the version string.
	 */
	public function showVersion(): void
	{
		$this->writeLn('  ' . $this->style()->bold()->apply($this->getVersion(true)));
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
	 * @return static
	 */
	public function addCommand(KliCommand $command): static
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
	 * @return static
	 */
	public function write(string $str, bool $wrap = false): static
	{
		echo $wrap ? KliUtils::wrap($str) : $str;

		return $this;
	}

	/**
	 * Plays a bell sound in console (if available).
	 *
	 * @param int $count Bell play count
	 *
	 * @return static
	 */
	public function bell(int $count = 1): static
	{
		if (\posix_isatty(\STDOUT)) {
			return $this->write(\str_repeat("\007", $count));
		}

		return $this;
	}

	/**
	 * Creates log file or append to existing.
	 *
	 * @param string $level   the log level (e.g. 'info', 'error', etc.)
	 * @param mixed  $msg     the message to log
	 * @param array  $context additional context to include in the log
	 *
	 * @return static
	 */
	public function log(string $level, mixed $msg, array $context = []): static
	{
		if ($this->log_file) {
			// one line log format: [LEVEL] message {json_encoded_context}

			$msg = \str_replace("\n", ' ', (string) $msg); // ensure message is single-line

			$str = '[' . \strtoupper($level) . '] ' . $msg;
			$str .= !empty($context) ? ' ' . \json_encode($context) : '';
			$str .= \PHP_EOL;

			\file_put_contents($this->log_file, $str, \FILE_APPEND);
		}

		return $this;
	}

	/**
	 * Print info message.
	 *
	 * @param string $msg  the message
	 * @param bool   $wrap to wrap string or not
	 *
	 * @return static
	 */
	public function info(string $msg, bool $wrap = true): static
	{
		if ($wrap) {
			$msg = KliUtils::wrap($msg);
		}

		$icon = $this->style()->cyan()->apply('ℹ');

		return $this->writeLn('  ' . $icon . '  ' . $msg, false);
	}

	/**
	 * Print warning message.
	 *
	 * When $exit is non-null and we are NOT in interactive mode, the process
	 * exits with that code after printing. In interactive mode $exit is always
	 * ignored.
	 *
	 * @param string   $msg  the message
	 * @param bool     $wrap to wrap string or not
	 * @param null|int $exit exit code to use in script mode (default: null, no stop);
	 *                       pass a code to stop after printing.
	 *                       In interactive mode a non-null value throws
	 *                       KliAbortException instead of calling exit().
	 *
	 * @return static
	 *
	 * @throws KliAbortException in interactive mode when $exit is non-null
	 */
	public function warn(string $msg, bool $wrap = true, ?int $exit = null): static
	{
		if ($wrap) {
			$msg = KliUtils::wrap($msg);
		}

		$icon = $this->style()->yellow()->apply('⚠');

		$this->writeLn('  ' . $icon . '  ' . $msg, false);

		if (null !== $exit) {
			if ($this->is_interactive) {
				throw new KliAbortException();
			}
			$this->terminate($exit);
		}

		return $this;
	}

	/**
	 * Print success message.
	 *
	 * When $exit is non-null and we are NOT in interactive mode, the process
	 * exits with that code after printing. In interactive mode $exit is always
	 * ignored.
	 *
	 * @param string   $msg  the message
	 * @param bool     $wrap to wrap string or not
	 * @param null|int $exit exit code to use in script mode (default: null, no stop);
	 *                       pass 0 to stop cleanly after the success message.
	 *                       In interactive mode a non-null value throws
	 *                       KliAbortException instead of calling exit().
	 *
	 * @return static
	 *
	 * @throws KliAbortException in interactive mode when $exit is non-null
	 */
	public function success(string $msg, bool $wrap = true, ?int $exit = null): static
	{
		if ($wrap) {
			$msg = KliUtils::wrap($msg);
		}

		$icon = $this->style()->green()->apply('✔');

		$this->writeLn('  ' . $icon . '  ' . $msg, false);

		if (null !== $exit) {
			if ($this->is_interactive) {
				throw new KliAbortException();
			}
			$this->terminate($exit);
		}

		return $this;
	}

	/**
	 * Print error message.
	 *
	 * When $exit is non-null and we are NOT in interactive mode, the process
	 * exits with that code after printing. In interactive mode $exit is always
	 * ignored — errors should never kill the REPL session.
	 *
	 * @param string   $msg  the message
	 * @param bool     $wrap to wrap string or not
	 * @param null|int $exit exit code to use in script mode (default: 1);
	 *                       pass null to print without stopping.
	 *                       In interactive mode a non-null value throws
	 *                       KliAbortException instead of calling exit().
	 *
	 * @return static
	 *
	 * @throws KliAbortException in interactive mode when $exit is non-null
	 */
	public function error(string $msg, bool $wrap = true, ?int $exit = 1): static
	{
		if ($wrap) {
			$msg = KliUtils::wrap($msg);
		}

		$icon = $this->style()->red()->bold()->apply('✖');

		$this->writeLn('  ' . $icon . '  ' . $msg, false);

		if (null !== $exit) {
			if ($this->is_interactive) {
				throw new KliAbortException();
			}
			$this->terminate($exit);
		}

		return $this;
	}

	/**
	 * Terminate the process with the given exit code.
	 *
	 * Unlike the $exit parameter on output methods, this always exits, even
	 * inside interactive mode. Use it when you explicitly need to stop the
	 * whole process regardless of context.
	 *
	 * @param int $code POSIX exit code (0 = success, non-zero = failure)
	 */
	public function terminate(int $code = 0): never
	{
		exit($code);
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
	 * Create a new instance of Kli.
	 *
	 * @param string      $name                   the cli title
	 * @param bool        $allow_interactive_mode to allow interactive cli
	 * @param null|string $log_file               path to log file
	 *
	 * @return static
	 */
	public static function new(
		string $name,
		bool $allow_interactive_mode = false,
		?string $log_file = null
	): static {
		return new static($name, $allow_interactive_mode, $log_file);
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
	protected function readPass(): bool|string|null
	{
		/** @psalm-suppress ForbiddenCode */
		return \shell_exec('stty -echo; head -n1; stty echo');
	}

	/**
	 * Renders a single option as a formatted help line.
	 *
	 * @param KliOption $option      the option to render
	 * @param int       $indent_size leading spaces
	 *
	 * @return string
	 */
	private function renderOptionHelp(KliOption $option, int $indent_size): string
	{
		$indent = \str_repeat(' ', $indent_size);
		$flag   = $option->getFlag();

		// Left column: "-f  --name"  or  "    --name" (plain, no ANSI, for correct str_pad)
		$left  = $flag ? '-' . $flag . '  ' : '    ';
		$left .= '--' . $option->getName();

		$parts = [$option->getDescription()];
		$type  = $option->getType();

		if ($type->hasDefault()) {
			$default     = $type->getDefault();
			$default_str = \is_bool($default) ? ($default ? 'true' : 'false') : (string) $default;

			if ('' !== $default_str) {
				$parts[] = $this->style()->dim()->apply('default: ' . $default_str);
			}
		}

		if ($option->isRequired()) {
			$parts[] = $this->style()->yellow()->apply('required');
		}

		return $indent . \str_pad($left, 22) . \implode('   ', $parts) . \PHP_EOL;
	}
}
