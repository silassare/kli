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
 * Class KliColor.
 */
class KliColor
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

	private static string $foreground_reset = '39';

	private static string $background_reset = '49';

	private string $opt_color;

	private string $opt_bg;

	private string $opt_style;

	private array $box_options;

	public function string(string $string): string
	{
		if (isset($this->box_options)) {
			$string = KliUtils::margins(KliUtils::wrap($string), $this->box_options);
		}

		return self::color($string, $this->opt_color ?? null, $this->opt_bg ?? null, $this->opt_style ?? null);
	}

	public function box(array $options = [
		'top'    => 1,
		'right'  => 4,
		'left'   => 4,
		'bottom' => 1,
	]): self
	{
		$this->box_options = $options;

		return $this;
	}

	public function black(): self
	{
		$this->opt_color = 'black';

		return $this;
	}

	public function darkGray(): self
	{
		$this->opt_color = 'dark_gray';

		return $this;
	}

	public function blue(): self
	{
		$this->opt_color = 'blue';

		return $this;
	}

	public function lightBlue(): self
	{
		$this->opt_color = 'light_blue';

		return $this;
	}

	public function green(): self
	{
		$this->opt_color = 'green';

		return $this;
	}

	public function lightGreen(): self
	{
		$this->opt_color = 'light_green';

		return $this;
	}

	public function cyan(): self
	{
		$this->opt_color = 'cyan';

		return $this;
	}

	public function lightCyan(): self
	{
		$this->opt_color = 'light_cyan';

		return $this;
	}

	public function red(): self
	{
		$this->opt_color = 'red';

		return $this;
	}

	public function lightRed(): self
	{
		$this->opt_color = 'light_red';

		return $this;
	}

	public function magenta(): self
	{
		$this->opt_color = 'magenta';

		return $this;
	}

	public function lightMagenta(): self
	{
		$this->opt_color = 'light_magenta';

		return $this;
	}

	public function yellow(): self
	{
		$this->opt_color = 'yellow';

		return $this;
	}

	public function lightGray(): self
	{
		$this->opt_color = 'light_gray';

		return $this;
	}

	public function white(): self
	{
		$this->opt_color = 'white';

		return $this;
	}

	public function normal(): self
	{
		$this->opt_color = 'normal';

		return $this;
	}

	public function backgroundBlack(): self
	{
		$this->opt_bg = 'black';

		return $this;
	}

	public function backgroundRed(): self
	{
		$this->opt_bg = 'red';

		return $this;
	}

	public function backgroundGreen(): self
	{
		$this->opt_bg = 'green';

		return $this;
	}

	public function backgroundYellow(): self
	{
		$this->opt_bg = 'yellow';

		return $this;
	}

	public function backgroundBlue(): self
	{
		$this->opt_bg = 'blue';

		return $this;
	}

	public function backgroundMagenta(): self
	{
		$this->opt_bg = 'magenta';

		return $this;
	}

	public function backgroundCyan(): self
	{
		$this->opt_bg = 'cyan';

		return $this;
	}

	public function backgroundLightGray(): self
	{
		$this->opt_bg = 'light_gray';

		return $this;
	}

	public function bold(): self
	{
		$this->opt_style = 'bold';

		return $this;
	}

	public function dim(): self
	{
		$this->opt_style = 'dim';

		return $this;
	}

	public function underline(): self
	{
		$this->opt_style = 'underline';

		return $this;
	}

	public function blink(): self
	{
		$this->opt_style = 'blink';

		return $this;
	}

	public function invert(): self
	{
		$this->opt_style = 'invert';

		return $this;
	}

	public function hidden(): self
	{
		$this->opt_style = 'hidden';

		return $this;
	}

	/**
	 * Adds color to a given string.
	 *
	 * @param string      $string
	 * @param null|string $color
	 * @param null|string $bg
	 * @param null|string $style
	 *
	 * @return string
	 */
	public static function color(
		string $string,
		?string $color = null,
		?string $bg = null,
		?string $style = null
	): string {
		$set   = [];
		$reset = [];

		if (\stream_isatty(\STDOUT)) {
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

			return $set . $string . $reset;
		}

		return $string;
	}
}
