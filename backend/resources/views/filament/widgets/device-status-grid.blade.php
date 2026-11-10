<x-filament-widgets::widget>
    <x-filament::section
        :heading="$this->getHeading()"
        :description="$this->getDescription()"
    >
        <div class="nb-device-grid">
            @foreach ($this->devices as $device)
                @php($tag = $device['edit_url'] ? 'a' : 'div')
                <{{ $tag }} @if ($device['edit_url']) href="{{ $device['edit_url'] }}" @endif class="nb-device">
                    <div class="nb-device-head">
                        <h3 class="nb-device-name">{{ $device['name'] }}</h3>
                        <span class="nb-chip {{ $device['is_online'] ? 'nb-chip-green' : 'nb-chip-red' }}">
                            <span class="nb-dot"></span>
                            {{ $device['is_online'] ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                    <dl>
                        <div class="nb-kv">
                            <dt>Device ID</dt>
                            <dd>{{ $device['device_id'] }}</dd>
                        </div>
                        <div class="nb-kv">
                            <dt>Status</dt>
                            <dd>
                                @switch($device['status'])
                                    @case('active')
                                        <span class="nb-chip nb-chip-green">Active</span>
                                        @break
                                    @case('maintenance')
                                        <span class="nb-chip" style="background: var(--nb-yellow); color: #0b0b0b;">Maintenance</span>
                                        @break
                                    @default
                                        <span class="nb-chip nb-chip-red">{{ ucfirst($device['status']) }}</span>
                                @endswitch
                            </dd>
                        </div>
                        @if ($device['kasir_name'])
                            <div class="nb-kv">
                                <dt>Kasir</dt>
                                <dd style="font-family: var(--nb-ui);">{{ $device['kasir_name'] }}</dd>
                            </div>
                        @endif
                        <div class="nb-kv">
                            <dt>Last Seen</dt>
                            <dd>{{ $device['last_seen_at'] ?? 'Never' }}</dd>
                        </div>
                    </dl>
                </{{ $tag }}>
            @endforeach
            @if (empty($this->devices))
                <div class="nb-empty">Belum ada device terdaftar</div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>