<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Collections\Utils;

class ValueResolver
{
    /**
     * @var string|int|\Closure
     */
    private $keyOrFn;

    /**
     * Creates class instance.
     *
     * @param mixed $keyOrFn
     */
    public function __construct($keyOrFn)
    {
        $this->keyOrFn = $keyOrFn;
    }

    /**
     * Resolve the value from the provided parameter.
     *
     * @param mixed $args
     *
     * @return mixed
     */
    public function __invoke($args)
    {
        $callback = !\is_string($this->keyOrFn) && \is_callable($this->keyOrFn) ? ($this->keyOrFn) : function ($arg) {
            return drewlabs_core_get($arg, $this->keyOrFn);
        };

        return $callback($args);
    }

    /**
     * Creates new class instance.
     *
     * @param mixed $keyOrFn
     *
     * @return ValueResolver
     */
    public static function new($keyOrFn)
    {
        return new self($keyOrFn);
    }
}
