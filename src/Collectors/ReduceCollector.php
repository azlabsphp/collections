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

class ReduceCollector implements CollectorInterface
{
    /**
     * @var mixed
     */
    private $identity;

    /**
     * @var callable
     */
    private $reducer;

    /**
     * Creates a collector instance.
     *
     * @param mixed $identity
     */
    public function __construct(callable $reducer, $identity = null)
    {
        $this->identity = $identity ?? 0;
        $this->reducer = $reducer;
    }

    public function __invoke(\Traversable $source)
    {
        $result = $this->identity;
        foreach ($source as $current) {
            $result = ($this->reducer)($result, $current);
        }

        return $result;
    }
}
