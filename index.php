<?php

require_once 'vendor/autoload.php';

$spider = new App\Spider;
$spider->crawl('http://kisima.app');