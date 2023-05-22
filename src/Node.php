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

class Node
{
    /**
     * @var mixed
     */
    public $value;

    /**
     * Pointer to the next node.
     *
     * @var self
     */
    public $next;

    /**
     * Pointer to the previous node.
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
