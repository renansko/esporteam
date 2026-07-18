<script setup>
import { computed } from 'vue'
import BottomSheet from './BottomSheet.vue'

const props = defineProps({ publication: { type: Object, required: true } })
const emit = defineEmits(['published'])
const labels = ['Localização', 'Detalhes', 'Participação', 'Revisão']
const draft = computed(() => props.publication.draft.value)

function next() { if (props.publication.step.value < 3) props.publication.step.value += 1 }
function previous() { if (props.publication.step.value > 0) props.publication.step.value -= 1 }
async function publish() {
  const session = await props.publication.publishDraft()
  if (session) emit('published', session)
}
</script>

<template>
  <BottomSheet :open="publication.open.value" title="Publicar Sessão Esportiva" @close="publication.close">
    <p class="publication-progress">{{ labels[publication.step.value] }} · {{ publication.step.value + 1 }}/4</p>
    <section v-if="publication.step.value === 0" class="publication-fields">
      <label>Ponto de encontro<input v-model="draft.meeting_point_label" placeholder="Ex.: Portão 3" /></label>
      <label>Área pública<input v-model="draft.location_label_public" placeholder="Ex.: Parque Ibirapuera" /></label>
      <label>Cidade<input v-model="draft.city" /></label><label>Região<input v-model="draft.region" /></label>
      <label>Latitude<input v-model.number="draft.latitude" type="number" step="any" /></label><label>Longitude<input v-model.number="draft.longitude" type="number" step="any" /></label>
      <small>O ponto exato só será mostrado a participantes confirmados. A Descoberta usa uma área aproximada.</small>
    </section>
    <section v-else-if="publication.step.value === 1" class="publication-fields">
      <label>ID da modalidade<input v-model.number="draft.sport_id" type="number" min="1" /></label>
      <label>Título<input v-model="draft.title" maxlength="160" /></label>
      <label>Tipo<select v-model="draft.type"><option value="partida">Partida</option><option value="treino">Treino</option><option value="corrida">Corrida</option><option value="aula">Aula aberta</option><option value="encontro">Encontro</option></select></label>
      <label>Começa em<input v-model="draft.starts_at" type="datetime-local" /></label><label>Termina em<input v-model="draft.ends_at" type="datetime-local" /></label>
      <label>Fuso IANA<input v-model="draft.timezone" /></label><label>Capacidade (opcional)<input v-model="draft.capacity" type="number" min="1" /></label>
      <label>Descrição (opcional)<textarea v-model="draft.description" maxlength="2000" /></label>
    </section>
    <section v-else-if="publication.step.value === 2" class="publication-fields">
      <label>Entrada<select v-model="draft.entry_mode"><option value="publica_direta">Direta</option><option value="publica_aprovacao">Com aprovação</option><option value="convite">Somente convite</option></select></label>
      <label>Visibilidade<select v-model="draft.visibility"><option value="public">Pública</option><option value="private">Privada</option></select></label>
      <p>Participação direta confirma na hora; por aprovação mantém o pedido sob decisão do Anfitrião da Sessão.</p>
    </section>
    <section v-else class="publication-review">
      <strong>{{ draft.title || 'Sessão sem título' }}</strong><p>{{ draft.location_label_public }} · {{ draft.city }} — {{ draft.region }}</p>
      <p>{{ draft.starts_at }} até {{ draft.ends_at }} ({{ draft.timezone }})</p><p>{{ draft.entry_mode === 'publica_direta' ? 'Entrada direta' : 'Entrada curada' }}</p>
      <small>O ponto exato “{{ draft.meeting_point_label }}” não aparece publicamente.</small>
    </section>
    <p v-if="publication.error.value" class="publication-error" role="alert">{{ publication.error.value }}</p>
    <footer class="publication-actions"><button v-if="publication.step.value" type="button" @click="previous">Voltar</button><button v-if="publication.step.value < 3" type="button" @click="next">Continuar</button><button v-else type="button" :disabled="!publication.canReview.value || publication.loading.value" @click="publish">{{ publication.loading.value ? 'Publicando…' : 'Publicar' }}</button></footer>
  </BottomSheet>
</template>

<style scoped>
.publication-progress{color:#64748b;font-size:.875rem}.publication-fields{display:grid;gap:.75rem}.publication-fields label{display:grid;gap:.3rem;font-weight:600}.publication-fields input,.publication-fields select,.publication-fields textarea{border:1px solid #cbd5e1;border-radius:.5rem;padding:.65rem;font:inherit}.publication-fields small,.publication-review small{color:#64748b}.publication-review{display:grid;gap:.5rem}.publication-actions{display:flex;justify-content:space-between;gap:.5rem;margin-top:1rem}.publication-actions button{border:0;border-radius:.5rem;background:#0f766e;color:#fff;padding:.7rem 1rem;font-weight:700}.publication-error{color:#b91c1c}
</style>
