<?php

declare(strict_types=1);

namespace Rabbit\Cron;


use Rabbit\Base\App;
use Rabbit\Base\Core\Timer;
use Rabbit\Base\Exception\InvalidArgumentException;

class CronJob
{
    const STATUS_STOP = 0;
    const STATUS_RUNNING = 1;

    protected array $jobs = [];
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param StorageInterface $storage
     * @param array $jobs
     */
    public function __construct(private readonly StorageInterface $storage, array $jobs = [])
    {
        $this->jobs = $jobs;
    }
    /**
     * @author Albert <63851587@qq.com>
     * @param string $name
     * @param array $job
     * @return void
     */
    public function add(string $name, array $job, bool $autoRun = true, bool $existThrow = true): void
    {
        if ($this->storage->exist($name) && $this->storage->get($name)['run'] === self::STATUS_RUNNING) {
            if ($existThrow) {
                throw new InvalidArgumentException("Job $name running");
            }
            return;
        }
        if (!is_callable($job[1])) {
            throw new InvalidArgumentException("Job is not callable");
        }
        $this->jobs[$name] = $job;
        $autoRun && $this->run($name);
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
        $this->storage->del($name);
        return Timer::clearTimerByName($name);
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

        $this->storage->set($name, ['worker_id' => getmypid(), 'run' => self::STATUS_RUNNING, 'next' => date('Y-m-d H:i:s', $next1)]);

        Timer::addAfterTimer(($next1 - time()) * 1000, function () use ($name, $function, $tick) {
            $this->storage->incr($name, 'times');
            Timer::addTickTimer($tick * 1000, function () use ($name, $function, $tick) {
                $this->storage->set($name, ['next' => date('Y-m-d H:i:s', time() + $tick)]);
                $this->storage->incr($name, 'times');
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
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @return StorageInterface
     */
    public function getStorage(): StorageInterface
    {
        return $this->storage;
    }
}
