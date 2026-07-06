<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAppStore } from '../stores/app'
import { STR, pickLang } from '../mock/i18n'
import Icon from './Icon.vue'

const store = useAppStore()
const workspaceName = ref('')

const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)
const profileLabel = computed(() => store.currentUser?.profile || 'user')

onMounted(() => {
  store.refreshWorkspaceOptions()
})

function createWorkspace() {
  const name = workspaceName.value.trim()
  if (!name) return
  store.createAndSelectWorkspace(name)
}
</script>

<template>
  <div class="login-page">
    <section class="login-card workspace-setup">
      <div class="login-logo">
        <div class="mark">P</div>
        <div>
          <div class="nm">Esporteam</div>
          <div class="profile-pill">{{ profileLabel }}</div>
        </div>
      </div>

      <h1>{{ t('workspace_setup_title') }}</h1>
      <p>{{ t('workspace_setup_subtitle') }}</p>

      <form @submit.prevent="createWorkspace">
        <label>{{ t('register_workspace') }}</label>
        <input class="input" type="text" v-model="workspaceName" autocomplete="organization" required />
        <div style="margin-top: 14px">
          <button class="btn btn-primary" type="submit" :disabled="store.workspaceSetupLoading">
            {{ store.workspaceSetupLoading ? '...' : t('workspace_setup_create') }} <Icon name="plus" />
          </button>
        </div>
      </form>

      <div v-if="store.workspaceOptions.length" class="workspace-picker">
        <div class="workspace-picker-title">{{ t('workspace_setup_pick') }}</div>
        <button
          v-for="workspace in store.workspaceOptions"
          :key="workspace.id"
          type="button"
          class="workspace-option"
          :disabled="store.workspaceSetupLoading"
          @click="store.chooseWorkspace(workspace)"
        >
          <span>{{ workspace.name }}</span>
          <Icon name="chevron" :size="13" />
        </button>
      </div>

      <div v-if="store.workspaceSetupError" class="field-error" style="margin-top: 12px">
        {{ store.workspaceSetupError }}
      </div>

      <div class="auth-switch">
        <button type="button" class="link" @click="store.logout()">
          {{ t('workspace_setup_logout') }}
        </button>
      </div>
    </section>
  </div>
</template>
