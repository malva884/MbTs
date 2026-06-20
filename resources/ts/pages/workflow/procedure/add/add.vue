<script lang="ts" setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import type { VForm } from 'vuetify/components/VForm'

definePage({
  meta: {
    action: 'list',
    subject: 'Qualita-Fai',
  },
})

const { t } = useI18n()
const router = useRouter()
const form = ref<VForm>()
const isDialogLoading = ref(false)

const img = ref()
const file = ref<any>(null)
const data = ref<any>({})

const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)

const processiOption = ref<any>([])
const categorieOption = ref<any>([])
const certificatiOption = ref<any>([])
const ufficiOption = ref<any>([])
const revisioni = ['0', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z']

const anniOption = computed(() => {
  const currentYear = new Date().getFullYear() // 2026
  return Array.from({ length: 6 }, (_, i) => String(currentYear - 4 + i))
})

interface Procedure {
  id: string | null
  procedura: string | null
  descrizione: string | null
  revisione: string | null
  revisione_anno: string | null
  categoria_id: string | null
  processo_id: string | null
  user_id: null
  stato: string | null
  padre_id: null
  tipologia: null
  data_prova: string | null
  folder_drive: string | null
  id_file_drive: string | null
  id_log_drive: []
  disable: boolean
  uffici: any[]
  certificati: any[]
  file_upload: any
}

const newItem = ref<Procedure>({
  id: null,
  procedura: null,
  descrizione: null,
  revisione: '0',
  revisione_anno: String(new Date().getFullYear()),
  categoria_id: null,
  processo_id: null,
  user_id: null,
  stato: null,
  padre_id: null,
  tipologia: null,
  data_prova: null,
  folder_drive: null,
  id_file_drive: null,
  id_log_drive: [],
  disable: false,
  uffici: [],
  certificati: [],
  file_upload: null,
})

const loadProcessi = async () => {
  try {
    const resultData = await useApi<any>(createUrl('/workflow/procedure/get_processi'))
    processiOption.value = resultData.data.value || []
  } catch (e) {
    console.error(e)
  }
}

const loadCategoria = async () => {
  try {
    const resultData = await useApi<any>(createUrl('/workflow/procedure/get_categorie'))
    categorieOption.value = resultData.data.value || []
  } catch (e) {
    console.error(e)
  }
}

const loadCertificati = async () => {
  try {
    const resultData = await useApi<any>(createUrl('/workflow/certificazioni/get_list'))
    certificatiOption.value = resultData.data.value || []
  } catch (e) {
    console.error(e)
  }
}

const loadUffici = async () => {
  try {
    const resultData = await useApi<any>(createUrl('/workflow/office/get_list'))
    ufficiOption.value = resultData.data.value || []
  } catch (e) {
    console.error(e)
  }
}

const onSubmit = async () => {
  newItem.value.file_upload = data.value

  const validateResult = await form.value?.validate()

  if (validateResult?.valid && newItem.value.processo_id && newItem.value.procedura && newItem.value.categoria_id && newItem.value.file_upload) {
    isDialogLoading.value = true
    try {
      await $api('/workflow/procedure/stored', {
        method: 'POST',
        body: newItem.value,
      })
      router.push('/workflow/procedure/list')
    } catch (e) {
      console.error(e)
    } finally {
      isDialogLoading.value = false
    }
  }
}

const uploadFile = (event: any) => {
  file.value = event.target.files[0]
  if (!file.value) return

  const reader = new FileReader()
  reader.readAsDataURL(file.value)
  reader.onload = async () => {
    const encodedFile = (reader.result as string).split(',')[1]

    data.value = {
      file: encodedFile,
      fileName: fileName.value,
      fileExtension: fileExtension.value,
      fileMimeType: fileMimeType.value,
    }
  }
}

onMounted(() => {
  loadProcessi()
  loadCategoria()
  loadCertificati()
  loadUffici()
})
</script>

<template>
  <div class="pa-5 elegant-form-view">
    <div class="mb-5 d-flex align-center gap-2 non-scannable-header">
      <VIcon icon="tabler-file-analytics" color="primary" size="26" />
      <div>
        <h2 class="text-h6 font-weight-bold mb-0">Nuova Procedura Principale</h2>
        <p class="text-caption text-disabled mb-0">Configura una nuova procedura master e assegna le relative distribuzioni uffici.</p>
      </div>
    </div>

    <VForm
      ref="form"
      enctype="multipart/form-data"
      lazy-validation
      @submit.prevent="onSubmit"
    >
      <VRow>
        <VCol cols="12">
          <VCard variant="outlined" class="form-section-card">
            <div class="py-2.5 px-4 bg-header d-flex align-center gap-2 border-b">
              <VIcon icon="tabler-hierarchy-3" color="primary" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-high-emphasis">Classificazione & Destinazione</span>
            </div>

            <VCardText class="pa-4">
              <VRow dense>
                <VCol cols="12" sm="6" md="3">
                  <AppSelect
                    v-model="newItem.processo_id"
                    :items="processiOption"
                    item-title="categoria"
                    item-value="id"
                    :rules="[requiredValidator]"
                    :label="t('Label.Processo')"
                    placeholder="Seleziona Processo"
                    prepend-inner-icon="tabler-git-fork"
                  />
                </VCol>

                <VCol cols="12" sm="6" md="3">
                  <AppSelect
                    v-model="newItem.categoria_id"
                    :items="categorieOption"
                    item-title="categoria"
                    item-value="id"
                    :rules="[requiredValidator]"
                    :label="t('Label.Categoria')"
                    placeholder="Seleziona Categoria"
                    prepend-inner-icon="tabler-tags"
                  />
                </VCol>

                <VCol cols="12" sm="6" md="3">
                  <AppSelect
                    v-model="newItem.uffici"
                    :items="ufficiOption"
                    item-title="ufficio"
                    item-value="id"
                    :rules="[requiredValidator]"
                    :label="t('Label.Uffici')"
                    placeholder="Seleziona Uffici"
                    prepend-inner-icon="tabler-building-fortress"
                    multiple
                    chips
                    max-chips="2"
                  />
                </VCol>

                <VCol cols="12" sm="6" md="3">
                  <AppSelect
                    v-model="newItem.certificati"
                    :items="certificatiOption"
                    item-title="certificazione"
                    item-value="id"
                    :rules="[requiredValidator]"
                    :label="t('Label.Certificati')"
                    placeholder="Certificazioni"
                    prepend-inner-icon="tabler-certificate"
                    multiple
                    chips
                    max-chips="2"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12">
          <VCard variant="outlined" class="form-section-card">
            <div class="py-2.5 px-4 bg-header d-flex align-center gap-2 border-b">
              <VIcon icon="tabler-file-description" color="primary" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-high-emphasis">Dettagli del Documento Master</span>
            </div>

            <VCardText class="pa-4">
              <VRow dense>
                <VCol cols="12" sm="6" md="3">
                  <AppTextField
                    v-model="newItem.procedura"
                    :rules="[requiredValidator]"
                    :label="t('Label.Procedura')"
                    placeholder="Codice o Titolo Master"
                    prepend-inner-icon="tabler-hash"
                  />
                </VCol>

                <VCol cols="12" sm="6" md="5">
                  <AppTextField
                    v-model="newItem.descrizione"
                    :label="t('Label.Descrizione')"
                    placeholder="Descrizione sintetica dello scopo"
                    prepend-inner-icon="tabler-align-left"
                  />
                </VCol>

                <VCol cols="12" sm="6" md="2">
                  <AppSelect
                    v-model="newItem.revisione"
                    :items="revisioni"
                    :label="t('Label.Revisione')"
                    placeholder="Rev."
                    prepend-inner-icon="tabler-versions"
                  />
                </VCol>

                <VCol cols="12" sm="6" md="2">
                  <AppSelect
                    v-model="newItem.revisione_anno"
                    :items="anniOption"
                    :rules="[requiredValidator]"
                    :label="t('Label.Anno')"
                    placeholder="Anno"
                    prepend-inner-icon="tabler-calendar-event"
                  />
                </VCol>

                <VCol cols="12" class="mt-2">
                  <div class="text-caption font-weight-medium text-medium-emphasis mb-1">Documento Principale (PDF o Immagine) *</div>
                  <VFileInput
                    v-model="img"
                    accept="image/*,application/pdf"
                    :label="t('Label.File')"
                    :rules="[requiredValidator]"
                    density="comfortable"
                    variant="filled"
                    flat
                    prepend-icon=""
                    prepend-inner-icon="tabler-upload"
                    class="elegant-file-input"
                    @change="uploadFile"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" class="d-flex align-center gap-3 pt-2">
          <VBtn
            color="success"
            type="submit"
            prepend-icon="tabler-device-floppy"
            variant="flat"
            class="px-5 font-weight-bold"
          >
            Salva Procedura
          </VBtn>

          <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-arrow-back"
            @click="router.push('/workflow/procedure/list')"
          >
            Annulla
          </VBtn>
        </VCol>
      </VRow>
    </VForm>

    <VDialog v-model="isDialogLoading" persistent width="280">
      <VCard color="primary" class="pa-4 text-center">
        <div class="text-body-2 font-weight-bold text-white mb-2">Salvataggio in corso...</div>
        <VProgressLinear indeterminate color="white" height="3" class="mb-0" />
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped lang="scss">
.elegant-form-view {
  .gap-2 { gap: 8px; }
  .gap-3 { gap: 12px; }

  .border-b {
    border-bottom: 1px solid rgba(var(--v-border-color), 0.08) !important;
  }

  .bg-header {
    background-color: rgba(var(--v-theme-on-surface), 0.015);
  }

  .form-section-card {
    border-radius: 8px;
    background-color: rgb(var(--v-theme-surface));
    transition: border-color 0.2s ease;

    &:focus-within {
      border-color: rgba(var(--v-theme-primary), 0.4) !important;
    }
  }

  // File Input stilizzato minimale
  :deep(.elegant-file-input) {
    .v-field {
      border-radius: 8px !important;
      border: 1px dashed rgba(var(--v-border-color), 0.3) !important;
      background-color: rgba(var(--v-theme-on-surface), 0.01) !important;
      transition: all 0.2s ease;

      &:hover {
        background-color: rgba(var(--v-theme-on-surface), 0.03) !important;
        border-color: rgba(var(--v-theme-primary), 0.4) !important;
      }
    }
    .v-field__input {
      min-height: 44px !important;
      align-items: center;
    }
  }
}
</style>
