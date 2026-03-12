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

/**
 * Class KliStyle.
 *
 * Fluent builder for ANSI terminal styling. Chain one foreground color method,
 * one background color method, and one style method, then call apply(string)
 * to wrap the string in the appropriate ANSI escape sequences.
 *
 * ANSI codes are emitted only when STDOUT is a TTY. Override with the static
 * flags: $forceAnsi (always emit) or $disableAnsi (never emit, takes
 * precedence over $forceAnsi). Per-attribute reset codes are used so multiple
 * independent styles can coexist on the same output line.
 */
class KliStyle
{
	public const FOREGROUND_COLORS = [
		'black'         => '30',
		'dark_gray'     => '90',
		'blue'          => '34',
		'light_blue'    => '94',
		'green'         => '32',
		'light_green'   => '92',
		'cyan'          => '36',
		'light_cyan'    => '96',
		'red'           => '31',
		'light_red'     => '91',
		'magenta'       => '35',
		'light_magenta' => '95',
		'yellow'        => '33',
		'light_gray'    => '37',
		'white'         => '97',
		'normal'        => '39',
	];

	public const BACKGROUND_COLORS = [
		'black'      => '40',
		'red'        => '41',
		'green'      => '42',
		'yellow'     => '43',
		'blue'       => '44',
		'magenta'    => '45',
		'cyan'       => '46',
		'light_gray' => '47',
	];

	public const STYLES = [
		'bold'      => '1',
		'dim'       => '2',
		'underline' => '4',
		'blink'     => '5',
		'invert'    => '7',
		'hidden'    => '8',
	];

	public const STYLES_RESET = [
		'bold'      => '22',
		'dim'       => '22',
		'underline' => '24',
		'blink'     => '25',
		'invert'    => '27',
		'hidden'    => '28',
	];

	/**
	 * When set to true, ANSI codes are always emitted regardless of whether
	 * STDOUT is a TTY. Intended for tests that need to assert ANSI output
	 * without a real terminal.
	 */
	public static bool $forceAnsi = false;

	/**
	 * When set to true, ANSI codes are never emitted regardless of whether
	 * STDOUT is a TTY. Takes precedence over $forceAnsi.
	 * Intended for tests that need to assert plain-text output
	 * even when running in a real terminal.
	 */
	public static bool $disableAnsi = false;

	private static string $foreground_reset = '39';

	private static string $background_reset = '49';

	private ?string $opt_color = null;

	private ?string $opt_bg = null;

	private ?string $opt_style = null;

	private array $box_options;

	/**
	 * Wraps $str in ANSI escape sequences for the configured color and style.
	 *
	 * Emits codes only when STDOUT is a TTY or $forceAnsi is true.
	 * Suppressed entirely when $disableAnsi is true regardless of TTY.
	 * Returns $str unchanged when no codes apply or ANSI is disabled.
	 *
	 * @param string $str the string to style
	 *
	 * @return string
	 */
	public function apply(string $str): string
	{
		if (!empty($this->box_options)) {
			$str = KliUtils::paddings(KliUtils::wrap($str), $this->box_options);
		}

		$color = $this->opt_color;
		$bg    = $this->opt_bg;
		$style = $this->opt_style;
		$set   = [];
		$reset = [];

		if (!self::$disableAnsi && (self::$forceAnsi || \stream_isatty(\STDOUT))) {
			if (isset(self::FOREGROUND_COLORS[$color])) {
				$set[]   = self::FOREGROUND_COLORS[$color];
				$reset[] = self::$foreground_reset;
			}

			if (isset(self::BACKGROUND_COLORS[$bg])) {
				$set[]   = self::BACKGROUND_COLORS[$bg];
				$reset[] = self::$background_reset;
			}

			if (isset(self::STYLES[$style])) {
				$set[]   = self::STYLES[$style];
				$reset[] = self::STYLES_RESET[$style];
			}
		}

		if (\count($set)) {
			$set   = "\033[" . \implode(';', $set) . 'm';
			$reset = "\033[" . \implode(';', $reset) . 'm';

			return $set . $str . $reset;
		}

		return $str;
	}

	/**
	 * Draw a box around the text.
	 *
	 * @param array $options
	 *
	 * @return static
	 */
	public function box(array $options = [
		'top'    => 1,
		'right'  => 1,
		'left'   => 1,
		'bottom' => 1,
	]): static
	{
		$this->box_options = $options;

		return $this;
	}

	/**
	 * Sets the foreground color to black.
	 *
	 * @return static
	 */
	public function black(): static
	{
		$this->opt_color = 'black';

		return $this;
	}

	/**
	 * Sets the foreground color to dark gray.
	 *
	 * @return static
	 */
	public function darkGray(): static
	{
		$this->opt_color = 'dark_gray';

		return $this;
	}

	/**
	 * Sets the foreground color to blue.
	 *
	 * @return static
	 */
	public function blue(): static
	{
		$this->opt_color = 'blue';

		return $this;
	}

	/**
	 * Sets the foreground color to light blue.
	 *
	 * @return static
	 */
	public function lightBlue(): static
	{
		$this->opt_color = 'light_blue';

		return $this;
	}

	/**
	 * Sets the foreground color to green.
	 *
	 * @return static
	 */
	public function green(): static
	{
		$this->opt_color = 'green';

		return $this;
	}

	/**
	 * Sets the foreground color to light green.
	 *
	 * @return static
	 */
	public function lightGreen(): static
	{
		$this->opt_color = 'light_green';

		return $this;
	}

	/**
	 * Sets the foreground color to cyan.
	 *
	 * @return static
	 */
	public function cyan(): static
	{
		$this->opt_color = 'cyan';

		return $this;
	}

	/**
	 * Sets the foreground color to light cyan.
	 *
	 * @return static
	 */
	public function lightCyan(): static
	{
		$this->opt_color = 'light_cyan';

		return $this;
	}

	/**
	 * Sets the foreground color to red.
	 *
	 * @return static
	 */
	public function red(): static
	{
		$this->opt_color = 'red';

		return $this;
	}

	/**
	 * Sets the foreground color to light red.
	 *
	 * @return static
	 */
	public function lightRed(): static
	{
		$this->opt_color = 'light_red';

		return $this;
	}

	/**
	 * Sets the foreground color to magenta.
	 *
	 * @return static
	 */
	public function magenta(): static
	{
		$this->opt_color = 'magenta';

		return $this;
	}

	/**
	 * Sets the foreground color to light magenta.
	 *
	 * @return static
	 */
	public function lightMagenta(): static
	{
		$this->opt_color = 'light_magenta';

		return $this;
	}

	/**
	 * Sets the foreground color to brown.
	 *
	 * @return static
	 */
	public function yellow(): static
	{
		$this->opt_color = 'yellow';

		return $this;
	}

	/**
	 * Sets the foreground color to light gray.
	 *
	 * @return static
	 */
	public function lightGray(): static
	{
		$this->opt_color = 'light_gray';

		return $this;
	}

	/**
	 * Sets the foreground color to white.
	 *
	 * @return static
	 */
	public function white(): static
	{
		$this->opt_color = 'white';

		return $this;
	}

	/**
	 * Sets the foreground color to normal.
	 *
	 * @return static
	 */
	public function normal(): static
	{
		$this->opt_color = 'normal';

		return $this;
	}

	/**
	 * Sets the background color to black.
	 *
	 * @return static
	 */
	public function backgroundBlack(): static
	{
		$this->opt_bg = 'black';

		return $this;
	}

	/**
	 * Sets the background color to red.
	 *
	 * @return static
	 */
	public function backgroundRed(): static
	{
		$this->opt_bg = 'red';

		return $this;
	}

	/**
	 * Sets the background color to green.
	 *
	 * @return static
	 */
	public function backgroundGreen(): static
	{
		$this->opt_bg = 'green';

		return $this;
	}

	/**
	 * Sets the background color to yellow.
	 *
	 * @return static
	 */
	public function backgroundYellow(): static
	{
		$this->opt_bg = 'yellow';

		return $this;
	}

	/**
	 * Sets the background color to blue.
	 *
	 * @return static
	 */
	public function backgroundBlue(): static
	{
		$this->opt_bg = 'blue';

		return $this;
	}

	/**
	 * Sets the background color to magenta.
	 *
	 * @return static
	 */
	public function backgroundMagenta(): static
	{
		$this->opt_bg = 'magenta';

		return $this;
	}

	/**
	 * Sets the background color to cyan.
	 *
	 * @return static
	 */
	public function backgroundCyan(): static
	{
		$this->opt_bg = 'cyan';

		return $this;
	}

	/**
	 * Sets the background color to light gray.
	 *
	 * @return static
	 */
	public function backgroundLightGray(): static
	{
		$this->opt_bg = 'light_gray';

		return $this;
	}

	/**
	 * Sets bold style.
	 *
	 * @return static
	 */
	public function bold(): static
	{
		$this->opt_style = 'bold';

		return $this;
	}

	/**
	 * Sets dim style.
	 *
	 * @return static
	 */
	public function dim(): static
	{
		$this->opt_style = 'dim';

		return $this;
	}

	/**
	 * Sets underline style.
	 *
	 * @return static
	 */
	public function underline(): static
	{
		$this->opt_style = 'underline';

		return $this;
	}

	/**
	 * Sets blink style.
	 *
	 * @return static
	 */
	public function blink(): static
	{
		$this->opt_style = 'blink';

		return $this;
	}

	/**
	 * Sets invert style.
	 *
	 * @return static
	 */
	public function invert(): static
	{
		$this->opt_style = 'invert';

		return $this;
	}

	/**
	 * Sets hidden style.
	 *
	 * @return static
	 */
	public function hidden(): static
	{
		$this->opt_style = 'hidden';

		return $this;
	}
}
