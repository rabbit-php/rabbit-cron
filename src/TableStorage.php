<?php

declare(strict_types=1);

namespace Rabbit\Cron;

use Swoole\Table;

class TableStorage implements StorageInterface
{
    private Table $table;
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param integer $size
     */
    public function __construct(int $size = 1024)
    {
        $this->table = new Table($size);
        $this->table->column('worker_id', Table::TYPE_INT);
        $this->table->column('run', Table::TYPE_INT);
        $this->table->column('next', Table::TYPE_STRING, 19);
        $this->table->column('times', Table::TYPE_INT);
        $this->table->create();
    }
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param string $name
     * @param string $column
     * @return void
     */
    public function get(string $name, string $column = null)
    {
        return $column ? $this->table->get($name, $column) : $this->table->get($name);
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
        $this->table->set($name, $values);
    }
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param string $name
     * @return boolean
     */
    public function exist(string $name): bool
    {
        return $this->table->exist($name);
    }
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param string $name
     * @return void
     */
    public function del(string $name): void
    {
        $this->table->del($name);
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
        $this->table->incr($name, $column, $incrby);
    }
}
