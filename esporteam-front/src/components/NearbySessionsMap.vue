<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

const props = defineProps({
  sessions: { type: Array, default: () => [] },
  selectedSessionId: { type: [String, Number], default: null },
  participantAvatarUrl: { type: String, default: '' },
  participantInitials: { type: String, default: 'PE' },
})
const emit = defineEmits(['select'])

const mapElement = ref(null)
const locationStatus = ref(props.sessions.length
  ? 'Localizando você…'
  : 'Nenhuma Sessão Esportiva próxima · localizando você…')
let map
let L
let sessionLayer
let currentLocationMarker
let currentLocation = [-27.5949, -48.5482]

function numberValue(...values) {
  const value = values.find(item => Number.isFinite(Number(item)))
  return value === undefined ? null : Number(value)
}

function sessionCoordinates(session, index) {
  const raw = session.rawCard ?? {}
  const source = raw.session ?? raw
  const location = source.location ?? {}
  const latitude = numberValue(location.latitude, location.lat, source.latitude, source.lat, raw.latitude, raw.lat)
  const longitude = numberValue(location.longitude, location.lng, location.lon, source.longitude, source.lng, source.lon, raw.longitude, raw.lng, raw.lon)
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
    L.circleMarker(coordinates, {
      radius: selected ? 12 : 10,
      color: '#FFFFFF',
      weight: 3,
      fillColor: selected ? '#2F63C7' : '#916412',
      fillOpacity: 1,
    })
      .bindTooltip(`${session.modalityLabel} · ${session.timeCueLabel}`, { permanent: selected, direction: 'top', offset: [0, -10] })
      .on('click', () => emit('select', session.id))
      .addTo(sessionLayer)
  })

  if (bounds.length > 1) map.fitBounds(bounds, { padding: [38, 38], maxZoom: 15 })
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
    locationStatus.value = props.sessions.length
      ? 'Mostrando Sessões próximas de você'
      : 'Nenhuma Sessão Esportiva próxima · explore o mapa'
    map.setView(currentLocation, 14)
    drawCurrentLocation()
    drawSessions()
  }, () => {
    locationStatus.value = 'Ative a localização para centralizar o mapa em você'
    drawCurrentLocation()
    drawSessions()
  }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 })
}

onMounted(async () => {
  L = (await import('leaflet')).default
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
  locateParticipant()
})

watch(() => [props.sessions, props.selectedSessionId], () => {
  drawSessions()
  if (!props.sessions.length && locationStatus.value.startsWith('Mostrando')) {
    locationStatus.value = 'Nenhuma Sessão Esportiva próxima · explore o mapa'
  }
}, { deep: true })
watch(() => [props.participantAvatarUrl, props.participantInitials], drawCurrentLocation)
onBeforeUnmount(() => map?.remove())
</script>

<template>
  <div class="nearby-real-map nearby-map">
    <div ref="mapElement" class="nearby-real-map-canvas" aria-label="Mapa real de Sessões Esportivas próximas"></div>
    <p class="nearby-location-status">{{ locationStatus }}</p>
  </div>
</template>
