<?php
/**
 * Admin Action: Scaffold JSON from Template
 *
 * Reads a template HTML file, walks all cms="key" nodes, infers the expected
 * JSON schema (inner / href / repeat), and deep-merges it INTO the existing
 * data file — adding missing keys only, never overwriting existing values.
 *
 * Invoked by: admin.php?action=scaffold  (POST with ?target=page.html)
 */

/**
 * Recursively build a schema stub from a DOMElement that has a cms= attribute.
 *
 * Rules:
 *  - cms-inner   → the cms key maps to {"inner": ""}
 *  - cms-href    → the cms key maps to {"href": ""}
 *  - A node can have BOTH cms-inner and cms-href → {"inner":"","href":""}
 *  - cms-repeat  → the cms key maps to [<schema of children>]
 *  - Child nodes with their own cms= attributes are resolved recursively.
 */
function buildSchema(DOMElement $node): mixed {
    $classes = $node->hasAttribute('class')
        ? explode(' ', $node->getAttribute('class'))
        : [];

    $isRepeat = in_array('cms-repeat', $classes);
    $isInner  = in_array('cms-inner', $classes);
    $isHref   = in_array('cms-href', $classes);
    $isSrc    = in_array('cms-src', $classes);

    if ($isRepeat) {
        // Build schema for the child elements of the repeat item
        $itemSchema = buildChildSchema($node);

        // The repeat element itself might also carry content classes (e.g. a <span> that is
        // BOTH the repeat item AND the text field). Merge those in so we get {"inner":""} etc.
        if ($isInner && !isset($itemSchema['inner'])) $itemSchema['inner'] = '';
        if ($isHref  && !isset($itemSchema['href']))  $itemSchema['href']  = '';
        if ($isSrc   && !isset($itemSchema['src']))   $itemSchema['src']   = '';

        // Fallback: if still empty, treat as a plain text field
        if (empty($itemSchema)) $itemSchema = ['inner' => ''];

        return [$itemSchema];
    }

    $schema = [];
    if ($isInner) {
        $schema['inner'] = '';
    }
    if ($isHref) {
        $schema['href'] = '';
    }
    if ($isSrc) {
        $schema['src'] = '';
    }

    // Recurse into children that carry their own cms= keys
    $childSchema = buildChildSchema($node);
    $schema = array_merge($schema, $childSchema);

    // Only fall back to {'inner':''} if no specific cms-* class was recognised
    // and there are no named children either (plain text field with no class set)
    if (empty($schema) && !$isHref && !$isSrc) {
        return ['inner' => ''];
    }

    return $schema;
}

/**
 * Walk the direct children of $node and collect keyed sub-schemas.
 */
function buildChildSchema(DOMElement $node): array {
    $schema = [];
    foreach ($node->childNodes as $child) {
        if (!($child instanceof DOMElement)) continue;

        if ($child->hasAttribute('cms')) {
            $key = $child->getAttribute('cms');
            $schema[$key] = buildSchema($child);
        } else {
            // Transparent wrapper — descend into it
            $deeper = buildChildSchema($child);
            $schema = array_merge($schema, $deeper);
        }
    }
    return $schema;
}

/**
 * Deep-merge $new (schema) INTO $existing data.
 * - Keys are output in HTML/DOM order (schema order).
 * - Orphaned keys (in $existing but not in $new) are DROPPED, keeping the JSON
 *   in sync with the template.
 * - 'slug' is always preserved since it lives in data but never in the template.
 * - Existing values are NEVER overwritten.
 */
function deepMergeKeepExisting(array $existing, array $new): array {
    $result = [];

    // Walk schema keys in HTML order, filling from existing where available
    foreach ($new as $key => $schemaValue) {
        if (!array_key_exists($key, $existing)) {
            // Key is missing — add the schema stub
            $result[$key] = $schemaValue;
        } elseif (is_array($schemaValue) && is_array($existing[$key])) {
            $existingIsList = array_is_list($existing[$key]);
            $newIsList      = array_is_list($schemaValue);

            if ($existingIsList && $newIsList && !empty($schemaValue)) {
                // Repeat array: recurse schema into every existing item
                $itemSchema = $schemaValue[0];
                $mapped = array_map(function($item) use ($itemSchema) {
                    return is_array($item) && !array_is_list($item)
                        ? deepMergeKeepExisting($item, $itemSchema)
                        : $item;
                }, $existing[$key]);
                $result[$key] = empty($mapped) ? [$itemSchema] : $mapped;
            } elseif (!$existingIsList && !$newIsList) {
                // Nested object: recurse to preserve HTML order within it too
                $result[$key] = deepMergeKeepExisting($existing[$key], $schemaValue);
            } else {
                // Type mismatch — preserve existing as-is
                $result[$key] = $existing[$key];
            }
        } else {
            // Scalar — preserve existing value
            $result[$key] = $existing[$key];
        }
    }

    // Always preserve 'slug' — it's the collection routing key and will
    // never appear as a cms= attribute in any template.
    if (array_key_exists('slug', $existing) && !array_key_exists('slug', $result)) {
        $result['slug'] = $existing['slug'];
    }

    // NOTE: all other keys not in $new are intentionally dropped (orphan pruning).

    return $result;
}

// ─── Entry point ────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$target = $_POST['target'] ?? '';
$isCollection = isset($_POST['is_collection']) && $_POST['is_collection'] == '1';
$slug   = $_POST['slug'] ?? '';

// ─── Resolve the template file ───────────────────────────────────────────────
// For singletons $target is the template filename (e.g. 'index.html').
// For collections $target is the cms-collection value (e.g. 'courses') — NOT
// the filename — so we scan all templates for the matching meta tag.

$templateFile = null;
$dataFileName = null;

if ($isCollection) {
    // Find the template whose <meta name="cms-collection" content="..."> matches $target
    foreach (glob('templates/*.html') ?: [] as $candidate) {
        $html = file_get_contents($candidate);
        if (preg_match('/<meta\s+name="cms-collection"\s+content="([^"]+)"/i', $html, $m) && $m[1] === $target) {
            $templateFile = $candidate;
            break;
        }
    }
    // Data file is keyed by the collection name (target) directly
    $dataFileName = $target;
} else {
    // Singleton: target is the template filename
    $base = 'templates/' . basename($target);
    $templateFile = file_exists($base) ? $base : (file_exists($base . '.html') ? $base . '.html' : null);
    $dataFileName = basename($target, '.html');
}

$dataFile = "data/{$dataFileName}.json";

if (!$templateFile) {
    $errorRedirect = $isCollection
        ? "admin.php?action=list&target=" . urlencode($target) . "&scaffold_error=no_template"
        : "admin.php?action=edit&target=" . urlencode($target) . "&scaffold_error=no_template";
    header("Location: $errorRedirect");
    exit;
}

// ─── Parse the template ──────────────────────────────────────────────────────

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTMLFile($templateFile);
libxml_clear_errors();

$xpath = new DOMXPath($dom);

// Find ALL elements with a cms= attribute (but skip the <meta> tags in <head>)
$cmsNodes = $xpath->query('//*[@cms][not(ancestor-or-self::head)]');

// Build a flat schema map: key => schema
$schemaMap = [];
foreach ($cmsNodes as $node) {
    $key = $node->getAttribute('cms');

    // Walk ancestors to detect two cases to skip:
    // 1. The node is inside another cms= element (it'll be captured by that parent's buildSchema)
    // 2. The node is inside a cms-collection-* element (its data lives in a different JSON file)
    $ancestor = $node->parentNode;
    $skipNode = false;
    while ($ancestor && $ancestor instanceof DOMElement) {
        if ($ancestor->hasAttribute('cms')) {
            $skipNode = true; // inside another cms= parent
            break;
        }
        // Check for any cms-collection-* class on this ancestor
        $classList = preg_split('/\s+/', $ancestor->getAttribute('class'));
        foreach ($classList as $cls) {
            if (str_starts_with($cls, 'cms-collection-')) {
                $skipNode = true; // inside a collection subtree — different file
                break 2;
            }
        }
        $ancestor = $ancestor->parentNode;
    }
    if ($skipNode) continue;

    $schemaMap[$key] = buildSchema($node);
}

// ─── Merge into existing data ────────────────────────────────────────────────

if ($isCollection) {
    // Collection-level scaffold: merge schema into EVERY existing item
    $existing = file_exists($dataFile)
        ? (json_decode(file_get_contents($dataFile), true) ?: [])
        : [];

    if (!is_array($existing) || !array_is_list($existing)) {
        $existing = [];
    }

    // Apply schema to every item in the array
    $existing = array_map(function($item) use ($schemaMap) {
        return is_array($item) ? deepMergeKeepExisting($item, $schemaMap) : $item;
    }, $existing);

    file_put_contents($dataFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    // Redirect back to the collection list view (not an individual item)
    header("Location: admin.php?action=list&target=" . urlencode($target) . "&scaffold_ok=1");
} else {
    // Singleton: load object, deep-merge schema into it
    $existing = file_exists($dataFile)
        ? (json_decode(file_get_contents($dataFile), true) ?: [])
        : [];

    if (!is_array($existing) || array_is_list($existing)) {
        $existing = [];
    }

    $merged = deepMergeKeepExisting($existing, $schemaMap);

    file_put_contents($dataFile, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    header("Location: admin.php?action=edit&target=" . urlencode($target) . "&scaffold_ok=1");
}
exit;
