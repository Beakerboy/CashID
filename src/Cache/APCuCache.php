<?php

namespace CashID\Cache;

class APCuCache implements RequestCacheInterface
{
    /**
     * @inheritdoc
     */
    public function store(string $key, $var)
    {
        return apcu_store($key, $var);
    }

    /**
     * @inheritdoc
     */
    public function fetch(string $key)
    {
        return apcu_fetch($key);
    }

    /**
     * @inheritdoc
     */
    public function exists(string $key)
    {
        return apcu_exists($key);
    }
}
