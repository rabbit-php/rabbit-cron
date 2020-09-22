<?php

declare(strict_types=1);

namespace Rabbit\Cron;

interface StorageInterface
{
    public function get(string $name, string $column = null);
    public function set(string $name, array $values): void;
    public function exist(string $name): bool;
    public function del(string $name): void;
}
