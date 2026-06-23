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

/** @template T */
class Node
{
    /**
     * @var T
     */
    public $value;

    /**
     * pointer to the next node.
     *
     * @var self
     */
    public $next;

    /**
     * pointer to the previous node.
     *
     * @var self
     */
    public $previous;

    /**
     * Creates a new class instance.
     *
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
