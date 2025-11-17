<?php

require __DIR__.'/../vendor/autoload.php';
$p = new App\Models\Post;
var_dump($p->getCasts());
