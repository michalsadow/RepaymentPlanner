<?php

declare(strict_types=1);

namespace Przeslijmi\RepaymentPlanner;

use DateInterval;
use DateTime;
use Przeslijmi\RepaymentPlanner\Exceptions\InstallmentDonoexException;
use Przeslijmi\RepaymentPlanner\Exceptions\PeriodIsBeyondPlanException;
use Przeslijmi\RepaymentPlanner\Installment;

/**
 * Collection of Installment objects - part of Schedule.
 */
class Installments
{

    /**
     * Parent Schedule object.
     *
     * @var Schedule
     */
    private $schedule;

    /**
     * Array with Installments.
     *
     * @var Installment[]
     */
    private $installments = [];

    /**
     * Global difference (for annuit).
     *
     * @var float
     */
    private $globalDiff = 0.0;

    /**
     * Possible first capital (for annuit).
     *
     * @var float
     */
    private $firstCapitalPossible = 0.0;

    /**
     * Constructor.
     *
     * @param Schedule $schedule Parent schedule object.
     */
    public function __construct(Schedule $schedule)
    {

        // Save.
        $this->schedule = $schedule;

        // Create empty installments.
        $this->reset();
    }

    /**
     * Getter for Parent Schedule object.
     *
     * @return Schedule
     */
    public function getSchedule(): Schedule
    {

        return $this->schedule;
    }

    /**
     * Getter for all Installments.
     *
     * @return Installment[]
     */
    public function getAll(): array
    {

        return $this->installments;
    }

    /**
     * Return Installment for given date.
     *
     * @param DateTime $date Date to analize.
     *
     * @throws InstallmentDonoexException When no Installment found for this date.
     * @return Installment
     */
    public function getInstallmentForDate(DateTime $date): Installment
    {

        // Search.
        foreach ($this->installments as $installment) {

            if (
                $date >= $installment->getPeriod()->getFirstDay()
                && $date <= $installment->getPeriod()->getLastDay()
            ) {
                return $installment;
            }
        }

        throw new InstallmentDonoexException([ $date->format('Y-m-d') ]);
    }

    /**
     * Return length of Installment array.
     *
     * @return integer
     */
    public function length(): int
    {

        return count($this->installments);
    }

    /**
     * Get sum of all interests payed.
     *
     * @return float
     */
    public function getSumOfInterests(): float
    {

        // Lvd.
        $sum = 0.0;

        // Sum.
        foreach ($this->installments as $installment) {
            $sum += $installment->getInterests();
        }

        return $sum;
    }

    /**
     * Get sum of all capitals payed.
     *
     * @return float
     */
    public function getSumOfCapital(): float
    {

        // Lvd.
        $sum = 0.0;

        // Sum.
        foreach ($this->installments as $installment) {
            $sum += $installment->getCapital();
        }

        return $sum;
    }

    /**
     * Recreate all empty Installments.
     *
     * @return self
     */
    private function reset(): self
    {

        // Clear all installments.
        $this->installments = [];

        // Add first installment.
        $this->addInstallment(new Installment($this, $this->schedule->getStart()));

        // Clone date.
        $date = clone $this->schedule->getStart();

        // Go through the rest.
        do {

            // Move date.
            $date->add($this->schedule->getPeriodType()->getInterval());

            // Add next installments until schedule deadline is exceeded.
            try {
                $this->addInstallment(new Installment($this, $date));
            } catch (PeriodIsBeyondPlanException $sexc) {
                break;
            }
        } while (true);

        return $this;
    }

    /**
     * Adds one Intallment.
     *
     * @param Installment $installment Installment to be added.
     *
     * @return self
     */
    private function addInstallment(Installment $installment): self
    {

        // Lvd.
        $name = $installment->getPeriod()->getName();

        // Save.
        $this->installments[$name] = $installment;

        // Set order.
        $installment->setOrder(count($this->installments));

        return $this;
    }

    /**
     * Adder of global difference (for annuit).
     *
     * @param float $amount Global difference (for annuit).
     *
     * @return self
     */
    public function addToGlobalDiff(float $amount): self
    {

        $this->globalDiff += $amount;

        return $this;
    }

    /**
     * Getter for global difference (for annuit).
     *
     * @return float
     */
    public function getGlobalDiff(): float
    {

        return $this->globalDiff;
    }

    /**
     * Adder of possible first capital  (for annuit).
     *
     * @param float $amount Possible first capital  (for annuit).
     *
     * @return self
     */
    public function addToFirstCapitalPossible(float $amount): self
    {

        $this->firstCapitalPossible += $amount;

        return $this;
    }

    /**
     * Cleans and returns possible first capital (for annuit).
     *
     * @return float
     */
    public function clearFirstCapitalPossible(): float
    {

        // Save.
        $result = $this->firstCapitalPossible;

        // Clear.
        $this->firstCapitalPossible = 0.0;

        return $result;
    }
}
