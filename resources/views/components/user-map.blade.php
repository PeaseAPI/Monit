{{--
    用户地理分布地图组件
    - 供应商由后台「网站设置 → 地图」决定：none（内置 SVG 世界地图，离线可用）
      / baidu（百度地图 JS API，国内推荐）/ google（Google Maps JS API）
    - $countries: ['CN' => 12, 'US' => 3, ...] 国家代码 => 用户数（SVG 模式着色）
    - $points:  [['lat'=>39.9,'lng'=>116.4,'label'=>'Beijing','count'=>8], ...] 城市坐标点（瓦片模式打点）
    - $height:  容器高度（默认 420px）
--}}
@php
    $provider = \App\Support\Settings::get('maps.provider') ?? 'none';
    $googleKey = \App\Support\Settings::get('maps.google_key') ?? '';
    $baiduKey = \App\Support\Settings::get('maps.baidu_key') ?? '';
    $mapId = 'user-map-'.uniqid();
    // Key 缺失时回退到内置 SVG 地图，避免空白
    if (($provider === 'google' && ! $googleKey) || ($provider === 'baidu' && ! $baiduKey)) {
        $provider = 'none';
    }
    $height = $height ?? '420px';
@endphp

<div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white">
    <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4">
        <h2 class="flex items-center gap-2 text-base font-semibold text-zinc-900">
            <svg class="h-[18px] w-[18px] text-brand-600" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3M3.5 9h17m-17 6h17"/></svg>
            {{ $title ?? __('admin.users_map_title') }}
        </h2>
        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-500">
            {{ $provider === 'baidu' ? __('admin.maps_provider_baidu') : ($provider === 'google' ? __('admin.maps_provider_google') : __('admin.maps_provider_none')) }}
        </span>
    </div>

    @if ($provider === 'baidu')
        <div id="{{ $mapId }}" style="height: {{ $height }}; width: 100%;"></div>
        <script>
            (function () {
                const points = @json($points ?? []);
                window.initMonitBaiduMap = function () {
                    const map = new BMap.Map('{{ $mapId }}');
                    let center = new BMap.Point(105.403119, 36.982834); // 默认中国中心
                    if (points.length) { center = new BMap.Point(points[0].lng, points[0].lat); }
                    map.centerAndZoom(center, 5);
                    map.enableScrollWheelZoom(true);
                    map.addControl(new BMap.NavigationControl());
                    map.addControl(new BMap.ScaleControl());
                    points.forEach(function (p) {
                        const marker = new BMap.Marker(new BMap.Point(p.lng, p.lat));
                        map.addOverlay(marker);
                        const label = new BMap.Label(p.label + ' · ' + p.count, {
                            offset: new BMap.Size(14, -8),
                        });
                        label.setStyle({ color: '#18181b', border: '1px solid #e4e4e7', padding: '2px 6px', borderRadius: '6px', fontSize: '12px', background: '#fff' });
                        marker.setLabel(label);
                    });
                    if (points.length > 1) {
                        map.setViewport(points.map(function (p) { return new BMap.Point(p.lng, p.lat); }));
                    }
                };
                const s = document.createElement('script');
                s.src = 'https://api.map.baidu.com/api?v=3.0&ak={{ $baiduKey }}&callback=initMonitBaiduMap';
                document.body.appendChild(s);
            })();
        </script>

    @elseif ($provider === 'google')
        <div id="{{ $mapId }}" style="height: {{ $height }}; width: 100%;"></div>
        <script>
            (function () {
                const points = @json($points ?? []);
                window.initMonitGoogleMap = function () {
                    const map = new google.maps.Map(document.getElementById('{{ $mapId }}'), {
                        zoom: 4,
                        center: points.length
                            ? { lat: points[0].lat, lng: points[0].lng }
                            : { lat: 35, lng: 105 },
                    });
                    points.forEach(function (p) {
                        const marker = new google.maps.Marker({
                            position: { lat: p.lat, lng: p.lng },
                            map: map,
                            title: p.label + ' · ' + p.count,
                            label: { text: String(p.count), color: '#fff', fontSize: '11px', fontWeight: '600' },
                        });
                    });
                };
                const s = document.createElement('script');
                s.src = 'https://maps.googleapis.com/maps/api/js?key={{ $googleKey }}&callback=initMonitGoogleMap';
                document.body.appendChild(s);
            })();
        </script>

    @else
        {{-- 内置 SVG 世界地图（svgMap，与原版同款，完全离线无外部依赖）--}}
        <div class="p-4">
            <div id="{{ $mapId }}"></div>
        </div>
        <link href="{{ asset('vendor/svgmap/svgMap.min.css') }}" rel="stylesheet">
        <script src="{{ asset('vendor/svgmap/svgMap.min.js') }}"></script>
        <script>
            (function () {
                new svgMap({
                    targetElementID: '{{ $mapId }}',
                    data: {
                        data: {
                            users: { name: '', format: '{0} {{ __('admin.stat_users') }}', thousandSeparator: ',' },
                        },
                        applyData: 'users',
                        values: @json($countries ?? (object) []),
                    },
                    colorMin: '#dbeafe',
                    colorMax: '#1d4ed8',
                    colorNoData: '#f4f4f5',
                    flagType: 'emoji',
                    noDataText: {{ json_encode(__('admin.no_data')) }},
                });
            })();
        </script>
    @endif
</div>
