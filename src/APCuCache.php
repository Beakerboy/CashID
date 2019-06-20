<?php

namespace CashID;

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
}
