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

use Drewlabs\Collections\Contracts\StreamInterface;
use Drewlabs\Collections\Exceptions\ValueNotFoundException;
use Drewlabs\Collections\Utils\CompareValueFactory;
use Drewlabs\Collections\Utils\DefaultValue;
use Drewlabs\Core\Helpers\Functional;
use Drewlabs\Core\Helpers\ImmutableDateTime;

/**
 * @method StreamInterface map(\callable $project)
 */
trait BaseStream
{
    /**
     * Transformation pipe in which each stream value is passed through.
     *
     * @var array<Closure<>>
     */
    private $pipe = [];

    /**
     * The stream data source instance.
     *
     * @var \Iterator<int,Stream>
     */
    private $source;

    /**
     * Control the state of the stream. If it value is true, the stream is unsafe
     * meaning stream never ends.
     *
     * @var bool
     */
    private $infinite;

    public function take(int $size)
    {
        $this->infinite = false;
        $fn = static function ($source) use ($size) {
            $index = 0;
            foreach ($source as $current) {
                ++$index;
                if ($index > $size) {
                    break;
                }
                yield $current;
            }
        };
        $this->source = $fn($this->source);

        return $this;
    }

    public function takeUntil($value)
    {
        $this->infinite = false;
        $value = $this->isCallable($value) ? $value : static function ($current) use ($value) {
            return $current === $value;
        };
        $fn = static function ($source) use ($value) {
            while ($source->valid()) {
                $current = $source->current();
                if ($value($current, $source->key())) {
                    break;
                }
                yield $current;
                $source->next();
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

    public function takeWhile($value, $flexible = true)
    {
        $value = $this->isCallable($value) ? $value : static function ($data) use ($value) {
            return $data === $value;
        };
        $fn = static function ($source) use ($value, $flexible) {
            while ($source->valid()) {
                $current = $source->current();
                if ($result = $value($current, $source->key())) {
                    yield $current;
                }
                if (!(bool) $flexible && !(bool) $result) {
                    break;
                }
                $source->next();
            }
        };
        $this->source = $fn($this->source);

        return $this;
    }

    /**
     * Take items in the collection until a given point in time.
     *
     * @return StreamInterface
     */
    public function takeUntilTimeout(\DateTimeInterface $timeout)
    {
        return $this->takeWhile(static function () use ($timeout) {
            return ImmutableDateTime::isfuture($timeout);
        });
    }

    public function each(callable $callback)
    {
        $this->_throwIfUnsafe();
        $this->pipe[] = Operator::create($callback);
        $composedFunc = Functional::compose(...$this->pipe);
        foreach ($this->source as $value) {
            $composedFunc(StreamInput::wrap($value));
        }
    }

    public function getIterator(): \Traversable
    {
        return $this->source;
    }

    public function firstOr($default = null)
    {
        $composedFunc = Functional::compose(...$this->pipe);
        foreach ($this->source as $value) {
            $result = $composedFunc(StreamInput::wrap($value));
            if ($result->accepts()) {
                return $result->value;
            }
        }

        return Functional::isCallable($default) ? \call_user_func($default) : $default;
    }

    public function collect(callable $collector)
    {
        $this->_throwIfUnsafe();
        $compose = Functional::compose(...$this->pipe);
        $fn = static function (\Iterator $source) use (&$compose) {
            foreach ($source as $value) {
                $result = $compose(StreamInput::wrap($value));
                if (!$result->accepts()) {
                    continue;
                }
                yield $result->value;
            }
        };

        return \call_user_func($collector, $fn($this->source));
    }

    /**
     * Produce a collection with each value mapped to a given class instance.
     *
     * @return mixed
     */
    public function mapInto(string $blueprint)
    {
        return $this->map(static function ($current) use ($blueprint) {
            return new $blueprint($current);
        });
    }

    public function first($value = null, $default = null)
    {
        if (null === $value) {
            return $this->firstOr($default);
        }
        $predicate = !\is_string($value) && \is_callable($value) ? $value : (static function ($item) use ($value) {
            return $item === $value;
        });
        $composedFunc = Functional::compose(...$this->pipe);
        foreach ($this->source as $value) {
            $result = $composedFunc(StreamInput::wrap($value));
            if ($result->accepts() && $predicate($result->value)) {
                return $result->value;
            }
        }

        return Functional::isCallable($default) ? \call_user_func($default) : $default;
    }

    public function firstOrFail($key = null, $operator = null, $value = null)
    {
        $filter = \func_num_args() > 1 ? CompareValueFactory::new(...\func_get_args()) : $key;
        if (($item = $this->first($filter, $default = new DefaultValue())) === $default) {
            throw new ValueNotFoundException($key);
        }

        return $item;
    }

    public function last($default = null)
    {
        $defaultValue = new DefaultValue();
        $last = $defaultValue;
        $composedFunc = Functional::compose(...$this->pipe);
        foreach ($this->source as $value) {
            $result = $composedFunc(StreamInput::wrap($value));
            if ($result->accepts()) {
                $last = $result->value;
            }
        }

        return $last === $defaultValue ? (Functional::isCallable($default) ? $default() : $default) : $last;
    }

    /**
     * Checks if the provided parameter is a callable instance but not a string.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function isCallable($value)
    {
        return !\is_string($value) && \is_callable($value);
    }

    /**
     * Throw error if the stream source is an unsafe stream source.
     *
     * @throws Exception
     *
     * @return void
     */
    private function _throwIfUnsafe()
    {
        if ($this->infinite) {
            throw new \Exception(
                'Stream source is unsafe, stream is infinite call take(n) to process finite number of source items'
            );
        }
    }
}
