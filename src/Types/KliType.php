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

/**
 * Class KliType.
 */
abstract class KliType implements KliTypeInterface
{
	protected array $error_messages = [];

	/**
	 * @var mixed
	 */
	protected $default;
	protected bool $has_default = false;

	/**
	 * {@inheritDoc}
	 */
	public function def($value): self
	{
		// the default should comply with all rules or not ?
		$this->default     = $value;
		$this->has_default = true;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function hasDefault(): bool
	{
		return $this->has_default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDefault()
	{
		return $this->default;
	}

	/**
	 * Sets/Gets custom error message.
	 *
	 * @param string      $key     the error key
	 * @param null|string $message the error message
	 *
	 * @return string
	 */
	protected function msg(string $key, ?string $message = null): string
	{
		if (!empty($message)) {
			$this->error_messages[$key] = $message;
		}

		return $this->error_messages[$key] ?? $key;
	}
}
