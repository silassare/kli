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

use Kli\Exceptions\KliInputException;

/**
 * Class KliTypeBool.
 */
class KliTypeBool extends KliType
{
	protected array      $error_messages = [
		'msg_require_bool' => 'option "%s" require a boolean.',
	];
	private static array $list           = [true, false, 'y', 'n', 'yes', 'no'];

	private static array $extended_list = [true, false, 'y', 'n', 'yes', 'no', 1, 0, '1', '0', 'true', 'false'];

	private static array $map = [
		'1'     => true,
		'0'     => false,
		'true'  => true,
		'false' => false,
		'yes'   => true,
		'no'    => false,
		'y'     => true,
		'n'     => false,
	];

	private bool $strict;

	/**
	 * KliTypeBool Constructor.
	 *
	 * @param bool        $strict  whether to limit bool value to only: true, false, 'y', 'n', 'yes', 'no'
	 * @param null|string $message the error message
	 */
	public function __construct(bool $strict = false, ?string $message = null)
	{
		$this->strict = $strict;

		!empty($message) && $this->msg('msg_require_bool', $message);
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate(string $opt_name, $value)
	{
		$is_str = \is_string($value);
		$value  = $is_str ? \strtolower($value) : $value;

		if (!\in_array($value, $this->strict ? self::$list : self::$extended_list, true)) {
			throw new KliInputException(\sprintf($this->msg('msg_require_bool'), $value, $opt_name));
		}

		return $is_str ? self::$map[$value] : (bool) $value;
	}
}
