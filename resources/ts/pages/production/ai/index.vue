<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { can } from '@layouts/plugins/casl'

definePage({
  meta: {
    action: 'report',
    subject: 'Produzione-Performance',
  },
})

const { t } = useI18n()
const d = new Date()
const month = d.toLocaleString('en', { month: 'long' })
const year = d.getFullYear()
const date = ref(`${year} ${month}`)

const loading = ref(false)
const aiAnalysis = ref<any>(null)
const chatMessage = ref('')
const chatHistory = ref<Array<{ role: string, content: string }>>([])
const showChat = ref(false)

const loadAIAnalysis = async () => {
  if (!date.value) return

  loading.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl('/api/production/ai-analysis', {
      query: { periodo: date.value },
    }))

    aiAnalysis.value = resultData.value
  } catch (e) {
    console.error("Errore durante il caricamento dell'analisi AI", e)
  } finally {
    loading.value = false
  }
}

const sendChatMessage = async () => {
  if (!chatMessage.value.trim()) return

  const userMessage = chatMessage.value
  chatHistory.value.push({ role: 'user', content: userMessage })
  chatMessage.value = ''
  loading.value = true

  try {
    const { data: resultData } = await useApi<any>(createUrl('/api/production/ai-chat', {
      method: 'POST',
      body: {
        message: userMessage,
        context: aiAnalysis.value,
        periodo: date.value,
      },
    }))

    chatHistory.value.push({ role: 'assistant', content: resultData.value?.response || 'Nessuna risposta' })
  } catch (e) {
    console.error("Errore durante l'invio del messaggio", e)
    chatHistory.value.push({ role: 'assistant', content: 'Errore durante l\'elaborazione della richiesta.' })
  } finally {
    loading.value = false
  }
}

// Caricamento iniziale
loadAIAnalysis()

watch(() => date.value, () => {
  loadAIAnalysis()
})
</script>

<template>
  <VCol cols="12">
    <VCard
      title=""
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Periodo Riferimento -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppDateTimePicker
              v-model="date"
              :label="$t('Local.Periodo-Riferimento')"
              :placeholder="$t('Local.Periodo-Riferimento')"
              :config="{ shorthand: true, dateFormat: 'Y F', altFormat: 'Y F' }"
            />
          </VCol>
          <VCol
            cols="12"
            sm="9"
            class="d-flex align-center justify-end"
          >
            <VBtn
              color="primary"
              :loading="loading"
              @click="loadAIAnalysis"
            >
              <VIcon icon="tabler-refresh" class="me-2" />
              {{ $t('Label.Aggiorna Analisi') }}
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- AI Insights -->
    <VCard class="mb-6" v-if="aiAnalysis">
      <div class="py-3 px-4 bg-header d-flex align-center gap-2 border-b">
        <VIcon icon="tabler-brain" color="primary" size="20" />
        <span class="text-subtitle-1 font-weight-bold text-high-emphasis">
          {{ $t('Label.AI Insights') }}
        </span>
      </div>
      <VCardText class="pa-4">
        <div v-if="aiAnalysis.insights" class="ai-insights">
          <div v-for="(insight, index) in aiAnalysis.insights" :key="index" class="mb-4">
            <div class="d-flex align-center gap-2 mb-2">
              <VIcon :icon="insight.type === 'warning' ? 'tabler-alert-triangle' : insight.type === 'success' ? 'tabler-check-circle' : 'tabler-info-circle'" 
                :color="insight.type === 'warning' ? 'warning' : insight.type === 'success' ? 'success' : 'info'" size="18" />
              <span class="font-weight-medium">{{ insight.title }}</span>
            </div>
            <p class="text-body-2 mb-0">{{ insight.description }}</p>
          </div>
        </div>
        <VAlert v-else type="info" class="mt-4">
          {{ $t('Label.Nessun insight disponibile') }}
        </VAlert>
      </VCardText>
    </VCard>

    <!-- Chat AI -->
    <VCard>
      <div class="py-3 px-4 bg-header d-flex align-center justify-space-between border-b">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-message" color="primary" size="20" />
          <span class="text-subtitle-1 font-weight-bold text-high-emphasis">
            {{ $t('Label.AI Chat') }}
          </span>
        </div>
        <VBtn
          icon
          variant="text"
          @click="showChat = !showChat"
        >
          <VIcon :icon="showChat ? 'tabler-chevron-up' : 'tabler-chevron-down'" />
        </VBtn>
      </div>
      <VCardText v-if="showChat" class="pa-4">
        <div class="chat-container mb-4" style="max-height: 400px; overflow-y: auto;">
          <div
            v-for="(msg, index) in chatHistory"
            :key="index"
            :class="['mb-3', msg.role === 'user' ? 'text-end' : 'text-start']"
          >
            <VChip
              :color="msg.role === 'user' ? 'primary' : 'secondary'"
              class="pa-3"
              style="max-width: 80%; display: inline-block;"
            >
              {{ msg.content }}
            </VChip>
          </div>
        </div>
        <div class="d-flex gap-2">
          <VTextField
            v-model="chatMessage"
            :label="$t('Label.Chiedi all\'AI')"
            placeholder="Es: Perché lo scrap è aumentato ad agosto?"
            outlined
            dense
            @keyup.enter="sendChatMessage"
          />
          <VBtn
            color="primary"
            :loading="loading"
            :disabled="!chatMessage.trim()"
            @click="sendChatMessage"
          >
            <VIcon icon="tabler-send" />
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <LoadingStandBy v-model="loading" />
  </VCol>
</template>

<style scoped lang="scss">
.ai-insights {
  .mb-4 {
    &:last-child {
      margin-bottom: 0;
    }
  }
}

.chat-container {
  background-color: rgba(var(--v-theme-on-surface), 0.02);
  border-radius: 8px;
  padding: 16px;
}
</style>
