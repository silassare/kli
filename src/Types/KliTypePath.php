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

use Kli\Exceptions\KliException;
use Kli\Exceptions\KliInputException;

/**
 * Class KliTypePath.
 */
class KliTypePath extends KliType
{
	protected array $error_messages = [
		'msg_require_valid_path'    => 'option "-%s" require valid path.',
		'msg_require_writable_path' => 'option "-%s" require writable path.',
		'msg_require_file_path'     => 'option "-%s" require file.',
		'msg_require_dir_path'      => 'option "-%s" require directory.',
		'msg_path_count_lt_min'     => 'option "-%s" require minimum %d path(s) (found=%d).',
		'msg_path_count_gt_max'     => 'option "-%s" require maximum %d path(s) (found=%d).',
		'msg_pattern_check_fails'   => '"%s" fails on regular expression for option "-%s".',
	];

	private int $opt_min = 1;

	private int $opt_max;

	private bool $multi = false;

	private bool $glob = false;

	private bool $is_file = true;

	private bool $is_dir = true;

	private bool $is_writable = false;

	private string $reg = '';

	/**
	 * KliTypePath constructor.
	 *
	 * @param null|int $min the minimum path count
	 * @param null|int $max the maximum path count
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function __construct(?int $min = null, ?int $max = null)
	{
		if (isset($min)) {
			$this->min($min);
		}

		if (isset($max)) {
			$this->max($max);
		}
	}

	/**
	 * Sets minimum path count.
	 *
	 * @param int         $value   the minimum path count
	 * @param null|string $message the error message
	 *
	 * @return $this
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function min(int $value, ?string $message = null): self
	{
		if ($value < 1) {
			throw new KliException(\sprintf('"%s" is not a valid integer(>0).', $value));
		}

		if (isset($this->opt_max) && $value > $this->opt_max) {
			throw new KliException(\sprintf('min=%s and max=%s is not a valid condition.', $value, $this->opt_max));
		}

		$this->opt_min = $value;

		!empty($message) && $this->msg('msg_path_count_lt_min', $message);

		return $this;
	}

	/**
	 * Sets maximum path count.
	 *
	 * @param int         $value   the maximum path count
	 * @param null|string $message the error message
	 *
	 * @return $this
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function max(int $value, ?string $message = null): self
	{
		if ($value < 1) {
			throw new KliException(\sprintf('"%s" is not a valid integer(>0).', $value));
		}

		if ($value < $this->opt_min) {
			throw new KliException(\sprintf('min=%s and max=%s is not a valid condition.', $this->opt_min, $value));
		}

		$this->opt_max = $value;

		!empty($message) && $this->msg('msg_path_count_gt_max', $message);

		return $this;
	}

	/**
	 * Sets the path pattern.
	 *
	 * @param string      $reg_expression the regular expression
	 * @param null|string $message        the error message
	 *
	 * @return $this
	 *
	 * @throws \Kli\Exceptions\KliException
	 */
	public function pattern(string $reg_expression, ?string $message = null): self
	{
		if (false === \preg_match($reg_expression, '')) {
			throw new KliException(\sprintf('invalid regular expression: %s', $reg_expression));
		}

		$this->reg = $reg_expression;

		!empty($message) && $this->msg('msg_pattern_check_fails', $message);

		return $this;
	}

	/**
	 * Allow multiple path.
	 *
	 * @return $this
	 */
	public function multiple(): self
	{
		$this->multi = true;

		return $this;
	}

	/**
	 * Accept file path only.
	 *
	 * @param null|string $message the error message
	 *
	 * @return $this
	 */
	public function file(?string $message = null): self
	{
		$this->is_file = true;
		$this->is_dir  = false;

		!empty($message) && $this->msg('msg_require_file_path', $message);

		return $this;
	}

	/**
	 * Accept directory path only.
	 *
	 * @param null|string $message the error message
	 *
	 * @return $this
	 */
	public function dir(?string $message = null): self
	{
		$this->is_file = false;
		$this->is_dir  = true;

		!empty($message) && $this->msg('msg_require_dir_path', $message);

		return $this;
	}

	/**
	 * Accept writable path only.
	 *
	 * @param null|string $message the error message
	 *
	 * @return $this
	 */
	public function writable(?string $message = null): self
	{
		$this->is_writable = true;

		!empty($message) && $this->msg('msg_require_writable_path', $message);

		return $this;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string|string[]
	 */
	public function validate(string $opt_name, $value)
	{
		$paths = $this->resolvePath($value);

		if (!$paths) {
			throw new KliInputException(\sprintf($this->msg('msg_require_valid_path'), $opt_name));
		}

		if (!empty($this->reg)) {
			$paths = $this->filterReg($paths);

			if (!\count($paths)) {
				throw new KliInputException(
					\sprintf($this->msg('msg_pattern_check_fails'), $value, $opt_name)
				);
			}
		}

		// directory only
		if (!$this->is_file) {
			$paths = \array_filter($paths, 'is_dir');

			if (!\count($paths)) {
				throw new KliInputException(\sprintf($this->msg('msg_require_dir_path'), $opt_name));
			}
		}

		// file only
		if (!$this->is_dir) {
			$paths = \array_filter($paths, 'is_file');

			if (!\count($paths)) {
				throw new KliInputException(\sprintf($this->msg('msg_require_file_path'), $opt_name));
			}
		}

		// writable only
		if ($this->is_writable) {
			$paths = \array_filter($paths, 'is_writable');

			if (!\count($paths)) {
				throw new KliInputException(\sprintf($this->msg('msg_require_writable_path'), $opt_name));
			}
		}

		$c = \count($paths);

		if ($c < $this->opt_min) {
			throw new KliInputException(
				\sprintf($this->msg('msg_path_count_lt_min'), $opt_name, $this->opt_min, $c)
			);
		}

		if (isset($this->opt_max) && $c > $this->opt_max) {
			throw new KliInputException(
				\sprintf($this->msg('msg_path_count_gt_max'), $opt_name, $this->opt_max, $c)
			);
		}

		return $this->multi ? $paths : $paths[0];
	}

	/**
	 * Resolve path use glob if enabled.
	 *
	 * @param string $path the path to resolve
	 *
	 * @return false|string[] path list
	 */
	private function resolvePath(string $path)
	{
		if (empty($path)) {
			return [];
		}

		if ($this->glob) {
			return \glob($path);
		}

		$path = \realpath($path);

		return $path ? [$path] : false;
	}

	/**
	 * Filters path list with regular expression.
	 *
	 * @param string[] $list the path list to filter
	 *
	 * @return array path list
	 */
	private function filterReg(array $list): array
	{
		$found = [];

		foreach ($list as $f) {
			if (\preg_match($this->reg, $f)) {
				$found[] = $f;
			}
		}

		return $found;
	}
}
