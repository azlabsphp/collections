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

namespace Drewlabs\Collections\Contracts;

interface Collectable
{
    /**
     * Collect the output of the a given data structure.
     *
     * @param CollectorInterface|\Closure(\Traversable $source): T $collector
     *
     * @throws Exception
     *
     * @return \Traversable|array|mixed
     */
    public function collect(callable $collector);
}
