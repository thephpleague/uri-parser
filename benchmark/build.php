<?php

require __DIR__.'/../src/functions.php';

$components = [
    'scheme' => 'http',
    'host' => 'uri.thephpleague.com',
    'path' => '/5.0',
];

for ($i = 0; $i < 100000; $i++) {
    League\Uri\build($components);
}
