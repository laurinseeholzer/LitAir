<?php
/**
 * Admin Edit View
 * 
 * Handles rendering the text editor for a specific Singleton page or Collection item.
 * It determines what item to edit, fetches its JSON from disk, and formats the header title.
 */

// 1. Determine exactly what we are editing 
// If ?slug= exists, it's a specific item inside a collection. Otherwise, it's a singleton page.
$isCollection = isset($_GET['slug']) || (isset($_GET['action']) && $_GET['action'] === 'create');
$target = htmlspecialchars($_GET['target'] ?? '');
$slug = htmlspecialchars($_GET['slug'] ?? '');
$isCreate = isset($_GET['action']) && $_GET['action'] === 'create';

// 2. Resolve a User-Friendly Display Name 
// We scan the $singletons or $collections arrays (provided by admin.php) to find its proper CMS name.
$prettyTargetName = $target;
if (isset($singletons) && !$isCollection) {
    foreach ($singletons as $scene) {
        if ($scene['file'] === $target) {
            $prettyTargetName = $scene['name'];
            break;
        }
    }
} else if (isset($collections) && $isCollection) {
     foreach ($collections as $col) {
        if ($col['collection'] === $target) {
            $prettyTargetName = $col['name'];
            break;
        }
    }
}

// 3. Fetch the Raw JSON Data
$data = [];
$dataFileName = basename($target, '.html');
$dataFile = "data/{$dataFileName}.json";

if (file_exists($dataFile)) {
    // Read and parse the JSON file
    $fileData = json_decode(file_get_contents($dataFile), true);
    
    if ($isCollection && is_array($fileData) && array_is_list($fileData)) {
        if ($isCreate) {
            // If creating a new item, grab the first item in the array to use as a schema template
            if (!empty($fileData)) {
                $templateItem = $fileData[0];
                
                // Recursive function to blank out data while preserving the structure
                $emptySchema = function($array) use (&$emptySchema) {
                    $result = [];
                    foreach ($array as $key => $val) {
                        if (is_array($val)) {
                            // If it's a nested array (like 'featureName' => ['inner' => '...']), recurse
                            $result[$key] = $emptySchema($val);
                        } else if (is_int($val)) {
                            $result[$key] = 0;
                        } else if (is_bool($val)) {
                            $result[$key] = false;
                        } else {
                            $result[$key] = '';
                        }
                    }
                    return $result;
                };
                
                $data = $emptySchema($templateItem);
                
                // Always assure the slug is present at the root level and empty
                $data['slug'] = '';
                
            } else {
                // Collection is entirely empty, provide a fallback basic schema
                $data = ['slug' => 'new-item', 'title' => '', 'description' => ''];
            }
        } else if ($slug) {
            // If editing an existing item, search the array for the exact item by matching the "slug"
            foreach ($fileData as $item) {
                if (isset($item['slug']) && $item['slug'] === $slug) {
                    $data = $item; // Found the item
                    break;
                }
            }
        }
    } else if (!$isCollection) {
        // If it's a singleton page, just load the raw array as-is
        $data = is_array($fileData) ? $fileData : [];
    }
}

// 4. Generate the Final Display Title for the Header
if ($isCollection) {
    if ($isCreate) {
        $itemTitle = "New Item";
    } else {
        // Try to find a human-readable title inside the item's JSON data (title -> name -> slug)
        $itemTitle = $slug ? (isset($data['title']) ? $data['title'] : (isset($data['name']) ? $data['name'] : $slug)) : "New Item";
    }
} else {
    // Fallback to the CMS name defined in the HTML meta tag
    $itemTitle = $prettyTargetName;
}
?>

<div>
    <!-- Header/Breadcrumb Block -->
    <div>
        <!-- Mobile Back Button -->
        <nav aria-label="Back" class="sm:hidden">
            <a href="admin.php<?= $isCollection ? "?action=list&target=" . urlencode($target) : "" ?>" class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg class="mr-1 -ml-1 size-5 shrink-0 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                </svg>
                Back
            </a>
        </nav>

        <nav aria-label="Breadcrumb" class="hidden sm:flex mb-4">
            <ol role="list" class="flex items-center space-x-4">
                <li>
                    <div class="flex">
                        <a href="admin.php" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Admin</a>
                    </div>
                </li>
                <?php if ($isCollection): ?>
                <li>
                    <div class="flex items-center">
                        <svg class="size-5 shrink-0 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                        <a href="admin.php?action=list&target=<?= urlencode($target) ?>" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"><?= htmlspecialchars(ucfirst($target)) ?></a>
                    </div>
                </li>
                <?php endif; ?>
                <li>
                    <div class="flex items-center">
                        <svg class="size-5 shrink-0 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-4 text-sm font-medium text-gray-700 dark:text-gray-200" aria-current="page"><?= $itemTitle ?></span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>
    <div class="mt-2 md:flex md:items-center md:justify-between mb-8 border-b border-gray-200 pb-5 dark:border-white/10">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                <?= $itemTitle ?>
            </h2>
        </div>
        <div class="mt-4 flex shrink-0 md:mt-0 md:ml-4 gap-2 flex-wrap">
            <?php if (!$isCollection): ?>
            <!-- Generate from Template button — singletons only; collections use the list view -->
            <form method="POST" action="admin.php?action=scaffold" onsubmit="return confirm('This will scan the template and add any missing fields to the JSON. Existing data will NOT be changed. Continue?')">
                <input type="hidden" name="target" value="<?= htmlspecialchars($target) ?>">
                <button type="submit" class="inline-flex items-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-white/10 dark:text-white dark:shadow-none dark:ring-white/5 dark:hover:bg-white/20">
                    <svg class="-ml-0.5 size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd" />
                    </svg>
                    Generate from Template
                </button>
            </form>
            <?php endif; ?>
            <a href="admin.php<?= $isCollection ? "?action=list&target=" . urlencode($target) : "" ?>" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-white/10 dark:text-white dark:shadow-none dark:ring-white/5 dark:hover:bg-white/20">
                Cancel
            </a>
            <button type="submit" form="edit-form" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:shadow-none dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500">
                Save Content
            </button>
        </div>
    </div>

    <?php if (isset($_GET['scaffold_ok'])): ?>
    <div class="rounded-md bg-green-50 p-4 mb-6 dark:bg-green-900/30">
        <div class="flex">
            <svg class="size-5 text-green-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Template scaffolded successfully</h3>
                <p class="mt-1 text-sm text-green-700 dark:text-green-300">Missing fields were added from the template. Existing data was not changed. Review the JSON below and save when ready.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['scaffold_error'])): ?>
    <div class="rounded-md bg-yellow-50 p-4 mb-6 dark:bg-yellow-900/30">
        <div class="flex">
            <svg class="size-5 text-yellow-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Could not scaffold</h3>
                <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">The template file for this page could not be found.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Editor Form Area -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl md:col-span-2 dark:bg-gray-800 dark:ring-white/10">
        <form id="edit-form" action="admin.php?action=save" method="POST" class="px-4 py-6 sm:p-8">
            <input type="hidden" name="target" value="<?= htmlspecialchars($target) ?>">
            <?php if ($isCollection): ?>
                <input type="hidden" name="is_collection" value="1">
                <input type="hidden" name="original_slug" value="<?= htmlspecialchars($slug) ?>">
            <?php endif; ?>

            <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                
                <?php if ($isCollection || isset($data['slug'])): ?>
                <div class="sm:col-span-4">
                    <label for="slug" class="block text-sm/6 font-medium text-gray-900 dark:text-white">Slug / Identifier</label>
                    <div class="mt-2">
                        <div class="flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-600 sm:max-w-md dark:ring-white/10 dark:focus-within:ring-indigo-500">
                            <span class="flex select-none items-center pl-3 text-gray-500 sm:text-sm dark:text-gray-400">/<?= $target ?>/</span>
                            <input type="text" name="data[slug]" id="slug" value="<?= htmlspecialchars($data['slug'] ?? $slug) ?>" class="block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm/6 dark:text-white" placeholder="my-awesome-post">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="sm:col-span-full">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">
                        Edit the raw JSON data below to update this content block.
                    </p>
                    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_json'): ?>
                        <div class="rounded-md bg-red-50 p-4 mb-4 dark:bg-red-900/50">
                            <div class="flex">
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Invalid JSON</h3>
                                    <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                        <p>The JSON you entered was malformed. Please fix syntax errors and try again.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <textarea name="raw_json" rows="15" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm/6 font-mono text-xs dark:bg-white/5 dark:text-gray-300 dark:ring-white/10"><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) ?></textarea>
                </div>

            </div>
        </form>
    </div>
</div>
