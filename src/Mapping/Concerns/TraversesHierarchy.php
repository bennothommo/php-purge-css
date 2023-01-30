<?php

namespace BennoThommo\PhpPurgeCss\Mapping\Concerns;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

trait TraversesHierarchy
{
    /**
     * Gets the descendants of a given element and returns an element map.
     *
     * @param string $elementId
     * @return array|null
     */
    public function getDescendants(string $elementId): ?array
    {
        if (is_null($this->getElement($elementId))) {
            return null;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($this->getHierarchy()),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $subHierarchy = null;

        foreach ($iterator as $key => $item) {
            if ($key === $elementId) {
                $subHierarchy = $item;
                break;
            }
        }

        if (is_null($subHierarchy)) {
            return [];
        }

        return $this->convertHierarchyToElements($subHierarchy);
    }

    /**
     * Gets the siblings of a given element and returns an element map.
     *
     * @param string $elementId
     * @return array|null
     */
    public function getSiblings(string $elementId): ?array
    {
        if (is_null($this->getElement($elementId))) {
            return null;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($this->getHierarchy()),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $subHierarchy = null;

        foreach ($iterator as $key => $item) {
            if ($key === $elementId) {
                $subHierarchy = array_filter(array_keys($iterator->getArrayCopy()), function ($item) use ($key) {
                    return $item !== $key;
                });
                break;
            }
        }

        if (is_null($subHierarchy)) {
            return [];
        }

        return $this->convertHierarchyToElements($subHierarchy);
    }
}
