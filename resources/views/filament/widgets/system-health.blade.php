<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            System Health
        </x-slot>

        <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full {{ $database_status === 'healthy' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                <span class="text-sm">Database</span>
            </div>
            
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full {{ $cache_status === 'healthy' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                <span class="text-sm">Cache</span>
            </div>
            
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full {{ $queue_status === 'healthy' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                <span class="text-sm">Queue</span>
            </div>
            
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full {{ $storage_usage['used'] < 80 ? 'bg-green-500' : 'bg-yellow-500' }}"></div>
                <span class="text-sm">Storage ({{ $storage_usage['used'] }}% used)</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>