<?php

namespace CashID\Cache;

class APCuCache implements RequestCacheInterface
{
    public function store(string $key, $var)
    {
        return apcu_store($key, $var);
    }

    public function fetch(string $key)
    {
        return apcu_fetch($key);
    }

    public function exists(string $key)
    {
        return apcu_exists($key);
    }
}
