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

namespace Drewlabs\Collections\Collectors;

use Drewlabs\Collections\Contracts\CollectorInterface;
use Drewlabs\Collections\ForwardList;
use Drewlabs\Collections\Streams\StreamStream;
use Traversable;

class StreamCollector implements CollectorInterface
{
    public const SIZE_LIMIT = 512;

    /**
     * @var int
     */
    private $size;

    /**
     * Create a stream collector class instance.
     *
     * @param int $size
     *
     * @return self
     */
    public function __construct(?int $size = 512)
    {
        $this->size = $size ? (int) $size : static::SIZE_LIMIT;
    }

    public function __invoke(\Traversable $source)
    {
        if ($this->size > static::SIZE_LIMIT) {
            throw new \LogicException('For performance reason, chunk size has been limit to '.$this->size);
        }

        return new StreamStream($this->createStream($source));
    }

    /**
     * Creates a traversable of `stream` instances.
     *
     * @return \Traversable<mixed>|mixed[]
     */
    private function createStream(\Traversable $source)
    {
        $list = new ForwardList();
        $tracker = new ForwardList();
        $index = 0;
        foreach ($source as $current) {
            if ($index === $this->size) {
                $list->push($tracker->stream());
                $index = 0;
                $tracker = new ForwardList();
            }
            $tracker->push($current);
            ++$index;
        }
        if (!$tracker->isEmpty()) {
            $list->push($tracker->stream());
            $tracker = new ForwardList();
        }

        return $list->getIterator();
    }
}
