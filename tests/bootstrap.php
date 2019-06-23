<?php
require_once 'Overriders/RandOverrider.php';
require_once 'Overriders/TimeOverrider.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
} elseif (file_exists(__DIR__ . '/../../../autoload.php')) {
    require_once(__DIR__ . '/../../../autoload.php');
}
