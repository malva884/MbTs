<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'

interface ProvaTipoData {
  id: string | null
  ol: string | null
  fai: string | null
  materiale: string | null
  descrizione: string | null
  esito: string | null
  standard: null
  specifica: string | null
  tipo: null
  data_prova: string | null
  cliente: string | null
  note: string | null
  disable: boolean
}

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'provaTipoData', value: ProvaTipoData): void
}

interface Props {
  provaTipoData?: ProvaTipoData
  standardProveOptions: object
  tipoProvaOptions: object
  isDrawerOpen: boolean
}

const props = withDefaults(defineProps<Props>(), {
  provaTipoData: () => ({
    id: 0,
    ol: '',
    tipo: null,
  }),
})

const emit = defineEmits<Emit>()
const { t } = useI18n()
const isDialogConfirmVisible = ref(false)
const provaTipoData = ref<ProvaTipoData>(structuredClone(toRaw(props.provaTipoData)))
const messageUscita = ref('')
const olAbilitato = ref(false)
const tipo = ref('prova di tipo')
const tipoProva = ['Prova di tipo', 'Fai']
const faiOptions = ref([])
const loading = ref(false)
const serverItems = ref<any>([])
const itemsPerPage = ref(-1)
const hiddenFai = ref(true)
const testP = ref('')

const loadItems = async (ol: string | null) => {
  loading.value = true
  if (ol !== '') {
    const { data: resultData, error } = await useApi<any>(createUrl(`/qt/prove_tipo/get_prove/${ol}`))

    if (resultData.value !== null)
      serverItems.value = resultData.value

    else
      serverItems.value = []

  }
  else
    serverItems.value = []
  loading.value = false
}

const headers = [
  { title: t('Table.Ordine'), key: 'ol', sortable: false },
  { title: t('Table.Materiale'), key: 'materiale', sortable: false },
  { title: t('Table.Esito'), key: 'esito', sortable: false },
  { title: t('Table.Standard'), key: 'standard', sortable: false },
  { title: t('Table.Specifica'), key: 'spcifica', sortable: false },
  { title: t('Table.Tipologia'), key: 'tipologia', sortable: false },
  { title: t('Table.Data'), key: 'data_prova', sortable: false },
]

const resetEvent = () => {
  alert('ok')

}



const getMateriale = async () => {
  if (provaTipoData.value.fai) {
    const found = faiOptions.value.find(element => element.id === provaTipoData.value.fai)

    provaTipoData.value.ol = found.ol
  }

  loadItems(provaTipoData.value.ol)

  const resultData = await useApi<any>(createUrl(`/gp/getMateriale/${provaTipoData.value.ol}`))

  provaTipoData.value.materiale = resultData.data.value.Prodotto
  provaTipoData.value.descrizione = resultData.data.value.Descrizione
  provaTipoData.value.cliente = resultData.data.value.Cliente
}

const close = () => {
  emit('update:isDrawerOpen', false)
}

const exit = () => {
  isDialogConfirmVisible.value = false
  emit('update:isDrawerOpen', false)
}

const errors = ref({})

const onSubmit = () => {
  emit('provaTipoData', provaTipoData.value)
  //emit('update:isDrawerOpen', false)
}

const file = ref(null)
const data = ref({})

const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)

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

const getFai = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/qt/fai/get_fai'))

  faiOptions.value = resultData.value
}

getFai()


const colorTipo = (tipo: string) => {
  if (tipo === 'Fai')
    return 'info'

  return 'success'
}

const checkTipo = () => {

  if (tipo.value === 'fai') {
    hiddenFai.value = false
    olAbilitato.value = true
  }
  else {
    hiddenFai.value = true
    provaTipoData.value.fai = null
    provaTipoData.value.ol = null
    olAbilitato.value = false
  }

}

</script>

<template>
  <VDialog
    :model-value="props.isDrawerOpen"
    @keyup.esc="close" tabindex="-1"
    fullscreen
    :scrim="false"
    transition="dialog-bottom-transition"
  >
    <!-- Dialog Content -->
    <VCard>
      <!-- Toolbar -->
      <div>
        <VToolbar color="primary">
          <VBtn
            icon
            variant="plain"
            @click="close"
          >
            <VIcon
              color="white"
              icon="tabler-x"
            />
          </VBtn>

          <VToolbarTitle>Nuova Prova di Tipo</VToolbarTitle>

          <VSpacer />

          <VToolbarItems>
            <VBtn
              variant="text"
              @click="onSubmit"
            >
              Salva
            </VBtn>
          </VToolbarItems>
        </VToolbar>
      </div>

      <!-- List -->
      <VList lines="two">
        <VListSubheader>Prove Ol</VListSubheader>

        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          :headers="headers"
          :items="serverItems"
          :loading="loading"
          class="text-no-wrap mb-0"
          density="compact"
        >
          <template #bottom>
            <VCardText class="pt-2" style="display: block">
              <div class="d-flex flex-wrap justify-center justify-sm-space-between gap-y-2 mt-2">

              </div>
            </VCardText>
          </template>
        </VDataTableServer>
      </VList>
      <VDivider />
      <VRow class="mt-5 ml-5 mr-5">
        <VCol
          cols="12"
          md="5"
        />
        <!-- 👉 Ol -->
        <VCol
          cols="12"
          md="2"
        >
          <VRadioGroup
            v-model="tipo"
            inline
          >
            <div>
              <VRadio
                v-for="radio in tipoProva"
                :key="radio"
                :label="radio"
                :color="colorTipo(radio)"
                :value="radio.toLocaleLowerCase()"
                @change="checkTipo"
              />
            </div>
          </VRadioGroup>
        </VCol>
        <VCol
          cols="12"
          md="5"
        />
        <!-- 👉 Fai -->
        <VCol
          v-if="!hiddenFai"
          cols="12"
          md="2"
          class="mt-0"
        >
          <AppSelect
            v-model="provaTipoData.fai"
            :items="faiOptions"
            :item-title="item => item.numero_fai"
            :item-value="item => item.id"
            :label="$t('Label.Fai')"
            :placeholder="$t('Label.Fai')"
            :readonly="!!provaTipoData.disable"
            @focusout="getMateriale"
          />
        </VCol>
        <!-- 👉 Ol -->
        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="provaTipoData.ol"
            :label="$t('Label.Numero Ordine')"
            :placeholder="$t('Label.Numero Ordine')"
            :rules="[requiredValidator]"
            :error-messages="errors.ol"
            :readonly="!!olAbilitato"
            @focusout="getMateriale"
          />
        </VCol>

        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="provaTipoData.materiale"
            :label="$t('Label.Materiale')"
            :placeholder="$t('Label.Materiale')"
            readonly
          />
        </VCol>

        <!-- 👉 Cliente -->
        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="provaTipoData.cliente"
            :label="$t('Label.Cliente')"
            :placeholder="$t('Label.Cliente')"
            :readonly="!!olAbilitato"
          />
        </VCol>

        <VCol
          cols="12"
          md="4"
        >
          <AppTextField
            v-model="provaTipoData.descrizione"
            :label="$t('Label.Descrizione-Matariale')"
            :placeholder="$t('Label.Descrizione-Matariale')"
            readonly
          />
        </VCol>

        <VCol
          cols="12"
          md="2"
        >
          <AppSelect
            v-model="provaTipoData.esito"
            :items="[{ value: 'Positivo', text: 'Positivo' }, { value: 'Negativo', text: 'Negativo' }]"
            :rules="[requiredValidator]"
            item-title="text"
            item-value="value"
            :label="$t('Label.Stato')"
            :placeholder="$t('Label.Stato')"
          />
        </VCol>

        <VCol
          cols="12"
          md="2"
        >
          <AppSelect
            v-model="provaTipoData.standard"
            :items="props.standardProveOptions"
            :item-title="item => item.categoria"
            :item-value="item => item.categoria"
            :rules="[requiredValidator]"
            :label="$t('Label.Standard')"
            :placeholder="$t('Label.Standard')"
          />
        </VCol>

        <!-- 👉 Specifica -->
        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="provaTipoData.specifica"
            :label="$t('Label.Specifica')"
            :placeholder="$t('Label.Specifica')"
            :readonly="!!olAbilitato"
          />
        </VCol>

        <!-- 👉 Tipologia Test -->
        <VCol
          cols="12"
          md="2"
        >
          <AppSelect
            v-model="provaTipoData.tipo"
            :items="props.tipoProvaOptions"
            :item-title="item => item.categoria"
            :item-value="item => item.id"
            :label="$t('Label.Tipologia')"
            :placeholder="$t('Label.Tipologia')"
            :rules="[requiredValidator]"
          />
        </VCol>

        <VCol
          cols="12"
          md="2"
        >
          <AppDateTimePicker
            v-model="testP"
            :label="$t('Label.Data')"
            :placeholder="$t('Label.Data')"
          />
        </VCol>

        <!-- 👉 Note -->
        <VCol
          cols="12"
          md="6"
        >
          <AppTextarea
            v-model="provaTipoData.note"
            :label="$t('Label.Note')"
            placeholder="Note"
            :readonly="!!provaTipoData.disable"
            :error-messages="errors.note"
          />
        </VCol>

        <!-- 👉 Upload -->
        <VCol
          v-if="!provaTipoData.disable"
          cols="12"
          md="6"
        >
          <VFileInput
            multiple
            accept="image/*,application/pdf"
            :label="$t('Label.File')"
            :rules="[requiredValidator]"
            @change="uploadFile"
          />
        </VCol>
      </VRow>

      <VDivider />
      <!-- Form -->
    </VCard>
  </VDialog>

  <VDialog
    v-model="isDialogConfirmVisible"
    persistent
    class="v-dialog-sm"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isDialogConfirmVisible = !isDialogConfirmVisible" />

    <!-- Dialog Content -->
    <VCard title="Conferma Uscita?">
      <VCardText>
        {{ messageUscita }}
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <VBtn
          color="error"
          variant="tonal"
          @click="isDialogConfirmVisible = false"
        >
          No
        </VBtn>
        <VBtn @click="exit">
          Si
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.permission-table {
  td {
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding-block: 0.5rem;
    padding-inline: 0;
  }
}
</style>
