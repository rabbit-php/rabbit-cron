<?php

declare(strict_types=1);

namespace Rabbit\Cron;

use Cron\DayOfMonthField;
use Cron\DayOfWeekField;
use Cron\FieldFactoryInterface;
use Cron\FieldInterface;
use Cron\HoursField;
use Cron\MinutesField;
use Cron\MonthField;
use Rabbit\Base\Exception\InvalidArgumentException;

class FieldFactory implements FieldFactoryInterface
{
    private array $fields = [];
    /**
     * @author Albert <63851587@qq.com>
     * @param integer $position
     * @return FieldInterface
     */
    public function getField(int $position): FieldInterface
    {
        return $this->fields[$position] ?? $this->fields[$position] = $this->instantiateField($position);
    }
    /**
     * @author Albert <63851587@qq.com>
     * @param [type] $position
     * @return FieldInterface
     */
    private function instantiateField($position): FieldInterface
    {
        switch ($position) {
            case CronExpression::MINUTE:
                return new MinutesField();
            case CronExpression::HOUR:
                return new HoursField();
            case CronExpression::DAY:
                return new DayOfMonthField();
            case CronExpression::MONTH:
                return new MonthField();
            case CronExpression::WEEKDAY:
                return new DayOfWeekField();
            case CronExpression::SECOND:
                return new SecondField();
        }

        throw new InvalidArgumentException(
            ($position + 1) . ' is not a valid position'
        );
    }
}
