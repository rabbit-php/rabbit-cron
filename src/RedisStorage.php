<?php

declare(strict_types=1);

namespace Rabbit\Cron;

use Rabbit\DB\Redis\Redis;
use Rabbit\Base\Helper\ArrayHelper;

class RedisStorage implements StorageInterface
{
    public function __construct(protected readonly Redis $redis)
    {
    }
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param string $name
     * @param string|null $column
     * @return void
     */
    public function get(string $name, ?string $column = null)
    {
        $ret = $this->redis->get($name);
        if (!$ret) {
            return $ret;
        }
        $arr = json_decode($ret, true);
        if ($column !== null) {
            return isset($arr[$column]) ? $arr[$column] : null;
        }
        return $arr;
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
        ($ret = $this->get($name)) && $values = array_merge($ret, $values);
        $this->redis->set($name, $values);
    }
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param string $name
     * @return boolean
     */
    public function exist(string $name): bool
    {
        return $this->redis->exist($name);
    }
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-22
     * @param string $name
     * @return void
     */
    public function del(string $name): void
    {
        $this->redis->delete($name);
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
        ($ret = $this->get($name)) && $ret[$column] = ArrayHelper::getValue($ret, $column, 0) + 1;
        $this->redis->set($name, $ret);
    }
}
