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

use Drewlabs\Collections\Streams\Stream;

class ForwardList implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var Node
     */
    private $root;

    /**
     * @var Node
     */
    private $tail;

    /**
     * Size of the collection.
     *
     * @var int
     */
    private $size = 0;

    /**
     * @param \Traversable|array $source
     *
     * @return self
     */
    public function __construct($source = [])
    {
        foreach ($source as $value) {
            $this->push($value);
        }
    }

    public function __toString()
    {
        $out = '[ ';
        foreach ($this->getIterator() as $value) {
            $out .= sprintf('%d ', $value);
        }
        $out .= "]\n";

        return $out;
    }

    public function push($value)
    {
        $node = new Node($value);
        if ($this->isEmpty()) {
            $this->root = &$node;
            $this->tail = &$node;
        } else {
            $node->previous = &$this->tail;
            $this->tail->next = &$node;
            $this->tail = &$node;
        }
        ++$this->size;
    }

    public function isEmpty()
    {
        return (null === $this->root) && (null === $this->tail);
    }

    public function size()
    {
        return $this->size;
    }

    public function pop()
    {
        $value = $this->tail->value;
        $this->tail = &$this->tail->previous;
        $this->tail->next = null;
        --$this->size;

        return $value;
    }

    public function first()
    {
        return $this->root ? $this->root->value : null;
    }

    public function last()
    {
        return $this->tail ? $this->tail->value : null;
    }

    public function clear()
    {
        // We loop through all the node and set their pointer to
        // null
        $current = $this->root;
        while (null !== $current) {
            $current->previous = null;
            $tmp = $current->next;
            $current = null;
            $current = $tmp;
        }
        $this->root = null;
        $this->tail = null;
        $this->size = 0;
    }

    public function stream()
    {
        return Stream::of($this->getIterator());
    }

    // region Miscellanous added as utility but do not use because O(n)=n
    /**
     * Performs a sequential search on the list. It will run at O(n)=n if the item
     * is not in the list or near the end of the list. Prefer use of other data structure
     * from PHP 8 \Ds namespace that are optimized for such search.
     *
     * @param \Closure|mixed $value
     *
     * @return int
     */
    public function find($value)
    {
        $predicate = \is_callable($value) && !\is_string($value) ?
            $value :
            static function ($current) use ($value) {
                return $value === $current;
            };
        $current = $this->root;
        $index = 0;
        while (null !== $current) {
            if ($predicate($current->value)) {
                return $index;
            }
            $current = $current->next;
            ++$index;
        }

        return -1;
    }

    public function at(int $index)
    {
        $current = $this->root;
        $i = 0;
        while (null !== $current) {
            if ($i === $index) {
                return $current->value;
            }
            $current = $current->next;
            ++$i;
        }

        return null;
    }
    // endregion

    #[\ReturnTypeWillChange]
    public function getIterator(): \Traversable
    {
        $current = null !== $this->root ? clone $this->root : $this->root;
        while (null !== $current) {
            yield $current->value;
            $current = $current->next;
        }
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
