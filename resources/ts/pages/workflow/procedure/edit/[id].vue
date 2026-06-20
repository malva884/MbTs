<script lang="ts" setup>
// eslint-disable-next-line @typescript-eslint/consistent-type-imports
import type { VForm } from 'vuetify/components/VForm'
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'list',
    subject: 'Wf-Procedure',
  },
})

const { t } = useI18n()
const route = useRoute('workflow-procedure-edit-id')
const form = ref<VForm>()
const isDialogLoading = ref(false)
const router = useRouter()
const img = ref()
const file = ref(null)
const data = ref({})
const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)
const certificatiOption = ref<any>([])
const ufficiOption = ref<any>([])
const revisioni = ['0', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z']

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
  id_log_drive: string | null
  disable: boolean
  uffici: []
  certificati: []
  file_upload: []
}

const editItem = ref<Procedure>({})

const loadDocument = async () => {
  isDialogLoading.value = true
  const { data: resultData } = await useApi<any>(createUrl(`/workflow/procedure/get/${route.params.id}`))

  editItem.value = resultData.value.item
  editItem.value.uffici = resultData.value.uffici
  editItem.value.certificati = resultData.value.certificati
  isDialogLoading.value = false
}

const loadCertificati = async () => {
  const resultData = await useApi<any>(createUrl('/workflow/certificazioni/get_list'))

  certificatiOption.value = resultData.data
}

const loadUffici = async () => {
  const resultData = await useApi<any>(createUrl('/workflow/office/get_list'))

  ufficiOption.value = resultData.data
}

loadCertificati()
loadUffici()

const onSubmit = async () => {
  editItem.value.file_upload = data.value
  if (editItem.value.procedura && editItem.value.revisione_anno && editItem.value.revisione && editItem.value.tipologia) {
    isDialogLoading.value = true

    await $api(`/workflow/procedure/edit/${route.params.id}`, {
      method: 'POST',
      body: editItem.value,
    })

    isDialogLoading.value = false
    router.push(`/workflow/procedure/view/${editItem.value.padre_id}`)
  }
}

const uploadFile = (event: any) => {
  file.value = event.target.files[0]

  const reader = new FileReader()

  reader.readAsDataURL(file.value)
  reader.onload = async () => {
    const encodedFile = reader.result.split(',')[1]

    data.value = {
      file: encodedFile,
      fileName: fileName.value,
      fileExtension: fileExtension.value,
      fileMimeType: fileMimeType.value,
    }
  }
}

onMounted(() => {
  loadDocument()
})
</script>

<template>
  <VForm
    ref="form"
    enctype="multipart/form-data"
    lazy-validation
    @submit.prevent="onSubmit"
  >
    <VRow class="mt-5 ml-5 mr-5">
      <!-- 👉 Procedura -->
      <VCol
        cols="12"
        md="2"
      >
        <AppTextField
          v-model="editItem.procedura"
          :label="$t('Label.Procedura')"
          :placeholder="$t('Label.Procedura')"
        />
      </VCol>

      <!-- 👉 Descrizione -->
      <VCol
        cols="12"
        md="5"
      >
        <AppTextField
          v-model="editItem.descrizione"
          :label="$t('Label.Descrizione')"
          :placeholder="$t('Label.Descrizione')"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppSelect
          v-model="editItem.tipologia"
          :items="[{ value: '3', text: t('Label.Istruzione') }, { value: '2', text: t('Label.Modulo') }, { value: '4', text: t('Label.Altro') }]"
          item-title="text"
          item-value="value"
          :rules="[requiredValidator]"
          :label="$t('Label.Tipologia')"
          :placeholder="$t('Label.Tipologia')"
        />
      </VCol>

      <VCol
        cols="12"
        md="1"
      >
        <AppSelect
          v-model="editItem.revisione"
          :items="revisioni"
          :label="$t('Label.Revisione')"
          :placeholder="$t('Label.Revisione')"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppSelect
          v-model="editItem.revisione_anno"
          :items="['2025', '2026']"
          :rules="[requiredValidator]"
          :label="$t('Label.Anno')"
          :placeholder="$t('Label.Anno')"
        />
      </VCol>

      <VCol
        cols="12"
        md="3"
      >
        <AppSelect
          v-model="editItem.uffici"
          :items="ufficiOption.value"
          :item-title="item => item.ufficio"
          :item-value="item => item.id"
          :rules="[requiredValidator]"
          :label="$t('Label.Uffici')"
          :placeholder="$t('Label.Uffici')"
          multiple
          readonly=""
        />
      </VCol>

      <VCol
        cols="12"
        md="3"
      >
        <AppSelect
          v-model="editItem.certificati"
          :items="certificatiOption.value"
          :item-title="item => item.certificazione"
          :item-value="item => item.id"
          :rules="[requiredValidator]"
          :label="$t('Label.Certificati')"
          :placeholder="$t('Label.Certificati')"
          multiple
          readonly=""
        />
      </VCol>

      <!-- 👉 Upload -->
      <!--VCol
        cols="12"
        md="6"
      >
        <VFileInput
          v-model="img"
          accept="image/*,application/pdf"
          :label="$t('Label.File')"
          :rules="[requiredValidator]"
          @change="uploadFile"
        />
      </VCol -->

      <VCol
        cols="12"
        class="d-flex flex-wrap gap-4"
      >
        <VBtn
          color="success"
          type="submit"
        >
          Salva
        </VBtn>
      </VCol>
    </VRow>
  </VForm>

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
