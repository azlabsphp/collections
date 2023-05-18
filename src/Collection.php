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

namespace Drewlabs\Collections;

use Drewlabs\Collections\Contracts\CollectionInterface;
use Drewlabs\Collections\Exceptions\ValueNotFoundException;
use Drewlabs\Collections\Traits\Enumerable;
use Drewlabs\Collections\Traits\Sortable;
use Drewlabs\Collections\Utils\CompareValueFactory;
use Drewlabs\Collections\Utils\DefaultValue;
use Drewlabs\Collections\Utils\ValueResolver;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Functional;
use Drewlabs\Core\Helpers\Iter;
use Drewlabs\Overloadable\Overloadable;

final class Collection implements CollectionInterface, \ArrayAccess, \JsonSerializable
{
    use Enumerable;
    use Overloadable;
    use Sortable;

    /**
     * @var \ArrayIterator
     */
    private $items;

    /**
     * Keep tracks of the array keys.
     *
     * @var \ArrayIterator
     */
    private $keys;

    /**
     * Creates new class instance.
     *
     * @param array|CollectionInterface|\iterable $items
     *
     * @return mixed
     */
    public function __construct($items = [])
    {
        if (\is_array($items)) {
            return $this->setProperties($items);
        }

        if ($items instanceof CollectionInterface) {
            $this->setProperties($items->all());
        }

        if (is_iterable($items)) {
            return $this->setProperties(iterator_to_array($items));
        }
        $this->setProperties([]);
    }

    public function __clone()
    {
        $this->keys = clone $this->keys;
        $this->items = clone $this->items;
    }

    /**
     * @return self
     */
    public static function fromArray(array $items)
    {
        return new self($items);
    }

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @param mixed $items
     *
     * @return static
     */
    public static function make($items = [])
    {
        return new self($items);
    }

    public function add(...$args)
    {
        return $this->overload($args, [
            function ($key, $value) {
                $keysArray = iterator_to_array($this->keys);
                $last = Arr::last($keysArray);
                if ((null !== $last) && ((is_numeric($last) && !is_numeric($key)) || (!is_numeric($last) && is_numeric($key)))) {
                    throw new \InvalidArgumentException('For performance reason collection index must be either numeric or alphanumeric, not both');
                }
                if ($this->keys->offsetExists($key)) {
                    $key = Arr::search($key, iterator_to_array($this->keys));
                    $this->items[$key] = $value;
                } else {
                    $this->items[] = $value;
                    $this->keys[] = $key;
                }

                return $this;
            },
            function ($value) {
                $this->items[] = $value;
                $last = Arr::last(iterator_to_array($this->keys));
                if ((null !== $last) && !is_numeric($last)) {
                    throw new \InvalidArgumentException('For performance reason collection index must be either numeric or alphanumeric, not both');
                }
                $this->keys[] = ++$last;

                return $this;
            },
        ]);
    }

    public function addAll(CollectionInterface $values)
    {
        $last = Arr::last(iterator_to_array($this->keys));
        foreach ($values as $key => $value) {
            if (
                (is_numeric($last) && !is_numeric($key)) ||
                (!is_numeric($last) && is_numeric($key))
            ) {
                throw new \InvalidArgumentException('For performance reason collection index must be either numeric or alphanumeric, not both');
            }
            $this->items[] = $value;
            if (is_numeric($key) && is_numeric($last)) {
                $key = ++$last;
            }
            $this->keys[] = $key;
        }

        return $this;
    }

    public function get(...$args)
    {
        return $this->overload($args, [
            function (\Closure $predicate, $default = null) {
                return ($value = \call_user_func($predicate, iterator_to_array($this->items))) ? $value : $default;
            },
            function (int $key) {
                return $this->items[$key] ?? null;
            },
            function (string $key) {
                return $this->offsetGet($key);
            },
            function () {
                return $this->all();
            },
        ]);
    }

    public function remove(...$args): bool
    {
        if (!$this->contains(...$args)) {
            return false;
        }

        return $this->overload($args, [
            function (\Closure $predicate) {
                // TODO : Handle refolveFn as a predicate
                return \call_user_func($predicate, iterator_to_array($this->items));
            },
            function (int $key) {
                $this->items->offsetUnset($key);
                $this->keys->offsetUnset($key);

                return true;
            },
            function (string $value) {
                $this->offsetUnset($value);

                return true;
            },
        ]);
    }

    public function clear(): void
    {
        $this->keys = new \ArrayIterator([]);
        $this->items = new \ArrayIterator([]);
    }

    public function size(): int
    {
        return \count($this->items);
    }

    /**
     * Determine if an item exists in the collection.
     */
    public function contains(...$args): bool
    {
        if (1 === \count($args_ = \func_get_args())) {
            if (!\is_string($args_[0]) && \is_callable($args_[0])) {
                $default = new \stdClass();

                return $this->first($args_[0], $default) !== $default;
            }

            return null !== $this->get(...$args);
        }

        return $this->contains(CompareValueFactory::new(...\func_get_args()));
    }

    public function isEmpty(): bool
    {
        return 0 === $this->size();
    }

    public function all()
    {
        return array_combine(iterator_to_array($this->keys), iterator_to_array($this->items));
    }

    public function count()
    {
        return $this->items->count();
    }

    public function toArray(): array
    {
        return iterator_to_array((static function ($internal) {
            foreach ($internal as $key => $value) {
                yield $key => method_exists($value, 'toArray') ? $value->toArray() : $value;
            }
        })($this->all()));
    }

    /**
     * @throws RuntimeException
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function each(callable $callback)
    {
        $iterator = new \MultipleIterator();
        $iterator->attachIterator($this->items);
        $iterator->attachIterator($this->keys);
        foreach ($iterator as $value) {
            $callback(...$value);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return self
     */
    public function map(callable $callback, $preserveKey = true)
    {
        if (!($callback instanceof \Closure) || !\is_callable($callback)) {
            throw new \InvalidArgumentException('Expect parameter 1 to be an instance of \Closure, or php callable, got : '.\gettype($callback));
        }

        return new static(
            $preserveKey ? array_combine(iterator_to_array($this->keys), iterator_to_array(Iter::map($this->items, $callback, $preserveKey))) :
                iterator_to_array(Iter::map($this->items, $callback, $preserveKey))
        );
    }

    /**
     * {@inheritDoc}
     *
     * @return self
     */
    public function filter(callable $predicate, $preserveKey = true)
    {
        $iterator = new \MultipleIterator();
        $iterator->attachIterator($this->items);
        $iterator->attachIterator($this->keys);
        $keys = [];
        $values = [];
        iterator_apply(
            $iterator,
            static function (\Iterator $it) use ($predicate, &$values, &$keys, $preserveKey) {
                [$current, $key] = $it->current();
                $result = $predicate($current, $key);
                if (!$result) {
                    return true;
                }
                if ($preserveKey) {
                    $keys[] = $key;
                }
                $values[] = $current;

                return true;
            },
            [$iterator]
        );

        return new static(array_combine($preserveKey ? $keys : array_keys($values), $values));
    }

    public function reduce(callable $callback, $init = null)
    {
        return Iter::reduce($this->items, $callback, $init);
    }

    public function first($value = null, $default = null)
    {
        if (null === $value) {
            $this->items->rewind();
            if (!$this->items->valid()) {
                return $default instanceof \Closure ? $default() : $default;
            }

            return $this->items->current();
        }
        $callback = !\is_string($value) && \is_callable($value) ? $value : (static function ($item) use ($value) {
            return $item === $value;
        });
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return Functional::isCallable($default) ? $default() : $default;
    }

    public function last($default = null)
    {
        $this->items->rewind();
        if (!$this->items->valid()) {
            return Functional::isCallable($default) ? $default() : $default;
        }
        $count = $this->items->count();
        // Seek the last item in the iterator
        $this->items->seek($count - 1);
        $last = $this->items->current();
        // Reset the iterator pointer
        $this->items->rewind();

        return $last;
    }

    public function combine($keys)
    {
        $keys = Arr::create($keys);
        if (\count($keys) !== $this->items->count()) {
            throw new \InvalidArgumentException('The size of keys must equals the size of elements in the collection');
        }
        $this->keys = new \ArrayIterator($keys);
    }

    // #region Adding missing Illuminate collection methods

    /**
     * Count the number of items in the collection by a field or using a callback.
     *
     * @param callable|string $countBy
     *
     * @return static
     */
    public function countBy($countBy = null)
    {
        return new static($this->lazy()->countBy($countBy));
    }

    /**
     * Pad collection to the specified length with a value.
     *
     * @param int   $size
     * @param mixed $value
     *
     * @return static
     */
    public function pad($size, $value)
    {
        return new self(
            array_pad(iterator_to_array($this->items), $size, $value)
        );
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * @param mixed ...$items
     *
     * @return static
     */
    public function zip(...$items)
    {
        // Creates an iterator zip function
        return new self(Arr::zip(iterator_to_array($this->items), ...$items));
    }

    /**
     * Return only unique items from the collection array.
     *
     * @param string|callable|null $key
     * @param bool                 $strict
     *
     * @return static
     */
    public function unique($key = null, $strict = false)
    {
        $callback = ValueResolver::new($key);
        $exists = [];

        return $this->reject(
            static function ($item, $key) use ($callback, $strict, &$exists) {
                if (\in_array($id = $callback($item, $key), $exists, $strict)) {
                    return true;
                }
                $exists[] = $id;
            }
        );
    }

    /**
     * Transform each item in the collection using a callback.
     *
     * @return self
     */
    public function transform(callable $callback)
    {
        return $this->map($callback);
    }

    /**
     * Take the first or last {$limit} items.
     *
     * @return static
     */
    public function take(int $limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * Take items in the collection until the given condition is met.
     *
     * @param mixed $value
     *
     * @return static
     */
    public function takeUntil($value)
    {
        return new static($this->lazy()->takeUntil($value));
    }

    /**
     * Splice a portion of the underlying collection array.
     *
     * @param int      $offset
     * @param int|null $length
     * @param mixed    $replacement
     *
     * @return static
     */
    public function splice($offset, $length = null, $replacement = [])
    {
        $items = $this->all();
        if (1 === \func_num_args()) {
            return new static(array_splice($items, $offset));
        }

        return new static(array_splice($items, $offset, $length, $replacement));
    }

    /**
     * Get the first item in the collection but throw an exception if no matching items exist.
     *
     * @param mixed $key
     * @param mixed $operator
     * @param mixed $value
     *
     * @throws ValueNotFoundException
     *
     * @return mixed
     */
    public function firstOrFail($key = null, $operator = null, $value = null)
    {
        $filter = \func_num_args() > 1 ? CompareValueFactory::new(...\func_get_args()) : $key;
        $default = new DefaultValue();
        $item = $this->first($filter, $default);
        if ($item === $default) {
            throw new ValueNotFoundException($key);
        }

        return $item;
    }

    /**
     * Chunk the collection into chunks of the given size.
     *
     * @return static
     */
    public function chunk(int $size)
    {
        if ($size <= 0) {
            return new static();
        }
        $chunks = [];
        foreach (array_chunk($this->all(), $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * Chunk the collection into chunks with a callback.
     *
     * @return static
     */
    public function chunkWhile(callable $callback)
    {
        return new static($this->lazy()->chunkWhile($callback)->mapInto(static::class));
    }

    /**
     * Take items in the collection while the given condition is met.
     *
     * @param mixed $value
     *
     * @return static
     */
    public function takeWhile($value, bool $flexible = true)
    {
        return new static($this->lazy()->takeWhile($value));
    }

    /**
     * Split a collection into a certain number of groups, and fill the first groups completely.
     *
     * @return static
     */
    public function splitIn(int $total)
    {
        return $this->chunk((int) ceil($this->count() / $total));
    }

    /**
     * Skip the first {$offset} items.
     *
     * @return static
     */
    public function skip(int $offset)
    {
        return $this->slice($offset);
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
        return new static($this->lazy()->skipUntil($value));
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
        return new static($this->lazy()->skipWhile($value));
    }

    /**
     * Slice the underlying collection array.
     *
     * @return static
     */
    public function slice(int $offset, int $length = null, $preserveKeys = true)
    {
        $slice = \array_slice($this->all(), $offset, $length, true);

        return new static($preserveKeys ? $slice : array_values($slice));
    }

    /**
     * Split a collection into a certain number of groups.
     *
     * @return static
     */
    public function split(int $total)
    {
        if ($this->isEmpty()) {
            return new static();
        }
        // #region Initialize variables
        $items = $this->all();
        $count = $this->count();
        $groups = new static();
        $gsize = (int) floor($count / $total);
        $remain = $count % $total;
        $start = 0;
        // #endregion Initialize variables
        for ($i = 0; $i < $total; ++$i) {
            $size = $gsize;
            if ($i < $remain) {
                ++$size;
            }
            if ($size) {
                $groups->push(new static(\array_slice($items, $start, $size)));
                $start += $size;
            }
        }

        return $groups;
    }

    /**
     * Shuffle the items in the collection.
     *
     * @return static
     */
    public function shuffle(int $seed = null)
    {
        return new static(Arr::shuffle($this->all(), $seed));
    }

    /**
     * Replace the collection items with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function replace($items)
    {
        return new static(array_replace($this->all(), Arr::create($items)));
    }

    /**
     * Recursively replace the collection items with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function replaceRecursive($items)
    {
        return new static(array_replace_recursive($this->all(), Arr::create($items)));
    }

    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->all(), true));
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param mixed $value
     * @param bool  $strict
     *
     * @return mixed
     */
    public function search($value, $strict = false)
    {
        if (!(!\is_string($value) && \is_callable($value))) {
            return array_search($value, $this->all(), $strict);
        }
        foreach ($this->all() as $key => $item) {
            if ($value($item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Get and remove the first N items from the collection.
     *
     * @param int $count
     *
     * @return mixed
     */
    public function shift($count = 1)
    {
        $items = $this->all();
        if (1 === $count) {
            $result = array_shift($items);
            $this->setProperties($items);

            return $result;
        }
        if ($this->isEmpty()) {
            return new static();
        }
        $count_ = $this->count();
        $results = [];
        foreach (range(1, min($count, $count_)) as $_) {
            $results[] = array_shift($items);
        }

        return new static($results);
    }

    /**
     * Push one or more items onto the end of the collection.
     *
     * @param mixed $values
     *
     * @return $this
     */
    public function push(...$values)
    {
        $last_key = Arr::last(iterator_to_array($this->keys));
        foreach ($values as $key => $value) {
            if (is_numeric($key) && is_numeric($last_key)) {
                $last_key = $last_key + 1;
                $this->keys[] = $last_key;
            } else {
                $last_key = $key;
                $this->keys[] = $last_key;
            }
            $this->items[] = $value;
        }

        return $this;
    }

    /**
     * Push all of the given items onto the collection.
     *
     * @param iterable $source
     *
     * @return static
     */
    public function concat($source)
    {
        $keys = iterator_to_array($this->keys);
        $values = iterator_to_array($this->items);
        $last_key = Arr::last($keys);
        foreach ($source as $key => $value) {
            if (is_numeric($key) && is_numeric($last_key)) {
                $last_key = $last_key + 1;
                $keys[] = $last_key;
            } else {
                $last_key = $key;
                $keys[] = $last_key;
            }
            $values[] = $value;
        }

        return new static(array_combine($keys, $values));
    }

    /**
     * Get and remove an item from the collection.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $item = $this->offsetGet($key) ?? ($default instanceof \Closure ? $default() : $default);
        $this->setProperties(Arr::except($this->all(), [$key]));

        return $item;
    }

    /**
     * Put an item in the collection by key.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this
     */
    public function put($key, $value)
    {
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * Merge the collection with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->all(), Arr::create($items)));
    }

    /**
     * Recursively merge the collection with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function mergeRecursive($items)
    {
        return new static(array_merge_recursive($this->all(), Arr::create($items)));
    }

    /**
     * Union the collection with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function union($items)
    {
        return new static($this->all() + Arr::create($items));
    }

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param int $step
     * @param int $offset
     *
     * @return static
     */
    public function nth($step, $offset = 0, bool $preserve_keys = false)
    {
        $generator_func = static function (array $list) use ($step, $offset, $preserve_keys) {
            $position = 0;
            if ($preserve_keys) {
                foreach ($list as $key => $value) {
                    if ($position % $step === $offset) {
                        yield $key => $value;
                    }
                    ++$position;
                }
            } else {
                foreach ($list as $value) {
                    if ($position % $step === $offset) {
                        yield $value;
                    }
                    ++$position;
                }
            }
        };

        return new static(iterator_to_array($generator_func($this->all())));
    }

    /**
     * Get the items with the specified keys.
     *
     * @param mixed $keys
     *
     * @return static
     */
    public function only($keys)
    {
        if (null === $keys) {
            return new static($this);
        }
        if (method_exists($keys, 'all')) {
            $keys = $keys->all();
        }
        $keys = \is_array($keys) ? $keys : \func_get_args();

        return new static(Arr::only($this->all(), $keys));
    }

    /**
     * Get and remove the last N items from the collection.
     *
     * @param int $count
     *
     * @return mixed
     */
    public function pop($count = 1)
    {
        $values = $this->all();
        if (1 === $count) {
            $item = array_pop($values);
            $this->setProperties($values);

            return $item;
        }
        if ($this->isEmpty()) {
            return new static();
        }
        $count_ = $this->count();
        $results = [];
        foreach (range(1, min($count, $count_)) as $_) {
            $results[] = array_pop($values);
        }

        return new static($results);
    }

    /**
     * Push an item onto the beginning of the collection.
     *
     * @param mixed $value
     * @param mixed $key
     *
     * @return $this
     */
    public function prepend($value, $key = null)
    {
        $array_prepend = static function ($array, $value, $key = null) {
            if (2 === \func_num_args()) {
                array_unshift($array, $value);
            } else {
                $array = [$key => $value] + $array;
            }

            return $array;
        };
        $this->setProperties($array_prepend($this->all(), ...\func_get_args()));

        return $this;
    }

    /**
     * Join all items from the collection using a string. The final items can use a separate glue string.
     *
     * @param string $glue
     * @param string $before_last
     *
     * @return string
     */
    public function join($glue, $before_last = '')
    {
        if ('' === $before_last) {
            return $this->implode($glue);
        }
        $count = $this->count();
        if (0 === $count) {
            return '';
        }
        if (1 === $count) {
            return $this->last();
        }
        $collection = new static($this);
        $end = $collection->pop();

        return $collection->implode($glue).$before_last.$end;
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function has($key)
    {
        $keys = \is_array($key) ? $key : \func_get_args();
        foreach ($keys as $value) {
            if (!\array_key_exists($value, $this->all())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the values of a given key.
     *
     * @param string|array|int|null $value
     * @param string|null           $key
     *
     * @return static
     */
    public function pluck($value, $key = null)
    {
        $pluck_generator = function () use ($value, $key) {
            $explode_pluck_params = static function ($value, $key) {
                $value = \is_string($value) ? explode('.', $value) : $value;
                $key = null === $key || \is_array($key) ? $key : explode('.', $key);

                return [$value, $key];
            };
            [$value, $key] = $explode_pluck_params($value, $key);
            foreach ($this->all() as $item) {
                $value_ = drewlabs_core_get($item, $value);
                if (null === $key) {
                    yield $value_;
                } else {
                    $itemKey = drewlabs_core_get($item, $key);
                    if (\is_object($itemKey) && method_exists($itemKey, '__toString')) {
                        $itemKey = (string) $itemKey;
                    }
                    yield $itemKey => $value_;
                }
            }
        };

        return new static(iterator_to_array($pluck_generator()));
    }

    /**
     * Concatenate values of a given key as a string.
     *
     * @param string      $value
     * @param string|null $glue
     *
     * @return string
     */
    public function implode($value, $glue = null)
    {
        $first = $this->first();
        if (\is_array($first) || (\is_object($first) && !method_exists($first, '__toString()'))) {
            return implode($glue ?? '', $this->pluck($value)->all());
        }

        return implode($value ?? '', $this->all());
    }

    /**
     * Intersect the collection with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function intersect($items)
    {
        return new static(array_intersect($this->all(), Arr::create($items)));
    }

    /**
     * Intersect the collection with the given items by key.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function intersectByKeys($items)
    {
        return new static(array_intersect_key(
            $this->all(),
            Arr::create($items)
        ));
    }

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param int $depth
     *
     * @return static
     */
    public function flatten($depth = \INF)
    {
        $flatten_func = static function ($array, $depth) use (&$flatten_func) {
            $result = [];
            foreach ($array as $item) {
                $item = (\is_object($item) || \is_string($item)) && method_exists($item, 'all') ? $item->all() : $item;
                if (!\is_array($item)) {
                    $result[] = $item;
                } else {
                    $values = 1 === $depth
                        ? array_values($item)
                        : $flatten_func($item, $depth - 1);
                    foreach ($values as $value) {
                        $result[] = $value;
                    }
                }
            }

            return $result;
        };

        return new static($flatten_func($this->all(), $depth));
    }

    /**
     * Flip the items in the collection.
     *
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->all()));
    }

    /**
     * Remove an item from the collection by key.
     *
     * @param string|array $keys
     *
     * @return $this
     */
    public function forget(...$keys)
    {
        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param array|callable|string $groupBy
     * @param bool                  $preserveKeys
     *
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        if (!(!\is_string($groupBy) && \is_callable($groupBy)) && \is_array($groupBy)) {
            $nextGroups = $groupBy;
            $groupBy = array_shift($nextGroups);
        }

        $groupBy = ValueResolver::new($groupBy);
        $results = [];
        foreach ($this->all() as $key => $value) {
            $groupKeys = !\is_array($group = $groupBy($value, $key)) ? [$group] : $group;
            foreach ($groupKeys as $groupKey) {
                $groupKey = \is_bool($groupKey) ? (int) $groupKey : $groupKey;
                if (!\array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static();
                }
                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }
        $result = new static($results);
        if (!empty($nextGroups)) {
            /**
             * @var \Closure
             */
            $map = $result->map;

            return $map('groupBy', [$nextGroups, $preserveKeys]);
        }

        return $result;
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param callable|string $keyBy
     *
     * @return static
     */
    public function keyBy($keyBy)
    {
        $keyBy = ValueResolver::new($keyBy);

        return new static(iterator_to_array(
            (function () use ($keyBy) {
                foreach ($this->all() as $key => $item) {
                    $resolvedKey = $keyBy($item, $key);
                    if (\is_object($resolvedKey)) {
                        $resolvedKey = (string) $resolvedKey;
                    }
                    yield $resolvedKey => $item;
                }
            })()
        ));
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * @param mixed $keys
     *
     * @return static
     */
    public function except($keys)
    {
        if ((\is_object($keys) || \is_string($keys)) && method_exists($keys, 'all')) {
            $keys = $keys->all();
        } elseif (!\is_array($keys)) {
            $keys = \func_get_args();
        }

        return new static(Arr::except($this->all(), $keys));
    }

    /**
     * Get the median of a given key.
     *
     * @param string|array|null $key
     *
     * @return mixed
     */
    public function median($key = null)
    {
        $values = (isset($key) ? $this->pluck($key) : $this)
            ->filter(static function ($item) {
                return null !== $item;
            })->sort()->values();

        $count = $values->count();
        if (0 === $count) {
            return;
        }
        $middle = (int) ($count / 2);
        if ($count % 2) {
            return $values->get($middle);
        }

        return (new static([
            $values->get($middle - 1), $values->get($middle),
        ]))->average();
    }

    /**
     * Get the mode of a given key.
     *
     * @param string|array|null $key
     *
     * @return array|null
     */
    public function mode($key = null)
    {
        if (0 === $this->count()) {
            return;
        }
        $collection = isset($key) ? $this->pluck($key) : $this;
        $counts = new static();
        $collection->each(static function ($value) use ($counts) {
            $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1;
        });
        $sorted = $counts->sort();
        $highestValue = $sorted->last();

        return $sorted->filter(static function ($value) use ($highestValue) {
            return $value === $highestValue;
        })->sort()->keys()->all();
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return static
     */
    public function collapse()
    {
        return new static(Iter::collapse($this->all()));
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diff($items)
    {
        return new static(array_diff($this->all(), Arr::create($items)));
    }

    /**
     * Get the items in the collection that are not present in the given items, using the callback.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diffUsing($items, callable $callback)
    {
        return new static(array_udiff($this->all(), Arr::create($items), $callback));
    }

    /**
     * Get the items in the collection whose keys and values are not present in the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diffAssoc($items)
    {
        return new static(array_diff_assoc($this->all(), Arr::create($items)));
    }

    /**
     * Get the items in the collection whose keys and values are not present in the given items, using the callback.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diffAssocUsing($items, callable $callback)
    {
        return new static(array_diff_uassoc($this->all(), Arr::create($items), null, $callback));
    }

    /**
     * Get the items in the collection whose keys are not present in the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diffKeys($items)
    {
        return new static(array_diff_key($this->all(), Arr::create($items)));
    }

    /**
     * Get the items in the collection whose keys are not present in the given items, using the callback.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diffKeysUsing($items, callable $callback)
    {
        return new static(array_diff_ukey($this->all(), Arr::create($items), null, $callback));
    }

    // #endregion Adding missing Illuminate collection methods

    public function getIterator(): \Traversable
    {
        // Provide a smart iterator implementation
        return new \ArrayIterator($this->all());
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param mixed $key
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($key): bool
    {
        return $this->contains($key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        if (\is_string($key)) {
            $key = Arr::search($key, iterator_to_array($this->keys));
        }
        if (false === $key) {
            return null;
        }

        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $key
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value): void
    {
        $key ? $this->add($key, $value) : $this->add($value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param string $key
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($key): void
    {
        if (!is_numeric($key) || !\is_string($key)) {
            $key = Arr::search($key, iterator_to_array($this->keys));
        }
        if (false !== $key) {
            $this->keys->offsetUnset($key);
            $this->items->offsetUnset($key);
        }
    }

    public function values()
    {
        // Makes the values return a static
        return new self(iterator_to_array($this->items));
    }

    public function keys()
    {
        // TODO : Makes the keys() method return
        return new self(iterator_to_array($this->keys));
    }

    private function setProperties(array $items = [])
    {
        $this->items = new \ArrayIterator(array_values($items));
        $this->keys = new \ArrayIterator(array_keys($items));
    }

    /**
     * Creates a lazy collection instance.
     *
     * @param mixed|null $iterable
     *
     * @return LazyCollection
     */
    private function lazy($iterable = null)
    {
        return new LazyCollection($iterable ?? $this->all());
    }
}
