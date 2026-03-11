<?php
/**
 * Master Admin Layout
 * 
 * This file serves as the outer HTML shell for the entire Admin Panel.
 * It includes the sidebar (both mobile and desktop versions), loads TailwindCSS,
 * and dynamically injects the current page's view into the <main> block based on $adminContentTemplate.
 */
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-white dark:bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planalite Admin</title>
    <!-- Tailwind CSS (CDN for POC) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js handles the mobile sidebar open/close state logic -->
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">

<?php include __DIR__ . '/sidebar-mobile.php'; ?>
<?php include __DIR__ . '/sidebar-desktop.php'; ?>
<?php include __DIR__ . '/topbar.php'; ?>

<!-- Main Content Area -->
<main class="py-10 lg:pl-72 dark:bg-gray-900">
    <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <?php if (isset($adminContentTemplate)) { include escapeshellcmd($adminContentTemplate); } ?>
    </div>
</main>

</body>
</html>
