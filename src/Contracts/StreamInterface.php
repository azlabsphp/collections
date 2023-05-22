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

interface StreamInterface extends Collectable, Enumerable
{
    /**
     * {@inheritDoc}
     *
     * Project enumerable values using provided callback
     *
     * @return self
     */
    public function map(callable $callback);

    /**
     * {@inheritDoc}
     *
     * Filters collection item using `$predicate`
     *
     * @return self
     */
    public function filter(callable $predicate);

    /**
     * Set a reducer that should be applied to a stream data. If the identity value
     * is omitted, meaning if the only a single parameter is passed, the parameter is
     * consider to be the reducer function.
     *
     * ```php
     *
     * $result = $stream->take(10)
     *              ->reduce(static function ($carry, $current) {
     *                      $carry += $current;
     *                      return $carry;
     *              });
     *
     * // Is same as
     * $result = $stream->take(10)
     *              ->reduce(static function ($carry, $current) {
     *                      $carry += $current;
     *                      return $carry;
     *              }, 0);
     * ```
     *
     * @param \Closure<R,T,R>|null $reducer
     * @param mixed|callable       $identity
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function reduce(callable $reducer, $identity = null);

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
     * {@inheritDoc}
     *
     * Takes stream data while a value is true.
     *
     * By default, stream is dropped if flexible=NULL|false. Else the only data
     * not matching the condition are dropped.
     *
     * Note: Becareful when running in flexible mode, to avoid undesireable results
     *
     * @param mixed $value
     * @param bool  $flexible
     *
     * @return self|Arrayable
     */
    public function takeWhile($value, $flexible = true);

    /**
     * {@inheritDoc}
     *
     * Set an offset on the number of stream data.
     *
     * @param mixed $n
     *
     * @return self|Arrayable
     */
    public function skip(int $n);

    /**
     * Method to apply an executor to each item in the stream.
     *
     * @return void
     */
    public function each(callable $callback);

    /**
     * Returns the first element of the stream or the default value if missing.
     *
     * @param callable|mixed $default
     *
     * @return mixed
     */
    public function firstOr($default = null);

    /**
     * {@inheritDoc}
     *
     * Map the values into a new class.
     *
     * @return self
     */
    public function mapInto(string $class);
}
