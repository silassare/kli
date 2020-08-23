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
		'bold'         => '1',
		'dim'          => '2',
		'black'        => '0;30',
		'dark_gray'    => '1;30',
		'blue'         => '0;34',
		'light_blue'   => '1;34',
		'green'        => '0;32',
		'light_green'  => '1;32',
		'cyan'         => '0;36',
		'light_cyan'   => '1;36',
		'red'          => '0;31',
		'light_red'    => '1;31',
		'purple'       => '0;35',
		'light_purple' => '1;35',
		'brown'        => '0;33',
		'yellow'       => '1;33',
		'light_gray'   => '0;37',
		'white'        => '1;37',
		'normal'       => '0;39',
	];

	public static $background_colors = [
		'black'        => '40',
		'red'          => '41',
		'green'        => '42',
		'yellow'       => '43',
		'blue'         => '44',
		'magenta'      => '45',
		'cyan'         => '46',
		'light_gray'   => '47',
	];

	public static $styles = [
		'underline'     => '4',
		'blink'         => '5',
		'reverse'       => '7',
		'hidden'        => '8',
	];

	private $color;

	private $bg;

	private $style;

	public function string($string)
	{
		return self::color($string, $this->color, $this->bg, $this->style);
	}

	public function bold()
	{
		$this->color = 'bold';

		return $this;
	}

	public function dim()
	{
		$this->color = 'dim';

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

	public function purple()
	{
		$this->color = 'purple';

		return $this;
	}

	public function lightPurple()
	{
		$this->color = 'light_purple';

		return $this;
	}

	public function brown()
	{
		$this->color = 'brown';

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

	public function reverse()
	{
		$this->style = 'reverse';

		return $this;
	}

	public function hidden()
	{
		$this->style = 'hidden';

		return $this;
	}

	public static function color($string, $color = null, $bg = null, $style = null)
	{
		$style = '';

		if (\posix_isatty(\STDOUT)) {
			if (isset(self::$foreground_colors[$color])) {
				$style .= "\033[" . self::$foreground_colors[$color] . 'm';
			}

			if (isset(self::$background_colors[$bg])) {
				$style .= "\033[" . self::$background_colors[$bg] . 'm';
			}

			if (isset(self::$styles[$style])) {
				$style .= "\033[" . self::$styles[$style] . 'm';
			}
		}

		return $style ? $style . $string . "\033[0m" : $string;
	}
}
