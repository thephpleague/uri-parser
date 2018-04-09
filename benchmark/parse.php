<?php

require __DIR__.'/../src/Parser/UriParser.php';
require __DIR__.'/../src/functions.php';

$uri = 'https://uri.thephpleague.com/5.0';
for ($i = 0; $i < 100000; $i++) {
    League\Uri\parse($uri);
}
