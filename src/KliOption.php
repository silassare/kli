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
use Kli\Types\Interfaces\KliTypeInterface;
use Kli\Types\KliTypeBool;
use Kli\Types\KliTypeNumber;
use Kli\Types\KliTypePath;
use Kli\Types\KliTypeString;

/**
 * Class KliOption.
 */
final class KliOption
{
	public const NAME_REG  = '/^[a-zA-Z0-9][a-zA-Z0-9-_]*$/';
	public const ALIAS_REG = '/^[a-zA-Z0-9][a-zA-Z0-9-_]+$/';
	public const FLAG_REG  = '/^[a-zA-Z0-9]$/';

	private string $name;

	private ?string $opt_flag = null;

	private array $aliases = [];

	private string $opt_description = 'no description';

	private KliTypeInterface $opt_type;

	private ?array $opt_offsets = null;

	private bool $prompt = false;

	private string $prompt_msg = '';

	private bool $prompt_for_password = false;

	private bool $required = false;

	private $default;

	private bool $has_default = false;

	private bool $locked = false;

	/**
	 * KliOption constructor.
	 *
	 * @param string $name option name
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function __construct(string $name)
	{
		if (!\preg_match(self::NAME_REG, $name)) {
			throw new KliException(\sprintf('"%s" is not a valid option name.', $name));
		}

		$this->name     = $name;
		$this->opt_type = new KliTypeString();
		$len            = \strlen($this->name);

		if (1 === $len) {
			$this->flag($this->name);
		} else {
			$this->alias($this->name);
		}
	}

	/**
	 * Option to string routine used as help.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$text = $this->opt_flag ? '-' . $this->opt_flag . ' , ' : '';

		if (\count($this->aliases)) {
			$text .= '--' . \implode(' --', $this->aliases);
		}

		$text .= "\t" . $this->getDescription();

		return KliUtils::indent($text, 6);
	}

	/**
	 * Sets option type as string.
	 *
	 * @param null|int $min the minimum length of the option value
	 * @param null|int $max the maximum length of the option value
	 *
	 * @return \Kli\Types\KliTypeString
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function string(?int $min = null, ?int $max = null): KliTypeString
	{
		$type = new KliTypeString($min, $max);

		$this->type($type);

		return $type;
	}

	/**
	 * Sets option type as bool.
	 *
	 * @param bool        $strict  if true, the option value must be a boolean
	 * @param null|string $message the error message to display if the option value is not a boolean
	 *
	 * @return \Kli\Types\KliTypeBool
	 */
	public function bool(bool $strict = false, ?string $message = null): KliTypeBool
	{
		$type = new KliTypeBool($strict, $message);

		$this->type($type);

		return $type;
	}

	/**
	 * Sets option type as number.
	 *
	 * @param null|float $min
	 * @param null|float $max
	 *
	 * @return \Kli\Types\KliTypeNumber
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function number(?float $min = null, ?float $max = null): KliTypeNumber
	{
		$type = new KliTypeNumber($min, $max);

		$this->type($type);

		return $type;
	}

	/**
	 * Sets option type as path.
	 *
	 * @param null|int $min min path count
	 * @param null|int $max max path count
	 *
	 * @return \Kli\Types\KliTypePath
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function path(?int $min = null, ?int $max = null): KliTypePath
	{
		$type = new KliTypePath($min, $max);

		$this->type($type);

		return $type;
	}

	/**
	 * Adds option alias.
	 *
	 * @param string $alias option alias to add
	 *
	 * @return $this
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function alias(string $alias): self
	{
		if (!\preg_match(self::ALIAS_REG, $alias)) {
			throw new KliException(\sprintf('"%s" is not a valid alias.', $alias));
		}

		if (!\in_array($alias, $this->aliases, true)) {
			$this->aliases[] = $alias;
		}

		return $this;
	}

	/**
	 * Sets option flag.
	 *
	 * @param string $flag option flag
	 *
	 * @return $this
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function flag(string $flag): self
	{
		if (!\preg_match(self::FLAG_REG, $flag)) {
			throw new KliException(\sprintf('"%s" is not a valid flag.', $flag));
		}

		$this->opt_flag = $flag;

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
	 * @return $this
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function offsets(int $at, ?int $to = null): self
	{
		if ($this->locked) {
			throw new KliException("can't define offsets, option is locked.");
		}

		if ($at < 0) {
			throw new KliException(\sprintf('"%s" is not a valid arg offset.', $at));
		}

		if (null !== $to && (!\is_infinite($to) || $to < $at)) {
			throw new KliException(\sprintf('from=%s to=%s is not a valid arg offset range.', $at, $to));
		}

		$range = [$at, $to ?? $at];

		$this->opt_offsets = $range;

		return $this;
	}

	/**
	 * Lock the option.
	 *
	 * Options are locked and should not be modified when added to an action.
	 *
	 * @return $this
	 */
	public function lock(): self
	{
		$this->locked = true;

		return $this;
	}

	/**
	 * Explicitly set the default value.
	 *
	 * @param mixed $value the value to use as default
	 *
	 * @return $this
	 */
	public function def($value): self
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
	 * @return $this
	 */
	public function description(string $description): self
	{
		$this->opt_description = \trim($description);

		return $this;
	}

	/**
	 * Sets this option value type.
	 *
	 * @param \Kli\Types\Interfaces\KliTypeInterface $type
	 *
	 * @return $this
	 */
	public function type(KliTypeInterface $type): self
	{
		$this->opt_type = $type;

		return $this;
	}

	/**
	 * Mark this option as required.
	 *
	 * @return $this
	 */
	public function required(): self
	{
		$this->required = true;

		return $this;
	}

	/**
	 * Define the prompt capability and even prompt message.
	 *
	 * @param bool        $prompt              prompt enable/disable
	 * @param null|string $prompt_msg          prompt message
	 * @param bool        $prompt_for_password prompt is for password
	 *
	 * @return $this
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function prompt(bool $prompt = true, ?string $prompt_msg = null, bool $prompt_for_password = false): self
	{
		if ($prompt && isset($prompt_msg)) {
			if ('' !== \trim($prompt_msg)) {
				$this->prompt_msg = \trim($prompt_msg);
			} else {
				throw new KliException(\sprintf('the prompt for "-%s" should be a string.', $this->getName()));
			}
		}

		$this->prompt              = $prompt;
		$this->prompt_for_password = $prompt_for_password;

		return $this;
	}

	/**
	 * Option name getter.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Option flag getter.
	 *
	 * @return null|string
	 */
	public function getFlag(): ?string
	{
		return $this->opt_flag;
	}

	/**
	 * Does this option enable prompt.
	 *
	 * @return bool
	 */
	public function promptEnabled(): bool
	{
		return $this->prompt;
	}

	/**
	 * Does this option prompt is for password.
	 *
	 * @return bool
	 */
	public function promptForPassword(): bool
	{
		return $this->prompt_for_password;
	}

	/**
	 * Option prompt message getter.
	 *
	 * @return string
	 */
	public function getPrompt(): string
	{
		return empty($this->prompt_msg) ? \sprintf('Please provide -%s', $this->getName()) : $this->prompt_msg;
	}

	/**
	 * Option default value getter.
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
	public function getOffsets(): ?array
	{
		return $this->opt_offsets;
	}

	/**
	 * Does the default value was explicitly set.
	 *
	 * @return bool
	 */
	public function hasDefault(): bool
	{
		return $this->has_default;
	}

	/**
	 * Does this option is required or not.
	 *
	 * @return bool
	 */
	public function isRequired(): bool
	{
		return $this->required;
	}

	/**
	 * Option type getter.
	 *
	 * @return \Kli\Types\Interfaces\KliTypeInterface
	 */
	public function getType(): KliTypeInterface
	{
		return $this->opt_type;
	}

	/**
	 * Option aliases getter.
	 *
	 * @return string[]
	 */
	public function getAliases(): array
	{
		return $this->aliases;
	}

	/**
	 * Option description getter.
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->opt_description;
	}
}
