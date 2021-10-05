<?php

namespace BennoThommo\PhpPurgeCss\Mapping;

use IvoPetkov\HTML5DOMDocument;

/**
 * HTML Map.
 *
 * Dilutes a single HTML document down to an array map with only the necessary information required for determining the
 * validity of CSS selectors.
 *
 * To do this, we only extract the following information:
 *   - HTML tags and their hierarchy
 *   - IDs and classes
 *   - Attributes for HTML tags
 */
class HtmlMap
{
    /**
     * Rendered element identifiers.
     *
     * @var array
     */
    protected $elements = [];

    /**
     * Hierarchy of elements, with their attributes (besides ID and classes)
     *
     * @var array
     */
    protected $hierarchy = [];

    /**
     * HTML IDs used in this document, linked to elements.
     *
     * @var array
     */
    protected $ids = [];

    /**
     * HTML tags used in this document, linked to elements.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * HTML classes used in this document, linked to elements.
     *
     * @var array
     */
    protected $classes = [];

    public function __construct(string $html)
    {
        $this->parse($html);
    }

    protected function parse(string $html): void
    {
        $dom = new HTML5DOMDocument();

        if (!$dom->loadHTML($html)) {
            throw new \Exception('Unable to parse the HTML code provided.');
        }

        if (!$dom->hasChildNodes()) {
            throw new \Exception('This appears to be an empty document.');
        }

        foreach ($dom->childNodes as $child) {
            if ($child instanceof \DOMElement === false) {
                continue;
            }
            $this->parseNode([], $child);
        }
    }

    protected function parseNode(array $hierarchy, \DOMElement $node): void
    {
        // Generate an ID for this node
        do {
            $id = uniqid();
        } while (isset($this->elements[$id]));

        // Normalise tag name
        $nodeName = trim($node->nodeName);

        // Skip head tag and all children, as it can't really be targeted by CSS
        if ($nodeName === 'head') {
            return;
        }

        // Register tag
        if (!in_array($nodeName, array_keys($this->tags))) {
            $this->tags[$nodeName] = [];
        }
        $this->tags[$nodeName][] = $id;

        // Get ID of element
        if ($node->hasAttribute('id')) {
            $elementId = $node->getAttribute('id');

            if (!in_array($elementId, array_keys($this->ids))) {
                $this->ids[$elementId] = [];
            }

            $this->ids[$elementId][] = $id;
        }

        // Get classes of element (split by spaces)
        if ($node->hasAttribute('class')) {
            $elementClasses = preg_split('/\s+/', $node->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);

            foreach ($elementClasses as $class) {
                if (!in_array($class, array_keys($this->classes))) {
                    $this->classes[$class] = [];
                }

                if (!in_array($id, $this->classes[$class])) {
                    $this->classes[$class][] = $id;
                }
            }
        }

        // Register element
        $this->elements[$id] = [
            'tag' => $nodeName,
            'id' => $elementId ?? null,
            'classes' => $elementClasses ?? [],
            'attrs' => $this->getAttributes($node),
        ];

        // Set the hierarchy and go through child nodes if there are any
        $newHierarchy = $hierarchy;
        $newHierarchy[] = $id;
        $this->hierarchy[] = $newHierarchy;

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMElement === false) {
                    continue;
                }
                $this->parseNode($newHierarchy, $child);
            }
        }
    }

    protected function getAttributes(\DOMElement $node): array
    {
        if ($node->attributes->length === 0) {
            return [];
        }

        $attributes = [];

        /** @var \DOMAttr */
        foreach ($node->attributes as $attribute) {
            if (!in_array($attribute->nodeName, ['id', 'class'])) {
                $attributes[$attribute->nodeName] = $attribute->nodeValue;
            }
        }

        return $attributes;
    }
}
