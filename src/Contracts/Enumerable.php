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

namespace Drewlabs\Collections\Contracts;

interface Enumerable extends \IteratorAggregate
{
    /**
     * Project enumerable values using provided callback.
     *
     * @return self
     */
    public function map(callable $callback);

    /**
     * Filters collection item using `$predicate`.
     *
     * @return self
     */
    public function filter(callable $predicate);

    /**
     * Map the values into a new class.
     *
     * @return self
     */
    public function mapInto(string $class);

    /**
     * Loop through enumerable values and invoke $callback on each of them.
     *
     * @throws RuntimeException
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function each(callable $callback);

    /**
     * Reduce collection items using the callback function.
     *
     * @param mixed $init
     *
     * @return mixed
     */
    public function reduce(callable $callback, $init = null);

    /**
     * Get the first item in the collection but throw an exception if no matching items exist.
     *
     * @param mixed $key
     * @param mixed $operator
     * @param mixed $value
     *
     * @throws NotFoundException
     *
     * @return mixed
     */
    public function firstOrFail($key = null, $operator = null, $value = null);

    /**
     * Returns the firt item in the collection matching user specify callback if any.
     *
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function first($value = null, $default = null);

    /**
     * Returns the last item in the collection.
     *
     * @return mixed
     */
    public function last();

    /**
     * Serialize the instance into it array representation.
     */
    public function toArray(): array;

    /**
     * {@inheritDoc}
     *
     * Set a limit on the number of stream data.
     *
     * @return $this
     */
    public function take(int $n);

    /**
     * {@inheritDoc}
     *
     * Operator to process a stream data until a condition is met.
     *
     * @param callable|mixed $value
     *
     * @return self|Arrayable
     */
    public function takeUntil($value);

    /**
     * Takes stream data while a value is or return true.
     *
     * @param mixed $value
     *
     * @return self|Arrayable
     */
    public function takeWhile($value, bool $flexible = true);

    /**
     * Set an offset on the number of stream data.
     *
     * @param mixed $n
     *
     * @return self|Arrayable
     */
    public function skip(int $n);
}
