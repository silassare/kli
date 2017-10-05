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
			$charCount = strlen($command);
			$argv      = [];
			$arg       = '';
			$inDQuote  = false;
			$inSQuote  = false;

			for ($i = 0; $i < $charCount; $i++) {
				$char = substr($command, $i, 1);

				if ($char === ' ' && !$inDQuote && !$inSQuote) {
					if (strlen($arg)) {
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
		 * @param string $text   the text string to indent
		 * @param int    $size   the indent size
		 * @param string $indent char to use
		 *
		 * @return string
		 */
		public static function indent($text, $size = 1, $indent = ' ')
		{
			return self::wrap($text, 80, $size, 0, $indent);
		}

		/**
		 * Wrap text.
		 *
		 * @param string $text         the text string to wrap
		 * @param int    $width        the width
		 * @param int    $margin_left  left margin
		 * @param int    $margin_right right margin
		 * @param string $pad          char to use for padding
		 *
		 * @return string
		 */
		public static function wrap($text, $width = 80, $margin_left = 0, $margin_right = 0, $pad = ' ')
		{
			$width        = max(1, $width);
			$margin_left  = max(0, $margin_left);
			$margin_right = max(0, $margin_right);
			$width        = $width - abs($margin_left) - abs($margin_right);
			$margin       = str_repeat($pad, $margin_left);

			return $margin . wordwrap(preg_replace("#[\n\r]#", '', $text), $width, PHP_EOL . $margin);
		}
	}