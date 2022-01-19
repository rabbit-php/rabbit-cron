<?php

declare(strict_types=1);

namespace Rabbit\Cron;

use Cron\AbstractField;
use Cron\FieldInterface;
use DateTimeInterface;

class SecondField extends AbstractField
{
    protected $rangeStart = 0;
    protected $rangeEnd = 59;

    /**
     * @author Albert <63851587@qq.com>
     * @param DateTimeInterface $date
     * @param [type] $value
     * @return boolean
     */
    public function isSatisfiedBy(DateTimeInterface $date, $value, bool $invert): bool
    {
        if ($value == '?') {
            return true;
        }

        return $this->isSatisfied((int)$date->format('s'), (string)$value);
    }

    /**
     * {@inheritdoc}
     * {@inheritDoc}
     *
     * @param \DateTime|\DateTimeImmutable &$date
     * @param string|null                  $parts
     */
    public function increment(DateTimeInterface &$date, $invert = false, $parts = null): FieldInterface
    {
        if (is_null($parts)) {
            $date = $date->modify(($invert ? '-' : '+') . '1 second');
            return $this;
        }

        $parts = false !== strpos($parts, ',') ? explode(',', $parts) : [$parts];
        $seconds = [];
        foreach ($parts as $part) {
            $seconds = [...$seconds, ...$this->getRangeForExpression($part, 59)];
        }

        $current_second = $date->format('s');
        $position = $invert ? \count($seconds) - 1 : 0;
        if (\count($seconds) > 1) {
            for ($i = 0; $i < \count($seconds) - 1; ++$i) {
                if ((!$invert && $current_second >= $seconds[$i] && $current_second < $seconds[$i + 1]) ||
                    ($invert && $current_second > $seconds[$i] && $current_second <= $seconds[$i + 1])
                ) {
                    $position = $invert ? $i : $i + 1;

                    break;
                }
            }
        }

        if ((!$invert && $current_second >= $seconds[$position]) || ($invert && $current_second <= $seconds[$position])) {
            $date = $date->modify(($invert ? '-' : '+') . '1 minute');
            $date = $date->setTime((int) $date->format('H'), (int) $date->format('i'), $invert ? 59 : 0);
        } else {
            $date = $date->setTime((int) $date->format('H'), (int) $date->format('i'), (int) $seconds[$position]);
        }

        return $this;
    }
}
