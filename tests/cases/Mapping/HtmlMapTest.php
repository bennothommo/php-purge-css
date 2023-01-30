<?php

namespace BennoThommo\PhpPurgeCss\Tests\Cases\Mapping;

use BennoThommo\PhpPurgeCss\Mapping\HtmlMap;
use PHPUnit\Framework\TestCase;

/**
 * @covers BennoThommo\PhpPurgeCss\Mapping\HtmlMap
 */
class HtmlMapTest extends TestCase
{
    /**
     * @var HtmlMap
     */
    protected $map;

    /**
     * @before
     */
    public function createMap(): void
    {
        $html = file_get_contents(__DIR__ . '/../../fixtures/set-001/source.html');
        $this->map = new HtmlMap($html);
    }

    /**
     * @testdox Successfully parses a HTML document
     */
    public function testSuccessfullyParsesAHtmlDocument(): void
    {
        $this->assertCount(8, $this->map->getElements());
        $this->assertCount(7, $this->map->getTags());
    }

    public function testCanFindIfATagIsUsed(): void
    {
        $this->assertTrue($this->map->usesTag('html'));
        $this->assertTrue($this->map->usesTag('p'));
        $this->assertFalse($this->map->usesTag('img'));
    }

    public function testCanFindIfAClassIsUsed(): void
    {
        $this->assertTrue($this->map->usesClass('square'));
        $this->assertTrue($this->map->usesClass('red'));
        $this->assertFalse($this->map->usesClass('rounded'));
    }

    /**
     * @testdox Can find if an ID is used
     */
    public function testCanFindIfAnIDIsUsed(): void
    {
        $this->assertTrue($this->map->usesId('content'));
        $this->assertFalse($this->map->usesId('footer'));
    }

    public function testCanGetDescendantsOfAnElement(): void
    {
        // Find body element
        $bodyId = $this->map->getTags()['body'][0];
        $descendants = $this->map->getDescendants($bodyId);

        $this->assertCount(3, $descendants);
        $this->assertEquals('div', array_values($descendants)[0]['tag']);
        $this->assertEquals('p', array_values($descendants)[1]['tag']);
        $this->assertEquals('footer', array_values($descendants)[2]['tag']);

        // Find <p> tag within square
        $pId = $this->map->getTags()['p'][0];
        $descendants = $this->map->getDescendants($pId);

        $this->assertCount(0, $descendants);

        // Find <p> tag outside square
        $pId = $this->map->getTags()['p'][1];
        $descendants = $this->map->getDescendants($pId);

        $this->assertCount(2, $descendants);
        $this->assertEquals('strong', array_values($descendants)[0]['tag']);
        $this->assertEquals('strong', array_values($descendants)[0]['tag']);
    }

    public function testCanGetSiblingsOfAnElement(): void
    {
        // Find footer element
        $footerId = $this->map->getTags()['footer'][0];
        $siblings = $this->map->getSiblings($footerId);

        $this->assertCount(2, $siblings);
        $this->assertEquals('div', array_values($siblings)[0]['tag']);
        $this->assertEquals('p', array_values($siblings)[1]['tag']);

        // Find <p> tag within square
        $pId = $this->map->getTags()['p'][0];
        $siblings = $this->map->getSiblings($pId);

        $this->assertCount(0, $siblings);

        // Find <strong> tag outside square inside a <p> tag
        $strongId = $this->map->getTags()['strong'][0];
        $siblings = $this->map->getSiblings($strongId);

        $this->assertCount(1, $siblings);
        $this->assertEquals('em', array_values($siblings)[0]['tag']);
    }
}
