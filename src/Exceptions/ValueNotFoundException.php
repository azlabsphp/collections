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

namespace Drewlabs\Collections\Exceptions;

use Exception;

class ValueNotFoundException extends \Exception
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * Creates exception class instance.
     *
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
        parent::__construct('Error!');
    }

    public function getValue()
    {
        return $this->value;
    }
}
