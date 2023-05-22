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

/**
 * @internal
 */
class CompareValueFactory
{
    /**
     * @var string
     */
    private $operator;

    /**
     * @var string|mixed
     */
    private $key;

    /**
     * @var mixed
     */
    private $value;

    /**
     * Creates class instance.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function __construct($key, string $operator, $value)
    {
        $this->key = $key;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function __invoke($item)
    {
        $result = drewlabs_core_get($item, $this->key);
        $items = array_filter([$result, $this->value], static function ($value) {
            return \is_string($value) || (\is_object($value) && method_exists($value, '__toString'));
        });
        if (\count($items) < 2 && 1 === \count(array_filter([$result, $this->value], 'is_object'))) {
            return \in_array($this->operator, ['!=', '<>', '!=='], true);
        }
        switch ($this->operator) {
            default:
            case '=':
            case '==':
                return $result === $this->value;
            case '!=':
            case '<>':
                return $result !== $this->value;
            case '<':
                return $result < $this->value;
            case '>':
                return $result > $this->value;
            case '<=':
                return $result <= $this->value;
            case '>=':
                return $result >= $this->value;
            case '===':
                return $result === $this->value;
            case '!==':
                return $result !== $this->value;
        }
    }

    /**
     * Creates new class instance.
     *
     * @param mixed $key
     * @param mixed $operator
     * @param mixed $value
     *
     * @return CompareValueFactory
     */
    public static function new($key, $operator = null, $value = null)
    {
        [$key, $operator, $value] = 1 === \func_num_args() ? [$key, '=', true] : (2 === \func_num_args() ? [$key, '=', $operator] : [$key, $operator, $value]);

        return new self($key, $operator, $value);
    }
}
