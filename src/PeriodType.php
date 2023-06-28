<?php

declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner;

use DateInterval;
use DateTime;
use Przeslijmi\RepaymentPlanner\Exceptions\UnknownPeriodTypeException;

/**
 * Period type (month/quarter/year) to use for calculations.
 */
class PeriodType
{

    /**
     * Type of period to use in installments.
     *
     * @var string
     */
    private $type;

    /**
     * Period interval for period type calculations.
     *
     * @var DateInterval
     */
    private $interval;

    /**
     * Constructor that creates period type (yearly, halfYearly, quarterly, monthly).
     *
     * @param string $type Period type to use (yearly, halfYearly, quarterly, monthly).
     *
     * @throws UnknownPeriodTypeException When period type is inproper.
     * @return self
     */
    public function __construct(string $type)
    {

        // Throw.
        if (in_array($type, [ 'yearly', 'halfYearly', 'quarterly', 'monthly' ]) === false) {
            throw new UnknownPeriodTypeException([ $type, 'monthly, quarterly, halfYearly, yearly' ]);
        }

        // Save type.
        if ($type === 'yearly') {
            $this->type = 'year';
        } elseif ($type === 'halfYearly') {
            $this->type = 'halfYear';
        } elseif ($type === 'quarterly') {
            $this->type = 'quarter';
        } elseif ($type === 'monthly') {
            $this->type = 'month';
        }

        // Save interval.
        if ($this->type === 'year') {
            $this->interval = new DateInterval('P1Y');
        } elseif ($this->type === 'halfYear') {
            $this->interval = new DateInterval('P6M');
        } elseif ($this->type === 'quarter') {
            $this->interval = new DateInterval('P3M');
        } elseif ($this->type === 'month') {
            $this->interval = new DateInterval('P1M');
        }

        return $this;
    }

    /**
     * Getter for interval.
     *
     * @return DateInterval
     */
    public function getInterval(): DateInterval
    {

        return $this->interval;
    }

    /**
     * Return number of possible periods in one year.
     *
     * @return integer
     */
    public function getPeriodsInYear(): int
    {

        if ($this->getType() === 'month') {
            return 12;
        } elseif ($this->getType() === 'quarter') {
            return 4;
        } elseif ($this->getType() === 'halfYear') {
            return 2;
        }

        return 1;
    }

    /**
     * Getter for type.
     *
     * @return string
     */
    public function getType(): string
    {

        return $this->type;
    }
}
