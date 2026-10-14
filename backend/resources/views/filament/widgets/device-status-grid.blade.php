<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach ($getDevices() as $device)
        <a href="{{ $device['edit_url'] }}" class="block">
            <div class="bg-white rounded-lg shadow p-5 hover:shadow-md transition cursor-pointer border-l-4 {{ $device['is_online'] ? 'border-green-500' : 'border-red-500' }}">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900 truncate">{{ $device['name'] }}</h3>
                    <span class="inline-flex items-center gap-1.5 text-sm font-medium {{ $device['is_online'] ? 'text-green-600' : 'text-red-600' }}">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-{{ $device['is_online'] ? 'green-400' : 'red-400' }} opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-{{ $device['is_online'] ? 'green-500' : 'red-500' }}"></span>
                        </span>
                        {{ $device['is_online'] ? 'Online' : 'Offline' }}
                    </span>
                </div>

                <div class="space-y-1 text-sm text-gray-600">
                    <div class="flex justify-between">
                        <span>Device ID</span>
                        <span class="font-mono font-medium">{{ $device['device_id'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Status</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            @if($device['status'] === 'active')
                                bg-green-100 text-green-800
                            @elseif($device['status'] === 'maintenance')
                                bg-yellow-100 text-yellow-800
                            @else
                                bg-red-100 text-red-800
                            @endif
                        ">
                            {{ ucfirst($device['status']) }}
                        </span>
                    </div>
                    @if($device['kasir_name'])
                        <div class="flex justify-between">
                            <span>Kasir</span>
                            <span class="font-medium">{{ $device['kasir_name'] }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between border-t pt-2">
                        <span>Last Seen</span>
                        <span>{{ $device['last_seen_at'] ? $device['last_seen_at']->format('d/m/Y H:i') : 'Never' }}</span>
                    </div>
                </div>
            </div>
        </a>
    @endforeach

    @if(empty($getDevices()))
        <div class="col-span-full text-center py-12 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
            <p class="mt-2">Belum ada device terdaftar</p>
        </div>
    @endif
</div>