<?php

/**
 * Copyright (c) 2017-present, Emile Silas Sare
 *
 * This file is part of OZone (O'Zone) package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kli\Types;

use Kli\Exceptions\KliException;
use Kli\Exceptions\KliInputException;

class KliTypePath implements KliType
{
    private $min            = 1;

    private $max;

    private $multi          = false;

    private $glob           = false;

    private $is_file        = true;

    private $is_dir         = true;

    private $is_writable    = false;

    private $reg;

    private $error_messages = [
    'msg_require_valid_path'    => 'option "-%s" require valid path.',
    'msg_require_writable_path' => 'option "-%s" require writable path.',
    'msg_require_file_path'     => 'option "-%s" require file.',
    'msg_require_dir_path'      => 'option "-%s" require directory.',
    'msg_path_count_lt_min'     => 'option "-%s" require minimum %d path(s) (found=%d).',
    'msg_path_count_gt_max'     => 'option "-%s" require maximum %d path(s) (found=%d).',
    'msg_pattern_check_fails'   => '"%s" fails on regular expression for option "-%s".',
    ];

    /**
     * KliTypePath constructor.
     *
     * @param null|int $min the minimum path count
     * @param null|int $max the maximum path count
     *
     * @throws \Kli\Exceptions\KliException
     */
    public function __construct($min = null, $max = null)
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
     * @param int         $value         the minimum path count
     * @param null|string $error_message the error message
     *
     * @throws \Kli\Exceptions\KliException
     *
     * @return $this
     */
    public function min($value, $error_message = null)
    {
        if (!\is_int($value) || $value < 1) {
            throw new KliException(\sprintf('"%s" is not a valid integer(>0).', $value));
        }

        if (isset($this->max) && $value > $this->max) {
            throw new KliException(\sprintf('min=%s and max=%s is not a valid condition.', $value, $this->max));
        }

        $this->min = $value;

        return $this->customErrorMessage('msg_path_count_lt_min', $error_message);
    }

    /**
     * Sets maximum path count.
     *
     * @param int         $value         the maximum path count
     * @param null|string $error_message the error message
     *
     * @throws \Kli\Exceptions\KliException
     *
     * @return $this
     */
    public function max($value, $error_message = null)
    {
        if (!\is_int($value) || $value < 1) {
            throw new KliException(\sprintf('"%s" is not a valid integer(>0).', $value));
        }

        if ($value < $this->min) {
            throw new KliException(\sprintf('min=%s and max=%s is not a valid condition.', $this->min, $value));
        }

        $this->max = $value;

        return $this->customErrorMessage('msg_path_count_gt_max', $error_message);
    }

    /**
     * Sets the path pattern.
     *
     * @param string      $pattern       the pattern (regular expression)
     * @param null|string $error_message the error message
     *
     * @throws \Kli\Exceptions\KliException
     *
     * @return $this
     */
    public function pattern($pattern, $error_message = null)
    {
        if (false === \preg_match($pattern, null)) {
            throw new KliException(\sprintf('invalid regular expression: %s', $pattern));
        }

        $this->reg = $pattern;

        return $this->customErrorMessage('msg_pattern_check_fails', $error_message);
    }

    /**
     * Allow multiple path.
     *
     * @return $this
     */
    public function multiple()
    {
        $this->multi = true;

        return $this;
    }

    /**
     * Accept file path only.
     *
     * @param null|string $error_message the error message
     *
     * @return $this
     */
    public function file($error_message = null)
    {
        $this->is_file = true;
        $this->is_dir  = false;

        return $this->customErrorMessage('msg_require_file_path', $error_message);
    }

    /**
     * Accept directory path only.
     *
     * @param null|string $error_message the error message
     *
     * @return $this
     */
    public function dir($error_message = null)
    {
        $this->is_file = false;
        $this->is_dir  = true;

        return $this->customErrorMessage('msg_require_dir_path', $error_message);
    }

    /**
     * Accept writable path only.
     *
     * @param null|string $error_message the error message
     *
     * @return $this
     */
    public function writable($error_message = null)
    {
        $this->is_writable = true;

        return $this->customErrorMessage('msg_require_writable_path', $error_message);
    }

    /**
     * @inheritdoc
     */
    public function validate($opt_name, $value)
    {
        $paths = $this->resolvePath($value);

        if (!\count($paths) || $paths[0] === false) {
            throw new KliInputException(\sprintf($this->error_messages['msg_require_valid_path'], $opt_name));
        }

        if (isset($this->reg)) {
            $paths = $this->filterReg($paths);

            if (!\count($paths)) {
                throw new KliInputException(\sprintf($this->error_messages['msg_pattern_check_fails'], $value, $opt_name));
            }
        }

        // directory only
        if (!$this->is_file) {
            $paths = \array_filter($paths, 'is_dir');

            if (!\count($paths)) {
                throw new KliInputException(\sprintf($this->error_messages['msg_require_dir_path'], $opt_name));
            }
        }

        // file only
        if (!$this->is_dir) {
            $paths = \array_filter($paths, 'is_file');

            if (!\count($paths)) {
                throw new KliInputException(\sprintf($this->error_messages['msg_require_file_path'], $opt_name));
            }
        }

        // writable only
        if ($this->is_writable) {
            $paths = \array_filter($paths, 'is_writable');

            if (!\count($paths)) {
                throw new KliInputException(\sprintf($this->error_messages['msg_require_writable_path'], $opt_name));
            }
        }

        $c = \count($paths);

        if ($c < $this->min) {
            throw new KliInputException(\sprintf($this->error_messages['msg_path_count_lt_min'], $opt_name, $this->min, $c));
        }

        if (isset($this->max) && $c > $this->max) {
            throw new KliInputException(\sprintf($this->error_messages['msg_path_count_gt_max'], $opt_name, $this->max, $c));
        }

        return $this->multi ? $paths : $paths[0];
    }

    /**
     * Sets custom error message
     *
     * @param string $key     the error key
     * @param string $message the error message
     *
     * @return $this
     */
    private function customErrorMessage($key, $message)
    {
        if (!empty($message)) {
            $this->error_messages[$key] = $message;
        }

        return $this;
    }

    /**
     * Resolve path use glob if enabled.
     *
     * @param string $path the path to resolve
     *
     * @return array path list
     */
    private function resolvePath($path)
    {
        if (!\is_string($path)) {
            return [];
        }

        return ($this->glob) ? \glob($path) : [\realpath($path)];
    }

    /**
     * Filters path list with regular expression.
     *
     * @param array $list the path list to filter
     *
     * @return array path list
     */
    private function filterReg(array $list)
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
