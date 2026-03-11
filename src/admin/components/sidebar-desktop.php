<?php
/**
 * Admin Desktop Sidebar Component
 * 
 * Included by: admin-layout.php
 */
?>
<!-- Static sidebar for desktop -->
<div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col dark:bg-gray-900">
    <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6 dark:border-white/10 dark:bg-black/10">
        <div class="flex h-16 shrink-0 items-center font-bold text-xl dark:text-white pt-4">
            Planalite
        </div>
        <nav class="flex flex-1 flex-col">
            <!-- Desktop Navigation -->
            <?php include __DIR__ . '/nav-links.php'; ?>
        </nav>
    </div>
</div>
