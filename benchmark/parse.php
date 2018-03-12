<?php

require __DIR__.'/../vendor/autoload.php';

$uri = 'https://uri.thephpleague.com/5.0';
for ($i = 0; $i < 100000; $i++) {
    League\Uri\parse($uri);
}
