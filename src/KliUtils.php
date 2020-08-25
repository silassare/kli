<?php

/**
 * Copyright (c) 2017-present, Emile Silas Sare
 *
 * This file is part of Kli package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kli;

class KliUtils
{
	/**
	 * Parse command string to argv like array.
	 *
	 * found here: https://someguyjeremy.com/2017/07/adventures-in-parsing-strings-to-argv-in-php.html
	 *
	 * @param string $command the command string
	 *
	 * @return array
	 */
	public static function stringToArgv($command)
	{
		$charCount = \strlen($command);
		$argv      = [];
		$arg       = '';
		$inDQuote  = false;
		$inSQuote  = false;

		for ($i = 0; $i < $charCount; $i++) {
			$char = \substr($command, $i, 1);

			if ($char === ' ' && !$inDQuote && !$inSQuote) {
				if (\strlen($arg)) {
					$argv[] = $arg;
				}
				$arg = '';

				continue;
			}

			if ($inSQuote && $char === "'") {
				$inSQuote = false;

				continue;
			}

			if ($inDQuote && $char === '"') {
				$inDQuote = false;

				continue;
			}

			if ($char === '"' && !$inSQuote) {
				$inDQuote = true;

				continue;
			}

			if ($char === "'" && !$inDQuote) {
				$inSQuote = true;

				continue;
			}

			$arg .= $char;
		}

		$argv[] = $arg;

		return $argv;
	}

	/**
	 * Indent text.
	 *
	 * @param string $text        the text string to indent
	 * @param int    $size        the indent size
	 * @param string $indent_char char to use
	 *
	 * @return string
	 */
	public static function indent($text, $size = 1, $indent_char = ' ')
	{
		return self::margins(self::wrap($text), [
			'left' => $size,
			'pad'  => $indent_char,
		]);
	}

	/**
	 * Wrap text.
	 *
	 * @param string $text          the text string to wrap
	 * @param int    $width         the width
	 * @param bool   $cut_long_word to cut long words
	 *
	 * @return string
	 */
	public static function wrap($text, $width = 80, $cut_long_word = false)
	{
		$width = \max(1, $width);

		return \wordwrap(\preg_replace("~\n|\r\n?~", '', $text), $width, "\n", $cut_long_word);
	}

	/**
	 * Adds margins to text.
	 *
	 * ```php
	 *
	 * $text = 'My text';
	 * $text = KliUtils::margins($text, [
	 *   'left'   => 4,
	 *   'right'  => 4,
	 *   'top'    => 1,
	 *   'bottom' => 1,
	 * ]);
	 * echo $text;
	 *
	 * ```
	 *
	 * @param string $text    the text string
	 * @param array  $options margin options
	 *
	 * @return string
	 */
	public static function margins($text, array $options = [])
	{
		$left         = isset($options['left']) ? \max(0, $options['left']) : 0;
		$right        = isset($options['right']) ? \max(0, $options['right']) : 0;
		$top          = isset($options['top']) ? \max(0, $options['top']) : 0;
		$bottom       = isset($options['bottom']) ? \max(0, $options['bottom']) : 0;
		$pad          = isset($options['pad']) ? $options['pad'] : ' ';

		$text         = \preg_replace("~\n|\r\n?~", \PHP_EOL, $text);
		$parts        = \explode(\PHP_EOL, $text);
		$margin_left  = \str_repeat($pad, $left);
		$margin_right = \str_repeat($pad, $right);
		$line_length  = 0;
		$out          = '';

		foreach ($parts as $line) {
			$len = \strlen($line);

			if ($len > $line_length) {
				$line_length = $len;
			}
		}

		foreach ($parts as $line) {
			$len  = \strlen($line);
			$fill = '';

			if ($len < $line_length) {
				$fill = \str_repeat($pad, $line_length - $len);
			}

			$out .= $margin_left . $line . $fill . $margin_right . \PHP_EOL;
		}

		$sp            = \str_repeat($pad, $line_length);
		$margin_top    = '';
		$margin_bottom = '';

		if ($top) {
			$margin_top = \str_repeat($margin_left . $sp . $margin_right . \PHP_EOL, $top);
		}

		if ($bottom) {
			$margin_bottom = \str_repeat($margin_left . $sp . $margin_right, $bottom);
		}

		return $margin_top .
			   $out .
			   $margin_bottom;
	}
}
