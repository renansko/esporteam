<script setup>
import { computed } from 'vue'
import UiButton from './ui/UiButton.vue'
import UiFormFooter from './ui/UiFormFooter.vue'
import UiStepper from './ui/UiStepper.vue'

const props = defineProps({ publication: { type: Object, required: true } })
const emit = defineEmits(['published'])
const draft = computed(() => props.publication.draft.value)

async function publish() {
  const session = await props.publication.publishDraft()
  if (session) emit('published', session)
}
</script>

<template>
  <section v-if="publication.open.value && publication.selectedLocation.value" class="publication-panel" aria-label="Criar Sessão Esportiva">
    <header class="publication-header">
      <div>
        <p class="publication-eyebrow">Nova Sessão Esportiva</p>
        <h2>Complete os detalhes</h2>
      </div>
      <button class="publication-close" type="button" @click="publication.close">Fechar</button>
    </header>
    <p class="publication-progress">Escolha o ponto no mapa e preencha o essencial.</p>
    <section class="publication-fields">
      <div class="publication-map-copy">
        <strong>Onde vai ser?</strong>
        <span>Toque no mapa acima para marcar o ponto da sessão.</span>
      </div>
      <p v-if="publication.selectedLocation.value" class="publication-selected-location" role="status">Local da sessão marcado no mapa.</p>
      <p v-else class="publication-field-hint">Toque no mapa para selecionar um local.</p>
      <label v-if="publication.sports.value.length">Modalidade<select v-model="draft.sport_id"><option v-for="sport in publication.sports.value" :key="sport.id" :value="sport.id">{{ sport.name }}</option></select></label>
      <label v-else>Modalidade<select disabled><option>Cadastre uma Modalidade no seu Perfil Esportivo</option></select></label>
      <label>Título<input v-model="draft.title" maxlength="160" /></label>
      <div class="publication-inline-fields">
        <label>Começa em<input v-model="draft.starts_at" type="datetime-local" /></label>
        <label>Termina em<input v-model="draft.ends_at" type="datetime-local" /></label>
      </div>
      <details class="publication-optional">
        <summary>Mais opções</summary>
        <label>Capacidade (opcional)<UiStepper v-model="draft.capacity" :min="1" :max="100" label="Capacidade da Sessão Esportiva" /></label>
        <label>Descrição (opcional)<textarea v-model="draft.description" maxlength="2000" /></label>
        <label>Entrada<select v-model="draft.entry_mode"><option value="publica_direta">Entrada direta</option><option value="publica_aprovacao">Com aprovação</option><option value="convite">Somente convite</option></select></label>
      </details>
      <small>O ponto exato fica protegido e só é mostrado a participantes confirmados.</small>
    </section>
    <p v-if="publication.error.value" class="publication-error" role="alert">{{ publication.error.value }}</p>
    <UiFormFooter class="publication-actions"><UiButton class="publication-submit" variant="primary" :busy="publication.loading.value" :disabled="!publication.canReview.value" @click="publish">{{ publication.loading.value ? 'Publicando…' : 'Criar sessão' }}</UiButton></UiFormFooter>
  </section>
</template>

<style scoped>
.publication-panel{margin:0 -16px -12px;padding:20px 16px 24px;border-top:1px solid var(--cola-ai-line);background:var(--cola-ai-surface);color:var(--cola-ai-text)}
.publication-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}.publication-header h2{margin:.15rem 0 0;font-size:1.2rem}.publication-eyebrow{margin:0;color:var(--cola-ai-action);font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.publication-close{border:1px solid var(--cola-ai-line);border-radius:999px;background:var(--cola-ai-surface);color:var(--cola-ai-action);padding:.45rem .75rem;font:inherit;font-weight:700}.publication-progress{color:var(--cola-ai-text-muted);font-size:.875rem}.publication-fields{display:grid;gap:.85rem}.publication-fields label{display:grid;gap:.35rem;font-weight:700}.publication-fields input,.publication-fields select,.publication-fields textarea{width:100%;border:1px solid var(--cola-ai-line);border-radius:.7rem;background:var(--cola-ai-surface);color:var(--cola-ai-text);padding:.72rem;font:inherit}.publication-fields input:focus,.publication-fields select:focus,.publication-fields textarea:focus{outline:3px solid var(--cola-ai-action-soft);border-color:var(--cola-ai-action)}.publication-fields small,.publication-field-hint,.publication-map-copy span{color:var(--cola-ai-text-muted)}.publication-map-copy{display:grid;gap:.2rem}.publication-map-copy span{font-size:.875rem}.publication-selected-location{margin:0;border-radius:.65rem;padding:.65rem .75rem;background:var(--cola-ai-highlight-soft);color:var(--cola-ai-blue-deep);font-size:.875rem;font-weight:700}.publication-inline-fields{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}.publication-optional{display:grid;gap:.75rem;color:var(--cola-ai-text)}.publication-optional summary{cursor:pointer;color:var(--cola-ai-action);font-weight:700}.publication-actions{display:flex;margin-top:1rem}.publication-submit{width:100%;min-height:48px;border:0;border-radius:.75rem;background:var(--cola-ai-action);color:#fff;padding:.75rem 1rem;font:inherit;font-weight:800;box-shadow:0 8px 20px color-mix(in srgb,var(--cola-ai-action) 24%,transparent)}.publication-submit:hover:not(:disabled){background:var(--cola-ai-blue-deep)}.publication-submit:focus-visible{outline:3px solid var(--cola-ai-action-highlight);outline-offset:2px}.publication-submit:disabled{background:var(--cola-ai-alpine-ice);color:var(--cola-ai-text-muted);box-shadow:none;cursor:not-allowed}.publication-error{color:#b42318}
@media (max-width:520px){.publication-inline-fields{grid-template-columns:1fr}}
</style>
