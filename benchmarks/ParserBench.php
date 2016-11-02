<?php

use League\Uri\Parser;

require 'vendor/autoload.php';

class ParserBench
{
    private $uri = 'https://cnn.example.com&story=breaking_news@10.0.0.1/top_story.htm';

    /**
     * Baseline - comparison with parse_url
     */
    public function benchParseUrl()
    {
        parse_url($this->uri);
    }

    public function benchLeagueUriParser()
    {
        (new Parser())->__invoke($this->uri);
    }
}