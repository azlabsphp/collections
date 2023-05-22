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

class Operator
{
    /**
     * @var callable|\Closure|null
     */
    private $callback;

    /**
     * Creates class instance.
     *
     * @param callable $callback
     */
    public function __construct(callable $callback = null)
    {
        $this->callback = $callback;
    }

    public function __invoke($data)
    {
        if ($accepts = (bool) $data->accepts()) {
            return null === $this->callback ? $data : StreamInput::wrap(\is_string($this->callback) ? \call_user_func($this->callback, $data->value) : ($this->callback)($data->value), $accepts);
        }

        return $data;
    }

    /**
     * Creates an instance of the operator class.
     *
     * @param callable|\Closure|null $callback
     *
     * @return self
     */
    public static function create($callback = null)
    {
        return new self($callback);
    }
}
