<?php

namespace BennoThommo\PhpPurgeCss\Mapping;

use BennoThommo\PhpPurgeCss\Mapping\Concerns\TraversesHierarchy;
use IvoPetkov\HTML5DOMDocument;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * HTML Map.
 *
 * Maps out a HTML code block with only the necessary information required for determining the validity of CSS
 * selectors.
 *
 * To do this, we only extract the following information:
 *   - HTML tags and their hierarchy
 *   - IDs and classes
 *   - Attributes for HTML tags
 *
 * We ignore <head> elements as these are not styleable.
 *
 * @author Ben Thomson <git@alfreido.com>
 * @since 1.0.0
 */
class HtmlMap
{
    use TraversesHierarchy;

    /**
     * Rendered element identifiers.
     *
     * @var array
     */
    protected $elements = [];

    /**
     * Hierarchy of elements. This maps as a simple array tree of IDs.
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

    /**
     * Constructor.
     *
     * Parses the given HTML immediately.
     *
     * @param string $html
     */
    public function __construct(string $html)
    {
        $this->parse($html);
    }

    /**
     * Gets all mapped elements.
     *
     * The array is formatted with the element ID as the key, and all pertinent element information as the value.
     *
     * @return array
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * Gets an individual element by the element ID.
     *
     * Note that the ID is *not* the HTML id attribute. It is an internal ID used by the HtmlMap instance.
     *
     * Returns `null` if no element by that element ID exists.
     *
     * @param string $elementId
     * @return array|null
     */
    public function getElement(string $elementId): ?array
    {
        if (!array_key_exists($elementId, $this->elements)) {
            return null;
        }

        return $this->elements[$elementId];
    }

    /**
     * Gets the hierarchy of mapped elements.
     *
     * This is presented as nested array of element IDs.
     *
     * @return array
     */
    public function getHierarchy(): array
    {
        return $this->hierarchy;
    }

    /**
     * Gets the IDs used in the HTML.
     *
     * This is presented as the ID as the key, and the element ID of all elements with that ID as the value.
     *
     * @return array
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * Gets the HTML tags used in the HTML.
     *
     * This is presented as the tag name as the key, and the element ID of all elements using that tag as the value.
     *
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Gets the classes used in the HTML.
     *
     * This is presented as the class name as the key, and the element ID of all elements using that class as a value.
     *
     * @return array
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * Determines if the given ID is used at all in the HTML.
     *
     * @param string $id
     * @return boolean
     */
    public function usesId(string $id): bool
    {
        return array_key_exists($id, $this->ids);
    }

    /**
     * Determines if the given class is used at all in the HTML.
     *
     * @param string $class
     * @return boolean
     */
    public function usesClass(string $class): bool
    {
        return array_key_exists($class, $this->classes);
    }

    /**
     * Determines if the given tag is used at all in the HTML.
     *
     * @param string $tag
     * @return boolean
     */
    public function usesTag(string $tag): bool
    {
        return array_key_exists($tag, $this->tags);
    }

    /**
     * Parses a HTML document and generates all information necessary for the map.
     *
     * This will recursively parse nodes and record information about the HTML in use as it traverses the DOM hierarchy
     * in the HTML document.
     *
     * @param string $html
     * @return void
     */
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
            $this->parseNode($this->hierarchy, $child);
        }
    }

    /**
     * Parse an individual node.
     *
     * Records all information about a HTML element node.
     *
     * @param array $hierarchy
     * @param \DOMElement $node
     * @return void
     */
    protected function parseNode(array &$hierarchy, \DOMElement $node): void
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
        if ($node->hasChildNodes()) {
            $foundChild = false;

            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMElement === false) {
                    continue;
                }
                if (!isset($hierarchy[$id])) {
                    $foundChild = true;
                    $hierarchy[$id] = [];
                }
                $this->parseNode($hierarchy[$id], $child);
            }

            if (!$foundChild) {
                $hierarchy[$id] = [];
            }
        } else {
            $hierarchy[$id] = [];
        }
    }

    /**
     * Get all attributes for a given HTML element as an array.
     *
     * @param \DOMElement $node
     * @return array
     */
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

    /**
     * Converts the whole or a portion of a hierarchy to an array of element data.
     *
     * @param array|RecursiveArrayIterator $hierarchy
     * @return array
     */
    protected function convertHierarchyToElements($hierarchy): array
    {
        $iterator = new RecursiveArrayIterator($hierarchy);
        $newHierarchy = [];

        foreach ($iterator as $key => &$item) {
            if ($iterator->hasChildren()) {
                $children = $this->convertHierarchyToElements($iterator->getChildren());

                $newHierarchy[$key] = array_merge($this->getElement($key), [
                    'children' => $children
                ]);
                continue;
            }

            $newHierarchy[$item] = $this->getElement($item);
        }

        return $newHierarchy;
    }
}
