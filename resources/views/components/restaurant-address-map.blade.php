@props([
    'addressInputId' => 'address',
    'lat' => null,
    'lng' => null,
])

@php
    $geocodeUrl = route('owner.geocode');
    $initialLat = old('latitude', $lat);
    $initialLng = old('longitude', $lng);
@endphp

<div
    class="space-y-2"
    x-data="restaurantAddressMap({
        addressInputId: @js($addressInputId),
        geocodeUrl: @js($geocodeUrl),
        csrf: @js(csrf_token()),
        lat: @js($initialLat !== null ? (float) $initialLat : null),
        lng: @js($initialLng !== null ? (float) $initialLng : null),
    })"
    x-init="boot()"
>
    <input type="hidden" name="latitude" x-model="lat">
    <input type="hidden" name="longitude" x-model="lng">

    <div class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            class="inline-flex items-center rounded-full bg-synoria-ink px-3 py-1.5 text-xs font-semibold text-white hover:bg-synoria-ink/90 disabled:opacity-60"
            @click="lookup()"
            :disabled="loading || !canLookup"
        >
            <span x-show="!loading">Placer sur la carte</span>
            <span x-show="loading" x-cloak>Recherche…</span>
        </button>
        <p class="text-xs text-synoria-ink-soft" x-text="status"></p>
    </div>

    <div class="overflow-hidden rounded-xl border border-synoria-yellow/35 bg-slate-100">
        <div x-ref="map" class="h-52 w-full"></div>
    </div>
    <p class="text-xs text-synoria-ink-faint">
        Saisis l’adresse (quartier + ville), puis place le pin. Tu peux aussi glisser le marqueur pour affiner.
    </p>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            function restaurantAddressMap(config) {
                return {
                    lat: config.lat,
                    lng: config.lng,
                    loading: false,
                    status: '',
                    map: null,
                    marker: null,
                    timer: null,
                    get canLookup() {
                        const el = document.getElementById(config.addressInputId);
                        return el && el.value.trim().length >= 3;
                    },
                    boot() {
                        const startLat = this.lat ?? 3.8667;
                        const startLng = this.lng ?? 11.5167;
                        this.map = L.map(this.$refs.map).setView([startLat, startLng], this.lat ? 15 : 12);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap',
                        }).addTo(this.map);

                        if (this.lat && this.lng) {
                            this.setMarker(this.lat, this.lng, false);
                            this.status = 'Position enregistrée';
                        } else {
                            this.status = 'En attente de l’adresse…';
                        }

                        const input = document.getElementById(config.addressInputId);
                        if (input) {
                            input.addEventListener('input', () => {
                                clearTimeout(this.timer);
                                this.timer = setTimeout(() => {
                                    if (this.canLookup) this.lookup(true);
                                }, 900);
                            });
                            input.addEventListener('change', () => {
                                if (this.canLookup) this.lookup(false);
                            });
                        }

                        setTimeout(() => this.map.invalidateSize(), 200);
                    },
                    async lookup(silent) {
                        const input = document.getElementById(config.addressInputId);
                        const address = (input?.value || '').trim();
                        if (address.length < 3) return;
                        this.loading = true;
                        if (!silent) this.status = 'Recherche sur la carte…';
                        try {
                            const res = await fetch(config.geocodeUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': config.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({ address }),
                            });
                            const data = await res.json();
                            if (!res.ok || !data.ok) {
                                this.status = data.message || 'Adresse introuvable';
                                return;
                            }
                            this.lat = data.lat;
                            this.lng = data.lng;
                            this.setMarker(data.lat, data.lng, true);
                            this.status = 'Position trouvée : ' + (data.label || '');
                        } catch (e) {
                            this.status = 'Impossible de joindre le service carte';
                        } finally {
                            this.loading = false;
                        }
                    },
                    setMarker(lat, lng, fly) {
                        if (!this.map) return;
                        if (this.marker) {
                            this.marker.setLatLng([lat, lng]);
                        } else {
                            this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                            this.marker.on('dragend', () => {
                                const p = this.marker.getLatLng();
                                this.lat = Number(p.lat.toFixed(7));
                                this.lng = Number(p.lng.toFixed(7));
                                this.status = 'Position ajustée manuellement';
                            });
                        }
                        if (fly) this.map.flyTo([lat, lng], 15);
                        else this.map.setView([lat, lng], 15);
                    },
                };
            }
        </script>
    @endpush
@endonce
