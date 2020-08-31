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

class KliColor
{
	public static $foreground_colors = [
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

	public static $background_colors = [
		'black'      => '40',
		'red'        => '41',
		'green'      => '42',
		'yellow'     => '43',
		'blue'       => '44',
		'magenta'    => '45',
		'cyan'       => '46',
		'light_gray' => '47',
	];

	public static $styles       = [
		'bold'      => '1',
		'dim'       => '2',
		'underline' => '4',
		'blink'     => '5',
		'invert'    => '7',
		'hidden'    => '8',
	];

	public static $styles_reset = [
		'bold'      => '22',
		'dim'       => '22',
		'underline' => '24',
		'blink'     => '25',
		'invert'    => '27',
		'hidden'    => '28',
	];

	private static $foreground_reset = '39';

	private static $background_reset = '49';

	private $color;

	private $bg;

	private $style;

	private $box_options;

	public function string($string)
	{
		if ($this->box_options) {
			$string = KliUtils::margins(KliUtils::wrap($string), $this->box_options);
		}

		return self::color($string, $this->color, $this->bg, $this->style);
	}

	public function box(array $options = [
		'top'    => 1,
		'right'  => 4,
		'left'   => 4,
		'bottom' => 1,
	])
	{
		$this->box_options = $options;

		return $this;
	}

	public function black()
	{
		$this->color = 'black';

		return $this;
	}

	public function darkGray()
	{
		$this->color = 'dark_gray';

		return $this;
	}

	public function blue()
	{
		$this->color = 'blue';

		return $this;
	}

	public function lightBlue()
	{
		$this->color = 'light_blue';

		return $this;
	}

	public function green()
	{
		$this->color = 'green';

		return $this;
	}

	public function lightGreen()
	{
		$this->color = 'light_green';

		return $this;
	}

	public function cyan()
	{
		$this->color = 'cyan';

		return $this;
	}

	public function lightCyan()
	{
		$this->color = 'light_cyan';

		return $this;
	}

	public function red()
	{
		$this->color = 'red';

		return $this;
	}

	public function lightRed()
	{
		$this->color = 'light_red';

		return $this;
	}

	public function magenta()
	{
		$this->color = 'magenta';

		return $this;
	}

	public function lightMagenta()
	{
		$this->color = 'light_magenta';

		return $this;
	}

	public function yellow()
	{
		$this->color = 'yellow';

		return $this;
	}

	public function lightGray()
	{
		$this->color = 'light_gray';

		return $this;
	}

	public function white()
	{
		$this->color = 'white';

		return $this;
	}

	public function normal()
	{
		$this->color = 'normal';

		return $this;
	}

	public function backgroundBlack()
	{
		$this->bg = 'black';

		return $this;
	}

	public function backgroundRed()
	{
		$this->bg = 'red';

		return $this;
	}

	public function backgroundGreen()
	{
		$this->bg = 'green';

		return $this;
	}

	public function backgroundYellow()
	{
		$this->bg = 'yellow';

		return $this;
	}

	public function backgroundBlue()
	{
		$this->bg = 'blue';

		return $this;
	}

	public function backgroundMagenta()
	{
		$this->bg = 'magenta';

		return $this;
	}

	public function backgroundCyan()
	{
		$this->bg = 'cyan';

		return $this;
	}

	public function backgroundLightGray()
	{
		$this->bg = 'light_gray';

		return $this;
	}

	public function bold()
	{
		$this->style = 'bold';

		return $this;
	}

	public function dim()
	{
		$this->style = 'dim';

		return $this;
	}

	public function underline()
	{
		$this->style = 'underline';

		return $this;
	}

	public function blink()
	{
		$this->style = 'blink';

		return $this;
	}

	public function invert()
	{
		$this->style = 'invert';

		return $this;
	}

	public function hidden()
	{
		$this->style = 'hidden';

		return $this;
	}

	/**
	 * Adds color to a given string.
	 *
	 * @param string $string
	 * @param string $color
	 * @param string $bg
	 * @param string $style
	 *
	 * @return string
	 */
	public static function color($string, $color = null, $bg = null, $style = null)
	{
		$set   = [];
		$reset = [];

		if (\stream_isatty(\STDOUT)) {
			if (isset(self::$foreground_colors[$color])) {
				$set[]   = self::$foreground_colors[$color];
				$reset[] = self::$foreground_reset;
			}

			if (isset(self::$background_colors[$bg])) {
				$set[]   = self::$background_colors[$bg];
				$reset[] = self::$background_reset;
			}

			if (isset(self::$styles[$style])) {
				$set[]   = self::$styles[$style];
				$reset[] = self::$styles_reset[$style];
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
