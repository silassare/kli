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

namespace Kli\Types;

use Kli\Types\Interfaces\KliTypeInterface;
use Override;

/**
 * Class KliType.
 */
abstract class KliType implements KliTypeInterface
{
	protected array $error_messages = [];
	protected mixed $default        = null;
	protected bool $has_default     = false;

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function def(mixed $value): static
	{
		// the default should comply with all rules or not ?
		$this->default     = $value;
		$this->has_default = true;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function hasDefault(): bool
	{
		return $this->has_default;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getDefault(): mixed
	{
		return $this->default;
	}

	/**
	 * Reads or overrides a named error message template.
	 *
	 * When $message is non-empty, stores it under $key and future calls will
	 * return that text. Always returns the current template for $key, falling
	 * back to $key itself when no template has been stored.
	 *
	 * @param string      $key     error message key defined in $error_messages
	 * @param null|string $message replacement template (null to read only)
	 *
	 * @return string the current message template for $key
	 */
	protected function msg(string $key, ?string $message = null): string
	{
		if (!empty($message)) {
			$this->error_messages[$key] = $message;
		}

		return $this->error_messages[$key] ?? $key;
	}
}
