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

namespace Drewlabs\Collections\Utils;

class DefaultValue
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * Creates class instance.
     *
     * @param mixed $value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }

    /**
     * Returns the `value` property value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
