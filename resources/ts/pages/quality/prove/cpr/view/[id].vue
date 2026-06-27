<script setup lang="ts">

import {useI18n} from "vue-i18n";
import {VDataTableServer} from "vuetify/labs/VDataTable";
import {can} from "@layouts/plugins/casl";
import DefineAbilities from "@/plugins/casl/DefineAbilities";
import {VForm} from "vuetify/components/VForm";

definePage({
  meta: {
    action: 'read',
    subject: 'Qualita-Prove-Tipo',
  },
})

interface ProvaTipo {
  files_upload: []
}

const newItem = ref<ProvaTipo>({})

const {t} = useI18n()
const route = useRoute('quality-prove-tipo-view-id')
const loading = ref(true)
const isDialogLoading = ref(false)
const prova = ref<any>([])
const serverItems = ref<any>([])
const itemsPerPage = ref(-1)
const file = ref(null)
const data = ref([])
const uploadDialog = ref(false)
const isFormValid = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const color = ref()
const message = ref()

const headers = computed(() => [
  {title: t('Table.Ordine'), key: 'ol', sortable: false},
  {title: t('Table.Materiale'), key: 'materiale', sortable: false},
  {title: t('Table.Esito'), key: 'esito', sortable: false},
  {title: t('Table.Standard'), key: 'standard', sortable: false},
  {title: t('Table.Specifica'), key: 'spcifica', sortable: false},
  {title: t('Table.Tipologia'), key: 'categoria', sortable: false},
  {title: t('Table.Data'), key: 'data_prova', sortable: false},
])

const loadItem = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/qt/prove_cpr/view/${route.params.id}`))

  prova.value = resultData.value

  const { data: proveData, error } = await useApi<any>(createUrl(`/qt/prove_cpr/get_prove/${prova.value.ol}`))

  serverItems.value = proveData.value
  loading.value = false

}

const reloadItem = async (id: string) => {
  isDialogLoading.value = true
  const { data: resultData } = await useApi<any>(createUrl(`/qt/prove_cpr/view/${id}`))

  prova.value = resultData.value

  isDialogLoading.value = false
}

const resolveStatusVariant = (risultato: string) => {
  if (risultato === 'POSITIVO')
    return { color: 'success', text: 'POSITIVO' }
  else if (risultato === 'NEGATIVO')
    return { color: 'error', text: 'NEGATIVO' }
  else
    return { color: '', text: risultato }
}

const onSubmit = async (id: string) => {
  newItem.value.files_upload = data.value
  if (newItem.value.files_upload) {
    isDialogLoading.value = true
    const retuenData = await $api(`/qt/prove_cpr/upload/${id}`, {
      method: 'POST',
      body: newItem.value,
    })

    isDialogLoading.value = false
    uploadDialog.value = false
    color.value = retuenData.color
    message.value = retuenData.message
    isSnackbarScrollReverseVisible.value = true
  }
}

const uploadFile = (event: any) => {

  for (let i = 0; i < event.target.files.length; i++) {
    file.value = event.target.files[i]
    let nameFile = file.value.name
    let ext = file.value.name?.substr(file.value.name?.lastIndexOf('.') + 1)
    let mimeTipe = file.value.type

    const reader = new FileReader()

    reader.readAsDataURL(file.value)
    reader.onload = async () => {
      const encodedFile = reader.result.split(',')[1]

      data.value.push({
        file: encodedFile,
        fileName: nameFile,
        fileExtension: ext,
        fileMimeType: mimeTipe,
      })
    }
  }
}

watch(
  loadItem()
)
</script>

<template>
  <VCol cols="12">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
    <VCard :title="$t('Label.Dettaglio-Prova')">
      <VCardText>
        <VRow no-gutters>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Ol') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ prova.ol }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Esito') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p v-if="prova.esito === 'POSITIVO'" class="text-button text-success mb-1">
              {{ prova.esito }}
            </p>
            <p v-else class="text-button text-error mb-1">
              {{ prova.esito }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Cliente') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ prova.cliente }}
            </p>
          </VCol>

          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Fai') }}:</span>
          </VCol>

          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ (!prova.fai ? '-' : prova.fai) }}
            </p>
          </VCol>
        </VRow>

        <VRow class="mt-5" no-gutters>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Standard') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ prova.standard }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Materiale') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ prova.materiale }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Descrizione') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="5"
          >
            <p class="text-button mb-1">
              {{ prova.descrizione }}
            </p>
          </VCol>
        </VRow>

        <VRow class="mt-5" no-gutters>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Specifica') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ (!prova.specifica ? '-' : prova.specifica) }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Tipologia') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ prova.categoria }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Nota') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="5"
          >
            <p class="text-button text-error mb-1">
              {{ prova.note }}
            </p>
          </VCol>
        </VRow>
        <VSpacer/>
      </VCardText>
    </VCard>
  </VCol>
  <VCol cols="12">
    <VCard>
      <VCardText class="d-flex flex-wrap py-4 gap-4">
        <VCol
          cols="12"
          class=""
          sm="5"
        >
          <!-- 👉 Upload button -->
          <VBtn
            v-if="can(DefineAbilities.qt_prove_tipo_create.action, DefineAbilities.qt_prove_tipo_create.subject)"
            color="success"
            prepend-icon="tabler-upload"
            @click="uploadDialog = true"
          >
            {{ $t('Button.Upload')}}
          </VBtn>
          <!-- 👉 Google Drive button -->
          <VBtn
            color="info"
            prepend-icon="tabler-brand-google-drive"
            class="ml-1"
            target="_blank"
            :href="`https://drive.google.com/drive/u/0/folders/${prova.path_drive}` "
          >
            {{ $t('Button.Google-Drive')}}
          </VBtn>
        </VCol>
      </VCardText>
    </VCard>
  </VCol>

  <VCol cols="7">
    <VCard
      :title="$t('Label.Prove-Per-Ol')"
      class="mb-6"
    >
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :loading="loading"
        class="text-no-wrap mb-0"
        density="compact"
      >
        <!-- Ol -->
        <template #item.ol="{ item }">
          <div class="d-flex align-center">
            <div
              v-if="can(DefineAbilities.qt_prove_tipo_read.action, DefineAbilities.qt_prove_tipo_read.subject)"
              class="d-flex flex-column">
              <h6 class="text-base">
                <VIcon
                  v-if="item.id === prova.id"
                  icon="tabler-arrow-right"
                  size="24"
                  color="primary"
                ></VIcon>
                &nbsp;
                <span
                  @click="reloadItem(item.id)"
                  class="font-weight-medium text-link"
                >
                  {{ item.ol }}
                </span>
                <span v-if="item.note">⚠️</span>
              </h6>
            </div>
            <div
              v-else
              class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.ol }}
                <span v-if="item.note">⚠️</span>
              </h6>
            </div>
          </div>
        </template>

        <!-- risultato -->
        <template #item.esito="{ item }">
          <div class="d-flex gap-1" v-if="item.esito">
            <VChip
              :color="resolveStatusVariant(item.esito).color"
              size="small"
            >
              {{ resolveStatusVariant(item.esito).text }}
            </VChip>
          </div>
        </template>

        <template #bottom>
          <VCardText class="pt-2" style="display: block">
            <div class="d-flex flex-wrap justify-center justify-sm-space-between gap-y-2 mt-2">

            </div>
          </VCardText>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>

  <!-- 👉 Uploadt Dialog  -->
  <VDialog
    v-model="uploadDialog"
    max-width="1400px"
  >
    <AppCardActions
      :title="$t('Label.Upload-Files')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              v-model="isFormValid"
            >
              <VRow>
                <!-- 👉 Upload -->
                <VCol
                  cols="12"
                  md="12"
                >
                  <VFileInput
                    multiple
                    :label="$t('Label.Files')"
                    :rules="[requiredValidator]"
                    @change="uploadFile"
                  />
                </VCol>
              </VRow>
            </VForm>
          </VContainer>
        </VCardText>

        <VCardActions>
          <VSpacer/>
          <VBtn
            type="reset"
            color="error"
            variant="outlined"
            @click="uploadDialog = false"
          >
            Cancel
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            @click="onSubmit(prova.id)"
          >
            Upload
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>

  <!-- Dialog Loading -->
  <VDialog
    v-model="isDialogLoading"
    width="300"
  >
    <VCard
      color="primary"
      width="300"
    >
      <VCardText class="pt-3">
        <span class="ml-4 mb-3">Please stand by</span>
        <VProgressLinear
          :size="40"
          color="warning"
          class="mt-3"
          indeterminate
        />
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped lang="scss">

</style>
