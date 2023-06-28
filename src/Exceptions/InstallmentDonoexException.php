<?php

declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner\Exceptions;

use Przeslijmi\Sexceptions\Sexception;

/**
 * When no Installment found for this date.
 */
class InstallmentDonoexException extends Sexception
{

    /**
     * Hint.
     *
     * @var string
     */
    protected $hint = 'When no Installment found for this date.';

    /**
     * Keys for extra data array.
     *
     * @var array
     */
    protected $keys = [
        'date',
    ];
}
