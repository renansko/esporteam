<script setup>
import { computed } from 'vue'
import BottomSheet from './BottomSheet.vue'
import NearbySessionsMap from './NearbySessionsMap.vue'

const props = defineProps({ publication: { type: Object, required: true } })
const emit = defineEmits(['published'])
const draft = computed(() => props.publication.draft.value)
const selectedLocation = computed(() => {
  if (draft.value.latitude === '' || draft.value.longitude === '') return null
  return { latitude: Number(draft.value.latitude), longitude: Number(draft.value.longitude) }
})

function selectLocation(location) {
  draft.value.latitude = location.latitude
  draft.value.longitude = location.longitude
  draft.value.meeting_point_label = 'Ponto selecionado no mapa'
}

async function publish() {
  const session = await props.publication.publishDraft()
  if (session) emit('published', session)
}
</script>

<template>
  <BottomSheet :open="publication.open.value" title="Publicar Sessão Esportiva" @close="publication.close">
    <p class="publication-progress">Escolha o lugar e preencha o essencial</p>
    <section class="publication-fields">
      <div class="publication-map-copy">
        <strong>Onde vai ser?</strong>
        <span>Clique no mapa para marcar o ponto da sessão.</span>
      </div>
      <NearbySessionsMap
        :sessions="[]"
        :selectable="true"
        :selected-location="selectedLocation"
        @location-select="selectLocation"
      />
      <p v-if="selectedLocation" class="publication-selected-location" role="status">Local selecionado ({{ selectedLocation.latitude.toFixed(5) }}, {{ selectedLocation.longitude.toFixed(5) }})</p>
      <p v-else class="publication-field-hint">Toque no mapa para selecionar um local.</p>
      <label v-if="publication.sports.value.length">Modalidade<select v-model="draft.sport_id"><option v-for="sport in publication.sports.value" :key="sport.id" :value="sport.id">{{ sport.name }}</option></select></label>
      <label v-else>ID da modalidade<input v-model.number="draft.sport_id" type="number" min="1" /></label>
      <label>Título<input v-model="draft.title" maxlength="160" /></label>
      <div class="publication-inline-fields">
        <label>Começa em<input v-model="draft.starts_at" type="datetime-local" /></label>
        <label>Termina em<input v-model="draft.ends_at" type="datetime-local" /></label>
      </div>
      <details class="publication-optional">
        <summary>Mais opções</summary>
        <label>Capacidade (opcional)<input v-model="draft.capacity" type="number" min="1" /></label>
        <label>Descrição (opcional)<textarea v-model="draft.description" maxlength="2000" /></label>
        <label>Entrada<select v-model="draft.entry_mode"><option value="publica_direta">Entrada direta</option><option value="publica_aprovacao">Com aprovação</option><option value="convite">Somente convite</option></select></label>
      </details>
      <small>O ponto exato fica protegido e só é mostrado a participantes confirmados.</small>
    </section>
    <p v-if="publication.error.value" class="publication-error" role="alert">{{ publication.error.value }}</p>
    <footer class="publication-actions"><button type="button" :disabled="!publication.canReview.value || publication.loading.value" @click="publish">{{ publication.loading.value ? 'Publicando…' : 'Criar sessão' }}</button></footer>
  </BottomSheet>
</template>

<style scoped>
.publication-progress{color:#64748b;font-size:.875rem}.publication-fields{display:grid;gap:.75rem}.publication-fields label{display:grid;gap:.3rem;font-weight:600}.publication-fields input,.publication-fields select,.publication-fields textarea{border:1px solid #cbd5e1;border-radius:.5rem;padding:.65rem;font:inherit}.publication-fields small,.publication-field-hint{color:#64748b}.publication-map-copy{display:grid;gap:.2rem}.publication-map-copy span{color:#64748b;font-size:.875rem}.publication-fields :deep(.nearby-real-map-canvas){height:230px;border-radius:.75rem}.publication-selected-location{margin:0;color:#0f766e;font-size:.875rem}.publication-inline-fields{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}.publication-optional{display:grid;gap:.75rem;color:#334155}.publication-optional summary{cursor:pointer;font-weight:600}.publication-actions{display:flex;justify-content:flex-end;gap:.5rem;margin-top:1rem}.publication-actions button{border:0;border-radius:.5rem;background:#0f766e;color:#fff;padding:.7rem 1rem;font-weight:700}.publication-actions button:disabled{opacity:.5}.publication-error{color:#b91c1c}
</style>
