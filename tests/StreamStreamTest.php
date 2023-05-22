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

use Drewlabs\Collections\Collectors\StreamCollector;
use Drewlabs\Collections\Contracts\Arrayable;
use Drewlabs\Collections\Streams\Stream;
use Drewlabs\Collections\Streams\StreamStream;
use PHPUnit\Framework\TestCase;

class StreamStreamTest extends TestCase
{
    public function test_chunk_stream_collector()
    {
        $stream = Stream::range(1, 10);
        $this->assertInstanceOf(
            StreamStream::class,
            $stream->collect(new StreamCollector(90)),
        );
    }

    public function test_chunk_stream_map()
    {
        /**
         * @var StreamStream
         */
        $stream = Stream::range(1, 10)->collect(new StreamCollector(2));
        $stream = $stream->map(
            static function ($current) {
                return $current * 2;
            }
        );
        $array = $stream->toArray();
        $this->assertSame(
            $array[0],
            [2, 4]
        );
    }

    public function test_chunk_stream_filter()
    {
        /**
         * @var StreamStream
         */
        $stream = Stream::range(1, 10)->collect(new StreamCollector(3));
        $stream = $stream->filter(
            static function ($current) {
                return 0 === $current % 2;
            }
        );
        $array = $stream->toArray();
        $this->assertSame(
            $array,
            [[2], [4, 6], [8], [10]]
        );
    }

    public function test_chunk_stream_reduce()
    {
        /**
         * @var StreamStream
         */
        $stream = Stream::range(1, 10)->collect(new StreamCollector(3));
        $result = $stream->filter(
            static function ($current) {
                return 0 === $current % 2;
            }
        )->reduce(static function ($carry, $current) {
            $carry += $current;

            return $carry;
        }, 0);
        $this->assertSame(
            array_reduce(
                array_filter(range(1, 10), static function ($current) {
                    return 0 === $current % 2;
                }),
                static function ($carry, $current) {
                    $carry += $current;

                    return $carry;
                },
                0
            ),
            $result
        );
    }

    public function test_chunk_stream_take()
    {
        /**
         * @var StreamStream
         */
        $stream = Stream::range(1, 10)->collect(new StreamCollector(3));
        $result = $stream->filter(static function ($current) {
            return 0 === $current % 2;
        })->take(3)
            ->reduce(static function ($carry, $current) {
                $carry += $current;

                return $carry;
            }, 0);
        $this->assertSame(20, $result);
    }

    public function test_chunk_stream_first()
    {
        /**
         * @var StreamStream
         */
        $stream = Stream::range(1, 10)->collect(new StreamCollector(3));
        /**
         * @var Arrayable
         */
        $result = $stream->filter(static function ($current) {
            return 0 === $current % 2;
        })->first();
        $this->assertInstanceOf(Arrayable::class, $result);
        $this->assertSame([2], $result->toArray());
    }
}
