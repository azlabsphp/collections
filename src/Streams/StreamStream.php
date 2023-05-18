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

use Drewlabs\Collections\Collectors\ReduceCollector;
use Drewlabs\Collections\Contracts\Arrayable;
use Drewlabs\Collections\Contracts\StreamInterface;

class StreamStream implements StreamInterface, Arrayable
{
    use BaseStream;

    /**
     * Creates new class instance.
     */
    public function __construct(\Traversable $source)
    {
        $this->source = $source;
    }

    public function map(callable $callback, $preserve = false)
    {
        $this->pipe[] = static function (StreamInput $input) use ($callback) {
            $stream = $input->value->map($callback);

            return Operator::create()(StreamInput::wrap($stream));
        };

        return $this;
    }

    public function filter(callable $predicate, $preserveKey = false)
    {
        $this->pipe[] = static function (StreamInput $input) use ($predicate) {
            return Operator::create()(StreamInput::wrap($input->value->filter($predicate)));
        };

        return $this;
    }

    public function reduce(callable $reducer, $identity = null)
    {
        /**
         * @var mixed    $identity
         * @var callable $reducer
         */
        [$identity, $reducer] = 1 === \func_num_args() ? [0, $reducer] : [$identity, $reducer];

        return $this->collect(new ReduceCollector(static function ($carry, $current) use ($reducer) {
            $carry = $current instanceof StreamInterface ? $current->reduce($reducer, $carry) : $reducer($carry, $current);

            return $carry;
        }, $identity));
    }

    public function toArray(): array
    {
        $fn = static function ($source) {
            foreach ($source as $value) {
                yield $value->toArray();
            }
        };
        $output = $this->collect(static function ($source) use (&$fn) {
            return $fn($source);
        });

        return \is_array($output) ? $output : iterator_to_array($output);
    }
}
