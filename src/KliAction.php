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
 * Class KliAction.
 */
final class KliAction
{
	public const NAME_REG = '~^[a-zA-Z0-9]([a-zA-Z0-9-_:]+)$~';

	private string $name;

	private string $description = 'no description';

	/**
	 * @var KliOption[]
	 */
	private array $options = [];

	private array $offsets_lock = [];

	private array $used_aliases = [];

	private array $used_flags = [];

	/**
	 * @var null|callable(KliArgs): void
	 */
	private $handler_fn;

	/**
	 * KliAction constructor.
	 *
	 * @param string $name action name
	 */
	public function __construct(string $name)
	{
		if (!\preg_match(self::NAME_REG, $name)) {
			throw new KliRuntimeException(\sprintf('"%s" is not a valid action name.', $name));
		}

		$this->name = $name;
	}

	/**
	 * Action to string routine used as help.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$text = $this->getName();

		if (\count($this->options) > 0) {
			$text .= ' [options]';
		}
		$text .= \PHP_EOL . KliUtils::indent($this->getDescription(), 4);
		$sep  = \PHP_EOL . \PHP_EOL;
		$text .= $sep . \implode($sep, $this->options);

		return $text;
	}

	/**
	 * Sets the action handler.
	 *
	 * @param callable $handler
	 *
	 * @return $this
	 */
	public function handler(callable $handler): self
	{
		$this->handler_fn = $handler;

		return $this;
	}

	/**
	 * Gets the action handler.
	 *
	 * @return null|callable(KliArgs): void
	 */
	public function getHandler(): ?callable
	{
		return $this->handler_fn ?? null;
	}

	/**
	 * Adds a new option.
	 *
	 * @param string   $name
	 * @param string   $flag
	 * @param array    $aliases
	 * @param null|int $offset_start
	 * @param null|int $offset_end
	 *
	 * @return KliOption
	 */
	public function option(
		string $name,
		string $flag = '',
		array $aliases = [],
		?int $offset_start = null,
		?int $offset_end = null
	): KliOption {
		$opt = new KliOption($name);

		if ($flag) {
			$opt->flag($flag);
		}

		if ($aliases) {
			foreach ($aliases as $alias) {
				$opt->alias($alias);
			}
		}

		if (null !== $offset_start) {
			$opt->offsets($offset_start, $offset_end);
		}

		$this->addOption($opt);

		return $opt;
	}

	/**
	 * Adds option(s) to this action.
	 *
	 * @param KliOption ...$options
	 *
	 * @return $this
	 */
	public function addOption(KliOption ...$options): self
	{
		foreach ($options as $o) {
			$opt_name = $o->getName();

			if (isset($this->options[$opt_name])) {
				throw new KliRuntimeException(
					\sprintf('option "--%s" is already defined in action "%s".', $opt_name, $this->getName())
				);
			}

			$opt_flag = $o->getFlag();

			if ($opt_flag) {
				if (\array_key_exists($opt_flag, $this->used_flags)) {
					throw new KliRuntimeException(\sprintf(
						'flag "-%s" is already defined for option "%s" in action "%s".',
						$opt_flag,
						$this->used_flags[$opt_flag],
						$this->getName()
					));
				}

				$this->used_flags[$opt_flag] = $opt_name;
			}

			$aliases = $o->getAliases();

			foreach ($aliases as $alias) {
				if (\array_key_exists($alias, $this->used_aliases)) {
					throw new KliRuntimeException(\sprintf(
						'alias "--%s" is already defined for option "%s" in action "%s"',
						$alias,
						$this->used_aliases[$alias],
						$this->name
					));
				}

				$this->used_aliases[$alias] = $opt_name;
			}

			$offsets = $o->getOffsets();

			if (!empty($offsets)) {
				[$a, $b] = $offsets;

				foreach ($this->offsets_lock as $locker => $lock) {
					[$c, $d] = $lock;
					$ok      = ($a > $d || $b < $c); // some math lol

					if (!$ok) {
						throw new KliRuntimeException(\sprintf(
							'all or parts of offsets(%s,%s) is used by option "%s" of action "%s".',
							$a,
							$b,
							$locker,
							$this->getName()
						));
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
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Define this action description.
	 *
	 * @param string $description action description
	 *
	 * @return $this
	 */
	public function description(string $description): self
	{
		$this->description = \trim($description);

		return $this;
	}

	/**
	 * Does this action has a given option.
	 *
	 * @param string $name the option name
	 *
	 * @return bool
	 */
	public function hasOption(string $name): bool
	{
		return isset($this->options[$name]) || isset($this->used_flags[$name]) || isset($this->used_aliases[$name]);
	}

	/**
	 * Gets option with a given name.
	 *
	 * @param string $name the option name or flag
	 *
	 * @return KliOption
	 */
	public function getOption(string $name): KliOption
	{
		if (!isset($this->options[$name])) {
			$resolved_name = $this->used_aliases[$name] ?? $this->used_flags[$name] ?? null;
			if (empty($resolved_name)) {
				throw new KliRuntimeException(\sprintf('"%s" - unknown option: "%s"', $this->getName(), $name));
			}

			$name = $resolved_name;
		}

		return $this->options[$name];
	}

	/**
	 * Gets this action options list.
	 *
	 * @return KliOption[]
	 */
	public function getOptions(): array
	{
		return $this->options;
	}

	/**
	 * Action description getter.
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->description;
	}
}
