<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers.php';

use Dotenv\Dotenv;

// LOAD ENVIRONMENT VARIABLES
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
