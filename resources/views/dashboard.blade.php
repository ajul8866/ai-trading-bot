<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Trading Dashboard') }}
            </h2>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Last updated: <span class="font-medium">{{ now()->format('Y-m-d H:i:s') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Main Dashboard Component -->
            <livewire:dashboard />
        </div>
    </div>
</x-app-layout>
