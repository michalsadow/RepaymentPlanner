<?php

declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner\Exceptions;

use Przeslijmi\Sexceptions\Sexception;

/**
 * Defined payment of capital is below zero.
 */
class NegativePaymentException extends Sexception
{

    /**
     * Hint.
     *
     * @var string
     */
    protected $hint = 'Defined payment of capital is below zero.';

    /**
     * Keys for extra data array.
     *
     * @var array
     */
    protected $keys = [
        'date',
        'payment',
    ];
}
