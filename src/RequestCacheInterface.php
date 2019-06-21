<?php

namespace CashID;

interface RequestCacheInterface
{
    public function store(string $key, $var);
    public function fetch(string $key);
    public function exists(string $key);
}
