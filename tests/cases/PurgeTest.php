<?php

namespace BennoThommo\PhpPurgeCss\Tests\Cases;

use DirectoryIterator;
use BennoThommo\PhpPurgeCss\Purge;
use PHPUnit\Framework\TestCase;

class PurgeTest extends TestCase
{
    public function setProvider(): array
    {
        $directory = new DirectoryIterator(__DIR__ . '/../fixtures/');
        $items = [];

        foreach ($directory as $item) {
            if (!$item->isDir() || $item->isDot()) {
                continue;
            }

            $items[] = [
                $item->getPathname() . '/source.css',
                $item->getPathname() . '/source.html',
                $item->getPathname() . '/expected.css',
            ];
        }

        return $items;
    }

    /**
     * @dataProvider setProvider
     */
    public function testPurgeSets(string $sourceCss, string $sourceHtml, string $expectedCss): void
    {
        $purge = new Purge();

        $purge->addCssFile($sourceCss);
        $purge->addHtmlFile($sourceHtml);
        $purged = $purge->purge();

        $this->assertEquals($expectedCss, $purged);
    }
}
