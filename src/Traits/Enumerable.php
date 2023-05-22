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

namespace Drewlabs\Collections\Traits;

use CachingIterator;
use Drewlabs\Collections\Collection;
use Drewlabs\Collections\SimpleCollection;
use Drewlabs\Collections\Utils\CompareValueFactory;
use Drewlabs\Collections\Utils\HigherOrderProxy;
use Drewlabs\Collections\Utils\HigherOrderWhenProxy;
use Drewlabs\Collections\Utils\ValueResolver;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Functional;

// Should review : strictContains(), eachSpread(), mapSpread(), slice(), collapse()

trait Enumerable
{
    /**
     * The methods that can be proxied.
     *
     * @var string[]
     */
    protected static $proxies = [
        'average',
        'avg',
        'contains',
        'each',
        'every',
        'filter',
        'first',
        'flatMap',
        'groupBy',
        'keyBy',
        'map',
        'max',
        'min',
        'partition',
        'reject',
        'skipUntil',
        'skipWhile',
        'some',
        'sortBy',
        'sortByDesc',
        'sum',
        'takeUntil',
        'takeWhile',
        'unique',
        'until',
    ];

    /**
     * Dynamically access collection proxies.
     *
     * @param string $proxy
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function __get($proxy)
    {
        if (!\in_array($proxy, static::$proxies, true)) {
            throw new \Exception("Property [{$proxy}] does not exist on this collection instance.");
        }

        return new HigherOrderProxy($this, $proxy);
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Create a new instance with no items.
     *
     * @return static
     */
    public static function empty()
    {
        return new self([]);
    }

    /**
     * Alias for the "avg" method.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function average($callback = null)
    {
        return $this->avg($callback);
    }

    /**
     * Get the average value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function avg($callback = null)
    {
        $callback = ValueResolver::new($callback);
        $items = $this->map(static function ($value) use ($callback) {
            return $callback($value);
        })->filter(static function ($value) {
            return null !== $value;
        });
        if ($count = $items->count()) {
            return $items->sum() / $count;
        }
    }

    /**
     * Get the sum of the given values.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function sum($callback = null)
    {
        $callback = null === $callback ? static function ($value) {
            return $value;
        }
        : ValueResolver::new($callback);

        return $this->reduce(static function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * Determine if an item exists, using strict comparison.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return bool
     */
    public function containsStrict($key, $value = null)
    {
        if (2 === \func_num_args()) {
            return $this->contains(static function ($item) use ($key, $value) {
                return drewlabs_core_get($item, $key) === $value;
            });
        }

        if (!\is_string($key) && \is_callable($key)) {
            return null !== $this->first($key);
        }

        foreach ($this as $item) {
            if ($item === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Execute a callback over each nested chunk of items.
     *
     * @return void
     */
    public function eachSpread(callable $callback)
    {
        return $this->each(static function ($chunk, $key) use ($callback) {
            $chunk[] = $key;

            return $callback(...$chunk);
        });
    }

    /**
     * Determine if all items pass the given truth test.
     *
     * @param string|callable $key
     * @param mixed           $operator
     * @param mixed           $value
     *
     * @return bool
     */
    public function every($key, $operator = null, $value = null)
    {
        if (1 === \func_num_args()) {
            $callback = ValueResolver::new($key);
            foreach ($this as $k => $v) {
                if (!$callback($v, $k)) {
                    return false;
                }
            }

            return true;
        }

        return $this->every(CompareValueFactory::new(...\func_get_args()));
    }

    /**
     * Get the first item by the given key value pair.
     *
     * @param string $key
     * @param mixed  $operator
     * @param mixed  $value
     *
     * @return mixed
     */
    public function firstWhere($key, $operator = null, $value = null)
    {
        return $this->first(CompareValueFactory::new(...\func_get_args()));
    }

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    /**
     * Run a map over each nested chunk of items.
     *
     * @return static
     */
    public function mapSpread(callable $callback, bool $preserve_key = true)
    {
        return $this->map(static function ($chunk, $key) use ($callback) {
            $chunk = Arr::wrap($chunk);
            $chunk[] = $key;

            return $callback(...$chunk);
        }, $preserve_key);
    }

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @param callable|\Closure|mixed $callback
     *
     * @return static
     */
    public function flatMap($callback)
    {
        return $this->map($callback)->collapse();
    }

    /**
     * Map the values into a new class.
     *
     * @param string $class
     *
     * @return static
     */
    public function mapInto($class)
    {
        return $this->map(static function ($value, $key) use ($class) {
            return new $class($value, $key);
        });
    }

    /**
     * Get the min value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = ValueResolver::new($callback);

        return $this->map(static function ($value) use ($callback) {
            return $callback($value);
        })->filter(static function ($value) {
            return null !== $value;
        })->reduce(static function ($result, $value) {
            return (null === $result) || ($value < $result) ? $value : $result;
        });
    }

    /**
     * Get the max value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = ValueResolver::new($callback);

        return $this->filter(static function ($value) {
            return null !== $value;
        })->reduce(static function ($result, $item) use ($callback) {
            $value = $callback($item);

            return (null === $result) || ($value > $result) ? $value : $result;
        });
    }

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @return static
     */
    public function forPage(int $page, int $perPage = 20, ?bool $preserve_key = true)
    {
        $offset = max(0, ($page - 1) * $perPage);

        return $this->slice($offset, $perPage, $preserve_key);
    }

    /**
     * Apply the callback if the value is truthy.
     *
     * @param bool|mixed $value
     *
     * @return static|mixed
     */
    public function when($value, callable $callback = null, callable $default = null)
    {
        if (null === $callback) {
            return new HigherOrderWhenProxy($this, $value);
        }
        if ($value) {
            return $callback($this, $value);
        } elseif ($default) {
            return $default($this, $value);
        }

        return $this;
    }

    /**
     * Apply the callback if the collection is empty.
     *
     * @return static|mixed
     */
    public function whenEmpty(callable $callback = null, callable $default = null)
    {
        return $this->when($this->isEmpty(), $callback, $default);
    }

    /**
     * Apply the callback if the collection is not empty.
     *
     * @return static|mixed
     */
    public function whenNotEmpty(callable $callback = null, callable $default = null)
    {
        return $this->when($this->isNotEmpty(), $callback, $default);
    }

    /**
     * Apply the callback if the value is falsy.
     *
     * @param bool $value
     *
     * @return static|mixed
     */
    public function unless($value, callable $callback = null, callable $default = null)
    {
        return $this->when(!$value, $callback, $default);
    }

    /**
     * Apply the callback unless the collection is empty.
     *
     * @return static|mixed
     */
    public function unlessEmpty(callable $callback = null, callable $default = null)
    {
        return $this->whenNotEmpty($callback, $default);
    }

    /**
     * Apply the callback unless the collection is not empty.
     *
     * @return static|mixed
     */
    public function unlessNotEmpty(callable $callback = null, callable $default = null)
    {
        return $this->whenEmpty($callback, $default);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed  $operator
     * @param mixed  $value
     *
     * @return static
     */
    public function where($key, $operator = null, $value = null)
    {
        return $this->filter(CompareValueFactory::new(...\func_get_args()));
    }

    /**
     * Filter items where the value for the given key is null.
     *
     * @param string|null $key
     *
     * @return static
     */
    public function whereNull($key = null)
    {
        return $this->whereStrict($key, null);
    }

    /**
     * Filter items where the value for the given key is not null.
     *
     * @param string|null $key
     *
     * @return static
     */
    public function whereNotNull($key = null)
    {
        return $this->where($key, '!==', null);
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return static
     */
    public function whereStrict($key, $value)
    {
        return $this->where($key, '===', $value);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed  $values
     * @param bool   $strict
     *
     * @return static
     */
    public function whereIn($key, $values, $strict = false)
    {
        return $this->filter(
            static function ($item) use ($key, $values, $strict) {
                return \in_array(drewlabs_core_get($item, $key), Arr::create($values), $strict);
            }
        );
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed  $values
     *
     * @return static
     */
    public function whereInStrict($key, $values)
    {
        return $this->whereIn($key, $values, true);
    }

    /**
     * Filter items such that the value of the given key is between the given values.
     *
     * @param string $key
     * @param array  $values
     *
     * @return static
     */
    public function whereBetween($key, $values)
    {
        return $this->where($key, '>=', reset($values))
            ->where($key, '<=', end($values));
    }

    /**
     * Filter items such that the value of the given key is not between the given values.
     *
     * @param string $key
     * @param array  $values
     *
     * @return static
     */
    public function whereNotBetween($key, $values)
    {
        return $this->filter(
            static function ($item) use ($key, $values) {
                return drewlabs_core_get($item, $key) < reset($values) || drewlabs_core_get($item, $key) > end($values);
            }
        );
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed  $values
     * @param bool   $strict
     *
     * @return static
     */
    public function whereNotIn($key, $values, $strict = false)
    {
        return $this->reject(
            static function ($item) use ($key, $values, $strict) {
                return \in_array(drewlabs_core_get($item, $key), Arr::create($values), $strict);
            }
        );
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed  $values
     *
     * @return static
     */
    public function whereNotInStrict($key, $values)
    {
        return $this->whereNotIn($key, $values, true);
    }

    /**
     * Filter the items, removing any items that don't match the given type(s).
     *
     * @return static
     */
    public function whereInstanceOf(...$types)
    {
        return $this->filter(static function ($value) use ($types) {
            foreach ($types as $classType) {
                if ($value instanceof $classType) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Pass the collection to the given callback and return the result.
     *
     * @param callable[]|\Closure[] $callback
     *
     * @return mixed
     */
    public function pipe(...$callback)
    {
        return Functional::compose(...$callback)($this);
    }

    /**
     * Pass the collection into a new class.
     *
     * @return mixed
     */
    public function pipeInto(string $class)
    {
        return new $class($this);
    }

    /**
     * Pass the collection to the given callback and then return it.
     *
     * @return $this
     */
    public function tap(callable $callback)
    {
        // Clone the collection in order to not modify it
        $callback(new static($this));

        return $this;
    }

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param callable|mixed $callback
     *
     * @return static
     */
    public function reject($callback = true)
    {
        $as_callback = !\is_string($callback) && \is_callable($callback);

        return $this->filter(
            static function ($value, $key) use ($callback, $as_callback) {
                return $as_callback
                    ? !$callback($value, $key)
                    : $value !== $callback;
            }
        );
    }

    /**
     * Return only unique items from the collection array using strict comparison.
     *
     * @param string|callable|null $key
     *
     * @return static
     */
    public function uniqueStrict($key = null)
    {
        return $this->unique($key, true);
    }

    /**
     * Collect the values into a collection.
     *
     * @return SimpleCollection
     */
    public function collect()
    {
        return new Collection($this->all());
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_map(
            static function ($value) {
                if ($value instanceof \JsonSerializable) {
                    return $value->jsonSerialize();
                } elseif (method_exists($value, 'toJson')) {
                    return json_decode($value->toJson(), true);
                } elseif (method_exists($value, 'toArray')) {
                    return $value->toArray();
                }

                return $value;
            },
            $this->all()
        );
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get a CachingIterator instance.
     *
     * @param int $flags
     *
     * @return \CachingIterator
     */
    public function getCachingIterator($flags = \CachingIterator::CALL_TOSTRING)
    {
        return new \CachingIterator($this->getIterator(), $flags);
    }

    /**
     * Add a method to the list of proxied methods.
     *
     * @param string $method
     *
     * @return void
     */
    public static function proxy($method)
    {
        static::$proxies[] = $method;
    }
}
