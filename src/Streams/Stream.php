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

namespace Drewlabs\Collections\Streams;

use Drewlabs\Collections\Collectors\ArrayCollector;
use Drewlabs\Collections\Collectors\ReduceCollector;
use Drewlabs\Collections\Contracts\Arrayable;
use Drewlabs\Collections\Contracts\StreamInterface;
use Drewlabs\Core\Helpers\Iter;

class Stream implements \IteratorAggregate, StreamInterface, Arrayable
{
    use BaseStream;

    /**
     * Creates new class instance.
     *
     * @return void
     */
    private function __construct(\Traversable $source, bool $infinite = false)
    {
        $this->source = $source;
        $this->infinite = $infinite;
    }

    public static function of(\Traversable $source)
    {
        return new self($source);
    }

    /**
     * @param int|mixed $seed
     *
     * @return Stream
     */
    public static function iterate($seed, \Closure $callback)
    {
        $source = static function () use (&$seed, $callback) {
            yield $seed;
            while (true) {
                $seed = $callback($seed);
                yield $seed;
            }
        };

        return new self($source(), true);
    }

    /**
     * Create a stream from a range of values.
     *
     * @param int $steps
     *
     * @throws \LogicException
     *
     * @return Stream
     */
    public static function range(int $start, int $end, $steps = 1)
    {
        return new self(Iter::range($start, $end, $steps));
    }

    public function reduce(callable $reducer, $identity = null)
    {
        $this->_throwIfUnsafe();
        [$identity, $reducer] = 1 === \func_num_args() ? [0, $reducer] : [$identity, $reducer];

        return $this->collect(new ReduceCollector($reducer, $identity));
    }

    public function filter(callable $callback, $preserveKey = false)
    {
        $this->pipe[] = static function ($source) use ($callback) {
            return Operator::create()(StreamInput::wrap($source->value, $callback($source->value)));
        };

        return $this;
    }

    public function map(callable $callback, $preserve = false)
    {
        $this->pipe[] = Operator::create($callback);

        return $this;
    }

    public function toArray(): array
    {
        return $this->collect(new ArrayCollector());
    }
}
