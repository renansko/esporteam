<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'
import { changeMapZoom, createLongPressGesture, focusMapSelection } from '../features/participant/mapInteraction.js'

const props = defineProps({
  sessions: { type: Array, default: () => [] },
  selectedSessionId: { type: [String, Number], default: null },
  participantAvatarUrl: { type: String, default: '' },
  participantInitials: { type: String, default: 'PE' },
  selectable: { type: Boolean, default: false },
  selectedLocation: { type: Object, default: null },
})
const emit = defineEmits(['select', 'create-session', 'location-select', 'selection-cancel'])

const mapElement = ref(null)
const mapAriaLabel = computed(() => props.selectable
  ? 'Mapa para escolher o local da Sessão Esportiva'
  : 'Mapa real de Sessões Esportivas próximas. Mantenha pressionado um ponto vazio por meio segundo para criar uma Sessão Esportiva')
const locationStatus = ref(props.selectable
  ? 'Localizando você para escolher o ponto da sessão…'
  : props.sessions.length
    ? 'Localizando você…'
    : 'Nenhuma Sessão Esportiva próxima · localizando você…')
let map
let L
let sessionLayer
let currentLocationMarker
let selectedLocationMarker
let currentLocation = [-27.5949, -48.5482]
let disposed = false
const longPressGesture = createLongPressGesture({
  delay: 500,
  movementTolerance: 10,
  onConfirm: location => {
    emit('create-session', location)
    locationStatus.value = 'Local selecionado. Complete os dados da Sessão Esportiva.'
  },
})

function numberValue(...values) {
  const value = values.find(item => Number.isFinite(Number(item)))
  return value === undefined ? null : Number(value)
}

function sessionCoordinates(session, index) {
  const raw = session.rawCard ?? {}
  const source = raw.session ?? raw
  const location = source.location ?? {}
  const latitude = numberValue(location.latitude_approx, location.latitudeApprox, location.latitude, location.lat, source.latitude_approx, source.latitudeApprox, source.latitude, source.lat, raw.latitude_approx, raw.latitudeApprox, raw.latitude, raw.lat)
  const longitude = numberValue(location.longitude_approx, location.longitudeApprox, location.longitude, location.lng, location.lon, source.longitude_approx, source.longitudeApprox, source.longitude, source.lng, source.lon, raw.longitude_approx, raw.longitudeApprox, raw.longitude, raw.lng, raw.lon)
  if (latitude !== null && longitude !== null) return [latitude, longitude]

  const angle = (index / Math.max(props.sessions.length, 1)) * Math.PI * 2
  const radius = .006 + (index % 3) * .0025
  return [currentLocation[0] + Math.sin(angle) * radius, currentLocation[1] + Math.cos(angle) * radius]
}

function drawCurrentLocation() {
  if (!map) return
  if (currentLocationMarker) currentLocationMarker.remove()

  const avatar = document.createElement('span')
  avatar.className = 'nearby-participant-avatar'
  avatar.textContent = props.participantInitials

  if (props.participantAvatarUrl) {
    const image = document.createElement('img')
    image.src = props.participantAvatarUrl
    image.alt = ''
    image.addEventListener('error', () => image.remove(), { once: true })
    avatar.prepend(image)
  }

  currentLocationMarker = L.marker(currentLocation, {
    icon: L.divIcon({
      className: 'nearby-participant-marker',
      html: avatar,
      iconSize: [46, 46],
      iconAnchor: [23, 23],
    }),
    zIndexOffset: 1000,
  }).addTo(map).bindTooltip('Você está aqui', { direction: 'top', offset: [0, -24] })
}

function drawSessions() {
  if (!map) return
  sessionLayer?.clearLayers()
  sessionLayer = sessionLayer || L.layerGroup().addTo(map)

  const bounds = [currentLocation]
  props.sessions.forEach((session, index) => {
    const coordinates = sessionCoordinates(session, index)
    const selected = String(session.id) === String(props.selectedSessionId)
    bounds.push(coordinates)
    const marker = L.circleMarker(coordinates, {
      radius: selected ? 12 : 10,
      color: '#FFFFFF',
      weight: 3,
      fillColor: selected ? '#2F63C7' : '#916412',
      fillOpacity: 1,
      bubblingMouseEvents: false,
    })
      .bindTooltip(`${session.modalityLabel} · ${session.timeCueLabel}`, { permanent: selected, direction: 'top', offset: [0, -10] })
      .on('click', () => emit('select', session.id))
      .addTo(sessionLayer)
    marker.getElement()?.setAttribute('role', 'button')
    marker.getElement()?.setAttribute('tabindex', '0')
    marker.getElement()?.setAttribute('aria-label', session.listAriaLabel || `${session.title}, ${session.timeCueLabel}`)
    marker.getElement()?.addEventListener('keydown', event => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault()
        emit('select', session.id)
      }
    })
    if (selected) nextTick(() => focusMapSelection(marker))
  })

  if (bounds.length > 1) map.fitBounds(bounds, { padding: [38, 38], maxZoom: 15 })
}

function drawSelectedLocation() {
  if (!map || !props.selectable) return
  selectedLocationMarker?.remove()
  if (!props.selectedLocation) return

  selectedLocationMarker = L.marker([props.selectedLocation.latitude, props.selectedLocation.longitude], {
    icon: L.divIcon({
      className: 'nearby-selected-location-marker',
      html: '<span></span>',
      iconSize: [28, 28],
      iconAnchor: [14, 14],
    }),
  }).addTo(map).bindTooltip('Local da Sessão', { direction: 'top', offset: [0, -12] })
}

function handleMapClick(event) {
  const location = { latitude: event.latlng.lat, longitude: event.latlng.lng }
  if (props.selectable) {
    emit('location-select', location)
    return
  }
}

function clearLongPress() {
  longPressGesture.cancel()
}

function beginLongPress(event) {
  if (props.selectable || event.button > 0 || event.target.closest?.('button, .leaflet-interactive, .leaflet-control')) return
  const latlng = map?.mouseEventToLatLng(event)
  if (latlng) longPressGesture.start(
    { x: event.clientX, y: event.clientY },
    { latitude: latlng.lat, longitude: latlng.lng },
  )
}

function moveLongPress(event) {
  longPressGesture.move({ x: event.clientX, y: event.clientY })
}

function zoom(delta) {
  changeMapZoom(map, delta)
}

function syncSelectable(selectable) {
  if (!map) return
  map.off('click', handleMapClick)
  map.on('click', handleMapClick)
  locationStatus.value = selectable
    ? publicationLocationStatus()
    : props.sessions.length
      ? 'Mostrando Sessões próximas de você'
      : 'Nenhuma Sessão Esportiva próxima · explore o mapa'
  nextTick(() => map?.invalidateSize())
}

function publicationLocationStatus() {
  return props.selectedLocation
    ? 'Sua localização e o local da sessão estão marcados'
    : 'Toque no mapa para marcar o local da sessão'
}

function locateParticipant() {
  if (!navigator.geolocation) {
    locationStatus.value = 'Localização indisponível neste dispositivo'
    drawCurrentLocation()
    drawSessions()
    return
  }

  navigator.geolocation.getCurrentPosition(({ coords }) => {
    currentLocation = [coords.latitude, coords.longitude]
    locationStatus.value = props.selectable
      ? publicationLocationStatus()
      : props.sessions.length
      ? 'Mostrando Sessões próximas de você'
      : 'Nenhuma Sessão Esportiva próxima · explore o mapa'
    if (!map || disposed) return
    map.setView(currentLocation, 14)
    drawCurrentLocation()
    drawSessions()
  }, () => {
    locationStatus.value = props.selectable
      ? publicationLocationStatus()
      : 'Ative a localização para centralizar o mapa em você'
    drawCurrentLocation()
    drawSessions()
  }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 })
}

onMounted(async () => {
  L = (await import('leaflet')).default
  if (disposed || !mapElement.value) return
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
  })
  map = L.map(mapElement.value, { zoomControl: false, attributionControl: true }).setView(currentLocation, 14)
  map.attributionControl.setPrefix(false)
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
    subdomains: 'abcd',
    maxZoom: 20,
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
  }).addTo(map)
  syncSelectable(props.selectable)
  const container = map.getContainer()
  container.addEventListener('pointerdown', beginLongPress)
  container.addEventListener('pointermove', moveLongPress)
  container.addEventListener('pointerup', clearLongPress)
  container.addEventListener('pointercancel', clearLongPress)
  container.addEventListener('pointerleave', clearLongPress)
  drawSelectedLocation()
  locateParticipant()
})

watch(() => [props.sessions, props.selectedSessionId], () => {
  drawSessions()
  if (!props.sessions.length && locationStatus.value.startsWith('Mostrando')) {
    locationStatus.value = 'Nenhuma Sessão Esportiva próxima · explore o mapa'
  }
}, { deep: true })
watch(() => [props.participantAvatarUrl, props.participantInitials], drawCurrentLocation)
watch(() => props.selectable, syncSelectable)
watch(() => props.selectedLocation, () => {
  drawSelectedLocation()
  if (props.selectable) locationStatus.value = publicationLocationStatus()
  nextTick(() => map?.invalidateSize())
}, { deep: true })
onBeforeUnmount(() => {
  disposed = true
  clearLongPress()
  const container = map?.getContainer()
  container?.removeEventListener('pointerdown', beginLongPress)
  container?.removeEventListener('pointermove', moveLongPress)
  container?.removeEventListener('pointerup', clearLongPress)
  container?.removeEventListener('pointercancel', clearLongPress)
  container?.removeEventListener('pointerleave', clearLongPress)
  map?.off('click', handleMapClick)
  selectedLocationMarker?.remove()
  map?.remove()
  map = null
})
</script>

<template>
  <div :class="['nearby-real-map', 'nearby-map', { 'nearby-real-map--selecting': selectable && !selectedLocation }]">
    <div ref="mapElement" class="nearby-real-map-canvas" role="application" :aria-label="mapAriaLabel"></div>
    <div class="nearby-map-zoom glass-surface" aria-label="Controles de zoom">
      <button type="button" aria-label="Aumentar zoom" @click="zoom(1)">+</button>
      <button type="button" aria-label="Diminuir zoom" @click="zoom(-1)">−</button>
    </div>
    <p class="nearby-location-status" aria-live="polite">{{ locationStatus }}</p>
    <button v-if="selectable && !selectedLocation" class="nearby-map-cancel" type="button" @click="emit('selection-cancel')">Cancelar</button>
  </div>
</template>
