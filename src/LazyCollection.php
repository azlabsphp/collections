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

namespace Drewlabs\Collections;

use Drewlabs\Collections\Collectors\ArrayCollector;
use Drewlabs\Collections\Collectors\ReduceCollector;
use Drewlabs\Collections\Contracts\Enumerable;
use Drewlabs\Collections\Utils\DefaultValue;
use Drewlabs\Collections\Utils\ValueResolver;
use Drewlabs\Core\Helpers\Functional;
use Drewlabs\Core\Helpers\ImmutableDateTime;
use Drewlabs\Core\Helpers\Iter;
use Iterator;

class LazyCollection implements \IteratorAggregate, Enumerable
{
    /**
     * @var \Iterator|\iterable
     */
    private $source;

    /**
     * @param \iterable|\Iterator $values
     *
     * @return self
     */
    public function __construct($values = [])
    {
        if (\is_array($values)) {
            $this->source = new \ArrayIterator($values);
        } elseif ($values instanceof self) {
            $this->source = $this->getIterator();
        } else {
            $this->source = $values;
        }
    }

    public function each(callable $callback)
    {
        foreach ($this->source as $key => $value) {
            $callback($value, $key);
        }
    }

    public function reduce(callable $reducer, $identity = null)
    {
        return (new ReduceCollector($reducer, $identity))->__invoke($this->source);
    }

    public function firstOrFail($key = null, $operator = null, $value = null)
    {
    }

    public function last($default = null)
    {
        $defaultValue = new DefaultValue();
        $last = $defaultValue;
        foreach ($this->source as $value) {
            $last = $value;
        }

        return $last === $defaultValue ? (Functional::isCallable($default) ? $default() : $default) : $last;
    }

    public function toArray(): array
    {
        return (new ArrayCollector())->__invoke($this->source);
    }

    public function take(int $number)
    {
        $fn = static function ($source) use ($number) {
            $index = 0;
            foreach ($source as $current) {
                ++$index;
                if ($index > $number) {
                    break;
                }
                yield $current;
            }
        };
        $this->source = $fn($this->source);

        return $this;
    }

    public function skip(int $number)
    {
        $fn = static function ($source) use ($number) {
            $index = 0;
            foreach ($source as $current) {
                ++$index;
                if ($index <= $number) {
                    continue;
                }
                yield $current;
            }
        };
        $this->source = $fn($this->source);

        return $this;
    }

    public static function empty()
    {
        $self = new static([]);

        return $self;
    }

    /**
     * @throws \Exception
     *
     * @return \Iterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator(): \Traversable
    {
        return $this->makeIterator($this->source);
    }

    public function takeUntil($value)
    {
        $callback = !\is_string($value) && \is_callable($value) ? $value : (static function ($item) use ($value) {
            return $item === $value;
        });

        return new static(function () use ($callback) {
            foreach ($this as $key => $item) {
                if ($callback($item, $key)) {
                    break;
                }
                yield $key => $item;
            }
        });
    }

    /**
     * Chunk the collection into chunks with a callback.
     *
     * @return static
     */
    public function chunkWhile(callable $callback)
    {
        return new static(function () use ($callback) {
            $iterator = $this->getIterator();
            $chunk = new Collection();
            if ($iterator->valid()) {
                $chunk[$iterator->key()] = $iterator->current();
                $iterator->next();
            }

            while ($iterator->valid()) {
                if (!$callback($iterator->current(), $iterator->key(), $chunk)) {
                    yield new Collection($chunk);
                    $chunk = new Collection();
                }
                $chunk[$iterator->key()] = $iterator->current();
                $iterator->next();
            }

            if ($chunk->isNotEmpty()) {
                yield new Collection($chunk);
            }
        });
    }

    /**
     * Get all items in the enumerable.
     *
     * @return array
     */
    public function all()
    {
        if (\is_array($this->source)) {
            return $this->source;
        }

        return iterator_to_array($this->getIterator());
    }

    /**
     * Take items in the collection until a given point in time.
     *
     * @return static
     */
    public function takeUntilTimeout(\DateTimeInterface $timeout)
    {
        return $this->takeWhile(static function () use ($timeout) {
            return ImmutableDateTime::isfuture($timeout);
        });
    }

    public function takeWhile($value, bool $flexible = true)
    {
        $callback = !\is_string($value) && \is_callable($value) ? $value : (static function ($item) use ($value) {
            return $item === $value;
        });

        return $this->takeUntil(static function ($item, $key) use ($callback) {
            return !$callback($item, $key);
        });
    }

    /**
     * Count the number of items in the collection by a field or using a callback.
     *
     * @param callable|string $countBy
     *
     * @return static
     */
    public function countBy($countBy = null)
    {
        $countBy = null === $countBy ? static function ($value) {
            return $value;
        }
        : ValueResolver::new($countBy);

        return new static(function () use ($countBy) {
            $counts = [];
            foreach ($this as $key => $value) {
                $group = $countBy($value, $key);
                if (empty($counts[$group])) {
                    $counts[$group] = 0;
                }
                ++$counts[$group];
            }

            yield from $counts;
        });
    }

    public function mapInto(string $class)
    {
        return $this->map(static function ($value, $key) use ($class) {
            return new $class($value, $key);
        });
    }

    public function count()
    {
        return \count($this->all());
    }

    /**
     * Apply the transformation callback over each item element.
     *
     * @param Closure|callable $callback
     *
     * @return self
     */
    public function map($callback, $preserveKey = true)
    {
        if (!($callback instanceof \Closure) || !\is_callable($callback)) {
            throw new \InvalidArgumentException(
                'Expect parameter 1 to be an instance of \Closure, or php callable, got : '.\gettype($callback)
            );
        }

        return new static(Iter::map($this->getIterator(), $callback, $preserveKey));
    }

    public function filter($callback, $preserveKey = true)
    {
        if (!($callback instanceof \Closure) || !\is_callable($callback)) {
            throw new \InvalidArgumentException(
                'Expect parameter 1 to be an instance of \Closure, or php callable, got : '.\gettype($callback)
            );
        }

        return new static(Iter::map($this->getIterator(), $callback, $preserveKey));
    }

    public function first($value = null, $default = null)
    {
        if (null === $value) {
            foreach ($this->getIterator() as $value) {
                return $value;
            }
        }
        $callback = !\is_string($value) && \is_callable($value) ? $value : (static function ($item) use ($value) {
            return $item === $value;
        });
        foreach ($this->getIterator() as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default instanceof \Closure ? $default() : $default;
    }

    /**
     * Skip items in the collection until the given condition is met.
     *
     * @param mixed $value
     *
     * @return static
     */
    public function skipUntil($value)
    {
        $callback = !\is_string($value) && \is_callable($value) ? $value : (static function ($item) use ($value) {
            return $item === $value;
        });

        return $this->skipWhile(static function (...$params) use ($callback) {
            return !$callback(...$params);
        });
    }

    /**
     * Skip items in the collection while the given condition is met.
     *
     * @param mixed $value
     *
     * @return static
     */
    public function skipWhile($value)
    {
        $callback = !\is_string($value) && \is_callable($value) ? $value : (static function ($item) use ($value) {
            return $item === $value;
        });

        return new static(function () use ($callback) {
            $iterator = $this->getIterator();
            while ($iterator->valid() && $callback($iterator->current(), $iterator->key())) {
                $iterator->next();
            }
            while ($iterator->valid()) {
                yield $iterator->key() => $iterator->current();
                $iterator->next();
            }
        });
    }

    /**
     * Make an iterator from the given source.
     *
     * @param mixed $source
     *
     * @return \Iterator
     */
    private function makeIterator($source)
    {
        if ($source instanceof \Iterator) {
            return $this->source;
        }
        if ($source instanceof \IteratorAggregate) {
            return $source->getIterator();
        }
        if (\is_array($source)) {
            return new \ArrayIterator($source);
        }

        return $source();
    }
}
