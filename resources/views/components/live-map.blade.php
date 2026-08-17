@props([
    'order',
    'role' => 'customer',
])

@php
    $live = $order->isLiveTrackingActive();
    $trackingUrl = route('orders.tracking', $order);
    $locationUrl = $role === 'courier'
        ? route('courier.missions.location', $order)
        : route('orders.location', $order);
@endphp

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

<div class="synoria-panel sm:rounded-2xl overflow-hidden">
    <div class="px-6 pt-5 pb-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h3 class="font-semibold text-synoria-ink text-lg">Suivi sur la carte</h3>
            <p class="text-sm text-synoria-ink-soft mt-1" data-live-map-status>
                @if ($live)
                    Suivi actif pendant la livraison (mise à jour toutes les 4 s).
                @else
                    Le suivi GPS n’est actif qu’entre « en livraison » et « livrée ».
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                Restaurant
            </span>
            <span class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700">
                <span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                Client
            </span>
            <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                Livreur
            </span>
            @if ($live)
                <button type="button"
                        data-live-map-share
                        class="shrink-0 inline-flex items-center px-3 py-1.5 rounded-full bg-synoria-yellow text-synoria-ink text-sm font-medium hover:bg-synoria-yellow-deep shadow-sm">
                    Partager ma position
                </button>
            @endif
        </div>
    </div>
    <div class="px-6 pb-6">
        <div class="relative overflow-hidden rounded-2xl border border-synoria-yellow/35 bg-white shadow-inner">
            <div class="pointer-events-none absolute inset-x-0 top-0 z-[400] flex justify-between bg-gradient-to-r from-white/90 via-white/60 to-white/90 px-4 py-3 text-xs text-synoria-ink-soft">
                <span>Position client et livreur actualisee automatiquement</span>
                <span class="font-medium">Ligne bleue = trajet en cours</span>
            </div>
            <div data-live-map
                 class="h-80 w-full bg-slate-100"
                 data-tracking-url="{{ $trackingUrl }}"
                 data-location-url="{{ $locationUrl }}"
                 data-role="{{ $role }}"
                 data-live="{{ $live ? '1' : '0' }}"
                 data-restaurant-name="{{ $order->restaurant->name }}"
                 data-restaurant-lat="{{ $order->restaurant->latitude }}"
                 data-restaurant-lng="{{ $order->restaurant->longitude }}"
                 data-courier-lat="{{ $live ? $order->courier_lat : '' }}"
                 data-courier-lng="{{ $live ? $order->courier_lng : '' }}"
                 data-customer-lat="{{ $live ? ($order->customer_lat ?? $order->delivery_lat) : $order->delivery_lat }}"
                 data-customer-lng="{{ $live ? ($order->customer_lng ?? $order->delivery_lng) : $order->delivery_lng }}">
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            const el = document.querySelector('[data-live-map]');
            if (!el || typeof L === 'undefined') return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const statusEl = document.querySelector('[data-live-map-status]');
            const shareBtn = document.querySelector('[data-live-map-share]');

            const num = (v) => {
                const n = parseFloat(v);
                return Number.isFinite(n) ? n : null;
            };

            const restoLat = num(el.dataset.restaurantLat);
            const restoLng = num(el.dataset.restaurantLng);
            const startLat = num(el.dataset.customerLat) ?? restoLat ?? 3.8667;
            const startLng = num(el.dataset.customerLng) ?? restoLng ?? 11.5167;

            const map = L.map(el, { zoomControl: true }).setView([startLat, startLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(map);

            const icon = (color) => L.divIcon({
                className: '',
                html: `<span style="display:block;width:18px;height:18px;border-radius:9999px;background:${color};border:3px solid #fff;box-shadow:0 6px 18px rgba(0,0,0,.25), 0 0 0 2px rgba(29,36,43,.25)"></span>`,
                iconSize: [18, 18],
                iconAnchor: [9, 9],
            });

            let restoMarker = (restoLat && restoLng)
                ? L.marker([restoLat, restoLng], { icon: icon('#63A03A') }).addTo(map).bindPopup(el.dataset.restaurantName || 'Restaurant')
                : null;
            let courierMarker = null;
            let customerMarker = null;
            let routeHalo = null;
            let routeLine = null;

            const setMarker = (current, lat, lng, color, label) => {
                if (lat == null || lng == null) {
                    if (current) map.removeLayer(current);
                    return null;
                }
                if (!current) {
                    return L.marker([lat, lng], { icon: icon(color) }).addTo(map).bindPopup(label);
                }
                current.setLatLng([lat, lng]);
                return current;
            };

            const fit = () => {
                const pts = [];
                if (restoMarker) pts.push(restoMarker.getLatLng());
                if (courierMarker) pts.push(courierMarker.getLatLng());
                if (customerMarker) pts.push(customerMarker.getLatLng());
                if (pts.length >= 2) map.fitBounds(L.latLngBounds(pts).pad(0.25));
            };

            const setRoute = () => {
                const courier = courierMarker?.getLatLng();
                const customer = customerMarker?.getLatLng();

                if (!courier || !customer) {
                    if (routeHalo) map.removeLayer(routeHalo);
                    if (routeLine) map.removeLayer(routeLine);
                    routeHalo = null;
                    routeLine = null;
                    return;
                }

                const points = [courier, customer];
                if (!routeHalo) {
                    routeHalo = L.polyline(points, {
                        color: '#34d399',
                        weight: 10,
                        opacity: 0.25,
                        lineCap: 'round',
                    }).addTo(map);
                } else {
                    routeHalo.setLatLngs(points);
                }

                if (!routeLine) {
                    routeLine = L.polyline(points, {
                        color: '#0ea5e9',
                        weight: 4,
                        opacity: 0.95,
                        dashArray: '10 8',
                        lineCap: 'round',
                    }).addTo(map);
                } else {
                    routeLine.setLatLngs(points);
                }
            };

            const apply = (data) => {
                const live = !!data.sharing_active;
                el.dataset.live = live ? '1' : '0';
                if (statusEl) {
                    statusEl.textContent = live
                        ? 'Suivi actif pendant la livraison (mise à jour toutes les 4 s).'
                        : 'Suivi GPS coupé: la commande n’est plus en livraison.';
                }
                if (!live) {
                    if (shareBtn) shareBtn.classList.add('hidden');
                    courierMarker = setMarker(courierMarker, null, null);
                    customerMarker = setMarker(customerMarker, num(el.dataset.customerLat), num(el.dataset.customerLng), '#1D242B', 'Livraison');
                    setRoute();
                    fit();
                    return live;
                }
                courierMarker = setMarker(courierMarker, num(data.courier_lat), num(data.courier_lng), '#FCD530', 'Livreur');
                customerMarker = setMarker(customerMarker, num(data.customer_lat), num(data.customer_lng), '#0EA5E9', 'Client');
                setRoute();
                fit();
                return live;
            };

            apply({
                sharing_active: el.dataset.live === '1',
                courier_lat: el.dataset.courierLat,
                courier_lng: el.dataset.courierLng,
                customer_lat: el.dataset.customerLat,
                customer_lng: el.dataset.customerLng,
            });

            let watchId = null;
            const sendLocation = (lat, lng) => {
                fetch(el.dataset.locationUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ lat, lng }),
                });
            };

            const startWatch = () => {
                if (!navigator.geolocation || watchId !== null) return;
                watchId = navigator.geolocation.watchPosition((pos) => {
                    if (el.dataset.live !== '1') return;
                    sendLocation(pos.coords.latitude, pos.coords.longitude);
                }, () => {
                    if (statusEl) statusEl.textContent = 'Impossible d’obtenir ta position. Autorise la géoloc.';
                }, { enableHighAccuracy: true, maximumAge: 4000 });
            };

            shareBtn?.addEventListener('click', startWatch);
            if (el.dataset.role === 'courier' && el.dataset.live === '1') {
                startWatch();
            }

            let timer = null;
            const poll = async () => {
                try {
                    const res = await fetch(el.dataset.trackingUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    const stillLive = apply(data);
                    const label = document.getElementById('status-label');
                    if (label && data.status_label) label.textContent = data.status_label;
                    if (!stillLive || ['delivered', 'cancelled'].includes(data.status)) {
                        if (timer) clearInterval(timer);
                        if (watchId !== null && navigator.geolocation) {
                            navigator.geolocation.clearWatch(watchId);
                            watchId = null;
                        }
                        if (['delivered', 'cancelled'].includes(data.status)) {
                            setTimeout(() => window.location.reload(), 800);
                        }
                    }
                } catch (e) {}
            };

            if (el.dataset.live === '1') {
                timer = setInterval(poll, 4000);
                poll();
            }
        })();
    </script>
@endpush
