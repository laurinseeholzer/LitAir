<?php
function parseRepeat($contextNode, $repeatData, DOMDocument $dom) {
    $parent = $contextNode->parentNode;
    
    //REPEAT
    foreach ($repeatData as $itemData) {
        $newNode = $contextNode->cloneNode(true);
        
        //REMOVE CMS CLASSES
        $classes = explode(' ', $newNode->getAttribute('class'));
        $filtered = array_diff($classes, ['cms-repeat']);
        
        if (empty($filtered)) {
            $newNode->removeAttribute('class');
        } else {
            $newNode->setAttribute('class', implode(' ', $filtered));
        }

        //PARSE ELEMENT
        parseElement($newNode, $itemData, $dom);
        
        //INJECT ELEMENT
        $parent->insertBefore($newNode, $contextNode);
    }
    
    //CLEANUP
    $parent->removeChild($contextNode);
}

?>