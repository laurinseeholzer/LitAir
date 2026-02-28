<?php

function hasClass($node, $className) {
    if (!$node->hasAttribute('class')) return false;
    $classes = explode(' ', $node->getAttribute('class'));
    return in_array($className, $classes);
}

function extractClassValue($node, $prefix) {
    if (!$node->hasAttribute('class')) return null;
    $classes = explode(' ', $node->getAttribute('class'));
    foreach ($classes as $class) {
        if (strpos($class, $prefix) === 0) {
            return substr($class, strlen($prefix));
        }
    }
    return null;
}

function removeClassPrefix($node, array $prefixes) {
    if (!$node->hasAttribute('class')) return;
    $classes = explode(' ', $node->getAttribute('class'));
    
    $filtered = array_filter($classes, function($class) use ($prefixes) {
        foreach ($prefixes as $p) {
            if (strpos($class, $p) === 0) return false;
        }
        return true;
    });

    if (empty($filtered)) {
        $node->removeAttribute('class');
    } else {
        $node->setAttribute('class', implode(' ', $filtered));
    }
}

?>