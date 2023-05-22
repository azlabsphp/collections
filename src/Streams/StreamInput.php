<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Collections\Streams;

class StreamInput
{
    /**
     * @var mixed
     */
    public $value;

    /**
     * @var \Closure|bool
     */
    private $predicate;

    /**
     * @param mixed         $value
     * @param bool|callable $predicate
     *
     * @return void
     */
    public function __construct($value, $predicate)
    {
        $this->value = $value;
        $this->predicate = $predicate;
    }

    public function accepts()
    {
        return \is_bool($this->predicate) ?
            $this->predicate :
            \call_user_func($this->predicate, $this->value);
    }

    public static function wrap($source, $accepts = true)
    {
        return new self($source, $accepts);
    }
}
