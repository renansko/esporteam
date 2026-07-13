<script setup>
import { computed, watch } from 'vue'
import { useBioAssistantFlow } from '../composables/useBioAssistantFlow.js'
import BottomSheet from './BottomSheet.vue'
import Icon from './Icon.vue'

const props = defineProps({
  draft: { type: Object, required: true },
  profile: { type: Object, default: null },
})
const emit = defineEmits(['missing-context', 'accepted', 'edit'])

const flow = useBioAssistantFlow()
const hasSportContext = computed(() => props.draft.sports.some(practice => practice.sport_id || practice.name))
const contextSummary = computed(() => props.draft.sports
  .filter(practice => practice.name)
  .map((practice) => {
    const details = [practice.name, practice.level, practice.goals?.filter(Boolean).join(', ')].filter(Boolean)
    return details.join(' · ')
  }))

watch(() => props.profile, profile => flow.evaluate(profile), { immediate: true, deep: true })

async function open() {
  await flow.openManual()
}

function close() {
  flow.close()
}

function dismissForSession() {
  flow.dismissForSession()
}

async function generate() {
  if (!hasSportContext.value) {
    emit('missing-context')
    return
  }
  const result = await flow.generate({ hasSportContext: hasSportContext.value })
  if (result.reason === 'missing_context') emit('missing-context')
}

function copySuggestionToEditor() {
  if (!flow.wizard.suggestion.value?.bio) return
  emit('edit', flow.wizard.suggestion.value)
  close()
}

async function acceptSuggestion() {
  const accepted = await flow.accept()
  if (!accepted?.bio) return
  emit('accepted', accepted)
  close()
}
</script>

<template>
  <div class="profile-bio-assistant">
    <button type="button" class="profile-bio-assistant-trigger" @click="open">
      <Icon name="sparkles" :size="16" />
      <span>Bio Assistida</span>
    </button>

    <BottomSheet :open="flow.onboarding.open.value" title="Bio Assistida" @close="close">
      <div class="bio-chat">
        <section v-if="flow.onboarding.automatic.value" class="bio-wizard-invitation" aria-label="Convite para Bio Assistida">
          <strong>Vamos criar sua bio?</strong>
          <p>Usaremos as Modalidades e preferências do seu Perfil Esportivo para criar uma sugestão privada. Você revisa tudo antes de publicar.</p>
          <button type="button" class="bio-wizard-secondary" @click="dismissForSession">Agora não</button>
        </section>
        <div class="bio-wizard-context">
          <Icon name="sparkles" :size="17" />
          <div>
            <strong>Como posso ajudar com sua bio?</strong>
            <p v-if="contextSummary.length">{{ contextSummary.join(' · ') }}</p>
            <p v-else>Inclua ao menos uma Modalidade salva para eu criar uma sugestão.</p>
          </div>
        </div>

        <article v-if="flow.wizard.suggestion.value && flow.wizard.suggestion.value.status !== 'failed'" class="bio-wizard-draft">
          <span>Assistente IA</span>
          <p>{{ flow.wizard.suggestion.value.bio }}</p>
          <ul v-if="flow.wizard.suggestion.value.key_points?.length">
            <li v-for="point in flow.wizard.suggestion.value.key_points" :key="point">{{ point }}</li>
          </ul>
          <div class="bio-wizard-actions">
            <button type="button" class="bio-wizard-primary" :disabled="flow.wizard.accepting.value || flow.wizard.suggestion.value.status === 'accepted'" @click="acceptSuggestion"><Icon name="check" :size="16" /> {{ flow.wizard.suggestion.value.status === 'accepted' ? 'Bio aceita' : flow.wizard.accepting.value ? 'Aceitando…' : 'Aceitar bio' }}</button>
            <button v-if="flow.wizard.suggestion.value.status !== 'accepted'" type="button" class="bio-wizard-secondary" @click="copySuggestionToEditor">Editar antes de salvar</button>
          </div>
        </article>

        <section v-if="flow.wizard.suggestions.value.length > 1 || flow.wizard.hasMoreSuggestions.value" class="bio-wizard-history" aria-label="Rascunhos anteriores de bio">
          <span>Rascunhos anteriores</span>
          <button
            v-for="item in flow.wizard.suggestions.value"
            :key="item.id"
            type="button"
            :class="{ active: flow.wizard.suggestion.value?.id === item.id }"
            @click="flow.wizard.selectSuggestion(item.id)"
          >
            <strong>{{ item.status === 'accepted' ? 'Bio aceita' : 'Rascunho' }}</strong>
            <small>{{ item.bio }}</small>
          </button>
          <button v-if="flow.wizard.hasMoreSuggestions.value" type="button" class="bio-wizard-secondary" :disabled="flow.wizard.loadingSuggestions.value" @click="flow.wizard.loadNextSuggestions()">
            {{ flow.wizard.loadingSuggestions.value ? 'Carregando…' : 'Carregar sugestões anteriores' }}
          </button>
        </section>

        <div class="bio-chat-composer">
          <label class="bio-wizard-field">
            <span>Sua mensagem <em>(opcional)</em></span>
            <textarea v-model="flow.wizard.message.value" maxlength="500" rows="2" placeholder="Ex.: Me ajude a deixar minha bio mais curta."></textarea>
          </label>
          <p v-if="flow.wizard.error.value" class="bio-wizard-error" role="alert">{{ flow.wizard.error.value }}</p>
          <button type="button" class="bio-wizard-primary" :disabled="flow.wizard.loading.value || flow.wizard.retryAfter.value || flow.onboarding.onboarding.value.blockingFields.length || !hasSportContext" @click="generate">
            <Icon name="sparkles" :size="16" /> {{ flow.wizard.loading.value ? 'Criando…' : 'Criar minha bio' }}
          </button>
          <p v-if="!hasSportContext" class="bio-wizard-error" role="status">Adicione ao menos uma Modalidade salva antes de criar sua bio.</p>
        </div>
      </div>
    </BottomSheet>
  </div>
</template>
