<?php

function parseElement($contextNode, $data, DOMDocument $dom) {
    if (!$contextNode instanceof DOMElement) return;

    // CHECK FOR CMS-COLLECTION
    $collectionName = extractClassValue($contextNode, 'cms-collection-');
    if ($collectionName) {
        $collectionFile = "data/$collectionName.json";
        
        if (file_exists($collectionFile)) {
            $data = json_decode(file_get_contents($collectionFile), true) ?: [];
            
            // Inject collection awareness into the entire array
            foreach ($data as &$item) {
                if (is_array($item)) {
                    $item['_collection'] = $collectionName;
                }
            }

            $limit = extractClassValue($contextNode, 'cms-limit-');
            if ($limit !== null) {
                $limitInt = (int) $limit;
                $data = array_slice($data, 0, $limitInt);
            }
        } else {
            $data = [];
        }
        
        removeClassPrefix($contextNode, ['cms-collection-', 'cms-limit-']);
        
        if (hasClass($contextNode, 'cms-repeat')) {
            parseRepeat($contextNode, $data, $dom);
            return;
        }
    }

    //CHECK FOR CMS-ITEM-LINK (changed from cms-collection-link to prevent prefix collision)
    if (hasClass($contextNode, 'cms-item-link') && isset($data['_collection']) && isset($data['slug'])) {
        $url = "/" . $data['_collection'] . "/" . $data['slug'];
        $contextNode->setAttribute('href', $url);
        removeClassPrefix($contextNode, ['cms-item-link']);
    }

    //GET CMS KEY
    $cmsKey = null;
    if ($contextNode->hasAttribute('cms')) {
        $cmsKey = $contextNode->getAttribute('cms');
    }
    $nodeData = ($cmsKey && isset($data[$cmsKey])) ? $data[$cmsKey] : $data;

    //INJECT ATTRIBUTES
    if (is_array($nodeData)) {
        if (hasClass($contextNode, 'cms-inner')) {
            $contextNode->nodeValue = ''; 
            $contextNode->appendChild($dom->createTextNode($nodeData['inner'] ?? ''));
        }
        if (hasClass($contextNode, 'cms-href') && isset($nodeData['href'])) {
            $contextNode->setAttribute('href', $nodeData['href']);
        }
    }

    //LOAD CHILDERN IN STATIC ARRAY TO AVOID CONCURRENT MODIFICATION
    $children = iterator_to_array($contextNode->childNodes);

    //RECURSE
    foreach ($children as $childNode) {
        if ($childNode->nodeType !== XML_ELEMENT_NODE) continue;

        if ($childNode->hasAttribute('cms')) {
            $childCmsKey = $childNode->getAttribute('cms');

            //PARSE REPEAT
            if (isset($nodeData[$childCmsKey]) && hasClass($childNode, 'cms-repeat')) {
                parseRepeat($childNode, $nodeData[$childCmsKey], $dom);
            } 
            //PARSE ELEMENT
            else {
                parseElement($childNode, $nodeData, $dom);
            }
        } else {
            parseElement($childNode, $nodeData, $dom);
        }
    }

    //CLEANUP
    $contextNode->removeAttribute('cms');
}

?>