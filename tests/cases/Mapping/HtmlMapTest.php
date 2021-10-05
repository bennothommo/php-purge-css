<?php

namespace BennoThommo\PhpPurgeCss\Tests\Cases\Mapping;

use BennoThommo\PhpPurgeCss\Mapping\HtmlMap;
use PHPUnit\Framework\TestCase;

class HtmlMapTest extends TestCase
{
    public function testParse(): void
    {
        $html = file_get_contents(__DIR__ . '/../../fixtures/set-001/source.html');
        $map = new HtmlMap($html);

        print_r($map);
        die();
    }
}
