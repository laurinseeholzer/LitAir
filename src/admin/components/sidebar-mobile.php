<?php
/**
 * Admin Mobile Sidebar Component
 * 
 * Included by: admin-layout.php
 */
?>
<!-- Mobile Sidebar -->
<div x-show="sidebarOpen" class="relative z-50 lg:hidden" role="dialog" aria-modal="true" x-cloak>
    <!-- Backdrop -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition-opacity ease-linear duration-300" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-gray-900/80"></div>

    <div class="fixed inset-0 flex">
        <!-- Sidebar panel -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition ease-in-out duration-300 transform" 
             x-transition:enter-start="-translate-x-full" 
             x-transition:enter-end="translate-x-0" 
             x-transition:leave="transition ease-in-out duration-300 transform" 
             x-transition:leave-start="translate-x-0" 
             x-transition:leave-end="-translate-x-full" 
             class="relative mr-16 flex w-full max-w-xs flex-1">
            
            <div class="absolute top-0 left-full flex w-16 justify-center pt-5">
                <button type="button" @click="sidebarOpen = false" class="-m-2.5 p-2.5">
                    <span class="sr-only">Close sidebar</span>
                    <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-2 dark:bg-gray-900 dark:ring dark:ring-white/10 dark:before:pointer-events-none dark:before:absolute dark:before:inset-0 dark:before:bg-black/10">
                <div class="flex h-16 shrink-0 items-center font-bold text-xl dark:text-white pt-4">
                    Planalite
                </div>
                <!-- Mobile Navigation (Reusing PHP loop) -->
                <nav class="flex flex-1 flex-col">
                    <?php include __DIR__ . '/nav-links.php'; ?>
                </nav>
            </div>
        </div>
    </div>
</div>
