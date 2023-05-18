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

namespace Drewlabs\Support\Tests\Unit;

use Drewlabs\Collections\Collectors\ArrayCollector;
use Drewlabs\Collections\Collectors\StreamCollector;
use Drewlabs\Collections\Streams\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function test_iterate()
    {
        $stream = Stream::iterate(1, static function ($previous) {
            return $previous + 1;
        });
        $this->assertSame(range(1, 10), $stream->take(10)->collect(new ArrayCollector()));
    }

    public function test_map()
    {
        $stream = Stream::iterate(1, static function ($previous) {
            return $previous + 1;
        });

        $this->assertSame(array_map(static function ($value) {
            return $value * 2;
        }, range(1, 10)), $stream->take(10)->map(static function ($value) {
            return $value * 2;
        })->collect(new ArrayCollector()));
    }

    public function test_filter()
    {
        $stream = Stream::iterate(1, static function ($previous) {
            return $previous + 1;
        });
        $this->assertSame(
            array_values(
                array_filter(
                    range(1, 10),
                    static fn ($state) => 0 === $state % 2
                )
            ),
            $stream->take(10)
                ->filter(static fn ($state) => 0 === $state % 2)
                ->collect(new ArrayCollector())
        );
    }

    public function test_stream_reduce()
    {
        $stream = Stream::iterate(1, static function ($previous) {
            return $previous + 1;
        });
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
            $stream->take(10)
                ->filter(static function ($current) {
                    return 0 === $current % 2;
                })
                ->reduce(static function ($carry, $current) {
                    $carry += $current;

                    return $carry;
                })
        );
    }

    public function test_first()
    {
        $stream = Stream::iterate(1, static function ($previous) {
            return $previous + 1;
        });
        $first = $stream->filter(static fn ($x) => 0 === $x % 2)
            ->map(static fn ($x) => $x * 2)
            ->take(10)
            ->first();
        $this->assertSame(4, $first);
    }

    public function test_each()
    {
        $stream = Stream::iterate(1, static function ($previous) {
            return $previous + 1;
        });
        $stream->filter(static fn ($x) => 0 === $x % 2)
            ->map(static fn ($x) => $x * 2)
            ->take(10)
            ->each(static function ($value) {
                // print_r($value);
            });
        $this->assertTrue(true);
    }

    public function test_takeWhile()
    {
        $stream = Stream::iterate(1, static function ($previous) {
            return $previous + 1;
        });

        $result = $stream->takeWhile(static fn ($x) => $x > 10)
            ->take(20)
            ->filter(static fn ($x) => 0 === $x % 5)
            ->map(static fn ($x) => $x * 2)
            ->first();
        $this->assertSame(30, $result);
    }

    public function test_takeUntil()
    {
        $stream = Stream::iterate(1, static function ($previous) {
            return $previous + 1;
        });
        $result = $stream->takeUntil(static fn ($x) => $x > 20)
            ->filter(static fn ($x) => 0 === $x % 5)
            ->map(static fn ($x) => $x * 2)
            ->first();
        $this->assertSame(10, $result);
    }

    public function test_range()
    {
        $stream = Stream::range(1, 100);
        $this->assertSame($stream->collect(new ArrayCollector()), range(1, 100));
    }

    public function test_chunk_stream_collector()
    {
        $stream = Stream::range(1, 100);
        $this->assertSame(
            $stream
                ->collect(new StreamCollector(90))
                ->toArray(),
            [range(1, 90), range(91, 100)]
        );
    }
}
