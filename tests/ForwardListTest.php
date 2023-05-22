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

use Drewlabs\Collections\ForwardList;
use Drewlabs\Collections\Streams\Stream;
use PHPUnit\Framework\TestCase;

class ForwardListTest extends TestCase
{
    public function test_contructor()
    {
        $list = new ForwardList();
        $this->assertInstanceOf(ForwardList::class, $list, 'Expect the constructor to run successfully');
    }

    public function test_list_grow_when_push_is_called()
    {
        $list = new ForwardList();
        $list->push(1);
        $list->push(2);
        $this->assertSame(2, $list->size());
    }

    public function test_is_empty_returs_false_if_item_pushed_to_list()
    {
        $list = new ForwardList();
        $this->assertTrue($list->isEmpty());
        $list->push(2);
        $this->assertFalse($list->isEmpty());
        $list->push(4);
        $this->assertSame([2, 4], $list->toArray());
    }

    public function test_list_shrink_when_pop_is_called()
    {
        $list = new ForwardList();
        $list->push(1);
        $list->push(2);
        $this->assertSame(2, $list->size());
        $result = $list->pop();
        $this->assertSame(2, $result);
        $this->assertSame(1, $list->size());
    }

    public function test_list_first()
    {
        $list = new ForwardList();
        $this->assertNull($list->first());
        $list->push(1);
        $list->push(2);
        $this->assertSame(1, $list->first());
    }

    public function test_list_last()
    {
        $list = new ForwardList();
        $this->assertNull($list->last());
        $list->push(1);
        $list->push(2);
        $this->assertSame(2, $list->last());
    }

    public function test_list_clear()
    {
        $list = new ForwardList();
        $list->push(1);
        $list->push(2);
        $list->clear();
        $this->assertSame(0, $list->size());
    }

    public function test_create_list_from_iterator()
    {
        $list = new ForwardList(new \ArrayIterator([1, 2, 3, 4, 5]));
        $this->assertTrue(5 === $list->size());
        $this->assertSame(1, $list->first());
        $this->assertSame(5, $list->last());
    }

    public function test_list_to_iterator()
    {
        $list = new ForwardList(new \ArrayIterator([1, 2, 3, 4, 5]));
        $this->assertInstanceOf(\Traversable::class, $list->getIterator());
    }

    public function test_list_to_stream()
    {
        $list = new ForwardList(new \ArrayIterator([1, 2, 3, 4, 5]));
        $this->assertInstanceOf(Stream::class, $list->stream());
        $this->assertSame([1, 2, 3, 4, 5], $list->stream()->toArray());
    }

    public function test_list_to_array()
    {
        $list = new ForwardList(new \ArrayIterator([1, 2, 3, 4, 5]));
        $this->assertSame([1, 2, 3, 4, 5], $list->toArray());
    }

    public function test_value_at()
    {
        $list = new ForwardList(new \ArrayIterator([1, 2, 3, 4, 5]));
        $this->assertSame(3, $list->at(2));
        $this->assertNull($list->at(5));
    }

    public function test_find_index()
    {
        $list = new ForwardList(new \ArrayIterator([1, 2, 3, 4, 5]));
        $this->assertSame(2, $list->find(3));
        $this->assertSame(2, $list->find(static function ($value) {
            return 3 === $value;
        }));
        $this->assertSame(-1, $list->find(10));
    }
}
