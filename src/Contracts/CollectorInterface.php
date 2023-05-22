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

interface CollectorInterface
{
    /**
     * Provides implementation that Creates a Ds from the stream output.
     *
     * @return mixed
     */
    public function __invoke(\Traversable $source);
}
