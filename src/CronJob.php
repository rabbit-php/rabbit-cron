<?php

declare(strict_types=1);

namespace Rabbit\Cron;

use Rabbit\Base\App;
use Rabbit\Base\Core\Timer;
use Rabbit\Base\Exception\InvalidArgumentException;
use Swoole\Table;

class CronJob
{
    const STATUS_STOP = 'stop';
    const STATUS_RUNNING = 'running';

    protected array $jobs = [];

    private Table $table;
    /**
     * @author Albert <63851587@qq.com>
     * @param array $jobs
     */
    public function __construct(array $jobs = [])
    {
        $this->table = new Table(1024);
        $this->table->column('worker_id', Table::TYPE_INT);
        $this->table->column('run', Table::TYPE_STRING, 16);
        $this->table->column('next', Table::TYPE_STRING, 19);
        $this->table->create();
        $this->jobs = $jobs;
    }
    /**
     * @author Albert <63851587@qq.com>
     * @param string $name
     * @param array $job
     * @return void
     */
    public function add(string $name, array $job, bool $existThrow = true): void
    {
        if ($this->table->exist($name)) {
            if ($existThrow) {
                throw new InvalidArgumentException("Job $name exists");
            }
            return;
        }
        if (!is_callable($job[1])) {
            throw new InvalidArgumentException("Job is not callable");
        }
        $this->jobs[$name] = $job;
    }
    /**
     * @author Albert <63851587@qq.com>
     * @param string $name
     * @return void
     */
    public function stop(string $name): bool
    {
        if (!isset($this->jobs[$name])) {
            return true;
        }
        $this->table->set($name, ['run' => self::STATUS_STOP]);
        return Timer::stopTimer($name);
    }
    /**
     * @author Albert <63851587@qq.com>
     * @param string $name
     * @return void
     */
    public function remove(string $name): void
    {
        $this->stop($name);
        unset($this->jobs[$name]);
    }
    /**
     * @author Albert <63851587@qq.com>
     * @param string $name
     * @return void
     */
    public function restart(string $name): void
    {
        $this->run($name);
    }

    /**
     * @author Albert <63851587@qq.com>
     * @param string|null $name
     * @return void
     */
    public function run(?string $name = null): void
    {
        if ($name === null) {
            foreach ($this->jobs as $name => [$expression, $function]) {
                $this->cron($name, $expression, $function);
            }
        } elseif (isset($this->jobs[$name])) {
            $this->cron($name, ...$this->jobs[$name]);
        }
    }
    /**
     * @author Albert <63851587@qq.com>
     * @param [type] $name
     * @param string $expression
     * @param callable $function
     * @return void
     */
    public function cron(string $name, string $expression, callable $function): void
    {
        $cron = CronExpression::factory($expression);
        $next1 = $cron->getNextRunDate()->getTimestamp();
        $next2 = $cron->getNextRunDate(null, 1)->getTimestamp();
        $tick = $next2 - $next1;

        $this->table->set($name, ['worker_id' => App::$id, 'run' => self::STATUS_RUNNING, 'next' => date('Y-m-d H:i:s', $next1)]);

        Timer::addAfterTimer(($next1 - time()) * 1000, function () use ($name, $function, $tick) {
            Timer::addTickTimer($tick * 1000, function () use ($name, $function, $tick) {
                $this->table->set($name, ['next' => date('Y-m-d H:i:s', time() + $tick)]);
                $function();
            }, $name);
            $function();
        }, $name);
    }

    /**
     * @author Albert <63851587@qq.com>
     * @return array
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }
    /**
     * @author Albert <63851587@qq.com>
     * @return Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }
}
