<?php

declare(strict_types=1);

namespace Rabbit\Cron;

use Cron\CronExpression as CronCronExpression;
use Cron\FieldFactoryInterface;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Rabbit\Base\Exception\InvalidArgumentException;
use RuntimeException;

class CronExpression extends CronCronExpression
{
    public const SECOND = 0;
    public const MINUTE = 1;
    public const HOUR = 2;
    public const DAY = 3;
    public const MONTH = 4;
    public const WEEKDAY = 5;
    public const YEAR = 6;

    private $fieldFactory;

    private $maxIterationCount = 1000;

    private static $order = [self::YEAR, self::MONTH, self::DAY, self::WEEKDAY, self::HOUR, self::MINUTE, self::SECOND];
    /**
     * @author Albert <63851587@qq.com>
     * @param string $expression
     * @param FieldFactoryInterface $fieldFactory
     * @return CronExpression
     */
    public static function factory(string $expression, FieldFactoryInterface $fieldFactory = null): CronExpression
    {
        $mappings = [
            '@yearly' => '0 0 1 1 *',
            '@annually' => '0 0 1 1 *',
            '@monthly' => '0 0 1 * *',
            '@weekly' => '0 0 * * 0',
            '@daily' => '0 0 * * *',
            '@hourly' => '0 * * * *',
        ];

        $shortcut = strtolower($expression);
        if (isset($mappings[$shortcut])) {
            $expression = $mappings[$shortcut];
        }

        return new static($expression, $fieldFactory ?: new FieldFactory());
    }
    /**
     * @author Albert <63851587@qq.com>
     * @param string $expression
     * @param FieldFactory $fieldFactory
     */
    public function __construct(string $expression, FieldFactory $fieldFactory = null)
    {
        $this->fieldFactory = $fieldFactory ?: new FieldFactory();
        $this->setExpression($expression);
    }

    public function setPart(int $position, string $value): CronExpression
    {
        if (!$this->fieldFactory->getField($position)->validate($value)) {
            throw new InvalidArgumentException(
                'Invalid CRON field value ' . $value . ' at position ' . $position
            );
        }

        $this->cronParts[$position] = $value;

        return $this;
    }

    /**
     * @author Albert <63851587@qq.com>
     * @param [type] $currentTime
     * @param integer $nth
     * @param boolean $invert
     * @param boolean $allowCurrentDate
     * @param [type] $timeZone
     * @return DateTime
     */
    protected function getRunDate($currentTime = null, int $nth = 0, bool $invert = false, bool $allowCurrentDate = false, $timeZone = null): DateTime
    {
        $timeZone = $this->determineTimeZone($currentTime, $timeZone);

        if ($currentTime instanceof DateTime) {
            $currentDate = clone $currentTime;
        } elseif ($currentTime instanceof DateTimeImmutable) {
            $currentDate = DateTime::createFromFormat('U', $currentTime->format('U'));
        } else {
            $currentDate = new DateTime($currentTime ?: 'now');
        }

        $currentDate->setTimezone(new DateTimeZone($timeZone));
        // $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'), 0);

        $nextRun = clone $currentDate;

        // We don't have to satisfy * or null fields
        $parts = [];
        $fields = [];
        foreach (self::$order as $position) {
            $part = $this->getExpression($position);
            if (null === $part || '*' === $part) {
                continue;
            }
            $parts[$position] = $part;
            $fields[$position] = $this->fieldFactory->getField($position);
        }

        if (isset($parts[3]) && isset($parts[5])) {
            $domExpression = sprintf('%s %s %s %s %s *', $this->getExpression(0), $this->getExpression(1), $this->getExpression(2), $this->getExpression(3), $this->getExpression(4));
            $dowExpression = sprintf('%s %s %s * %s %s', $this->getExpression(0), $this->getExpression(1), $this->getExpression(2), $this->getExpression(4), $this->getExpression(5));

            $domExpression = new self($domExpression);
            $dowExpression = new self($dowExpression);

            $domRunDates = $domExpression->getMultipleRunDates($nth + 1, $currentTime, $invert, $allowCurrentDate, $timeZone);
            $dowRunDates = $dowExpression->getMultipleRunDates($nth + 1, $currentTime, $invert, $allowCurrentDate, $timeZone);

            $combined = array_merge($domRunDates, $dowRunDates);
            usort($combined, function ($a, $b) {
                return $a->format('Y-m-d H:i:s') <=> $b->format('Y-m-d H:i:s');
            });

            return $combined[$nth];
        }

        // Set a hard limit to bail on an impossible date
        for ($i = 0; $i < $this->maxIterationCount; ++$i) {
            foreach ($parts as $position => $part) {
                $satisfied = false;
                // Get the field object used to validate this part
                $field = $fields[$position];
                // Check if this is singular or a list
                if (false === strpos($part, ',')) {
                    $satisfied = $field->isSatisfiedBy($nextRun, $part);
                } else {
                    foreach (array_map('trim', explode(',', $part)) as $listPart) {
                        if ($field->isSatisfiedBy($nextRun, $listPart)) {
                            $satisfied = true;

                            break;
                        }
                    }
                }

                // If the field is not satisfied, then start over
                if (!$satisfied) {
                    $field->increment($nextRun, $invert, $part);

                    continue 2;
                }
            }

            // Skip this match if needed
            if ((!$allowCurrentDate && $nextRun == $currentDate) || --$nth > -1) {
                $this->fieldFactory->getField(0)->increment($nextRun, $invert, $parts[0] ?? null);

                continue;
            }

            return $nextRun;
        }

        // @codeCoverageIgnoreStart
        throw new RuntimeException('Impossible CRON expression');
        // @codeCoverageIgnoreEnd
    }
}
