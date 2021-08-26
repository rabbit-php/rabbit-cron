<?php

declare(strict_types=1);

namespace Rabbit\Cron;

class ArrayStorage implements StorageInterface
{
    private array $table = [];
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param string $name
     * @param string $column
     * @return void
     */
    public function get(string $name, string $column = null)
    {
        return $column ? $this->table[$name][$column] ?? null : $this->table[$name] ?? null;
    }
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param string $name
     * @param array $values
     * @return void
     */
    public function set(string $name, array $values): void
    {
        $this->table[$name] = $values;
    }
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param string $name
     * @return boolean
     */
    public function exist(string $name): bool
    {
        return isset($this->table[$name]);
    }
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param string $name
     * @return void
     */
    public function del(string $name): void
    {
        unset($this->table[$name]);
        $this->table = array_slice($this->table, 0, null, true);
    }
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-10-12
     * @param string $name
     * @param string $column
     * @param integer $incrby
     * @return void
     */
    public function incr(string $name, string $column, int $incrby = 1): void
    {
        $this->table[$name][$column] ??= 0;
        $this->table[$name][$column] += $incrby;
    }
}
