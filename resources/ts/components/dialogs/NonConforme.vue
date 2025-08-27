<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import { can } from '@layouts/plugins/casl'
import type { Conformita } from '@/views/quality/conformita/type'

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'conformitaData', value: Conformita): void
}

interface Props {
  conformitaData?: Conformita
  macchineOptions: object
  defettiOptions: object
  fibraTipoOptions: object
  isDialogVisible: boolean
}

const props = withDefaults(defineProps<Props>(), {
  conformitaData: () => ({
    id: undefined,
    report_id: null,
    user: null,
    ol: '',
    materiale: '',
    num_fo: null,
    stage: '',
    bobina: '',
    note: '',
    macchina: '',
    difetto: '',
    fibre: '',
    soluzione: '',
    stato: null,
    diametro: null,
    tipologia_fibra: '',
    operator: '',
    physical_l: null,
    optical_l: null,
    tipologia_difetto: '',
    disable: false,
    approvazione: '',
    motivazione: '',
    file_upload: {},
  }),
})

const emit = defineEmits<Emit>()
const { t } = useI18n()
const isDialogConfirmVisible = ref(false)
const conformitaData = ref<Conformita>(structuredClone(toRaw(props.conformitaData)))
const stato = ref('0')
const viewNoteApprovazione = ref(false)
const messageUscita = ref('')
const olAbilitato = ref(false)
const stageDisable = ref(true)
const closed = ref(false)
const viewAttivita = ref(false)
const listAttivita = ref({})
const permessiAdmin = can(DefineAbilities.qt_non_conformita_admin.action, DefineAbilities.qt_non_conformita_admin.subject)
const activeClass = ref('active')
const errorClass = ref('text-error mb-1')
const goodClass = ref('text-success mb-1')
const listaStage = ref([])

watch(props, () => {
  closed.value = false
  errors.value = []
  conformitaData.value = structuredClone(toRaw(props.conformitaData))
  stato.value = conformitaData.value.stato
  if (conformitaData.value.stato == 3) {
    olAbilitato.value = false
    conformitaData.value.disable = true
    closed.value = true
  }

  listaStage.value = [
    { text: 'BUF', value: 'BUF' },
    { text: 'SZ', value: 'SZ' },
    { text: 'FC', value: 'FC' },
    { text: 'PE', value: 'PE' },
    { text: 'COL', value: 'COL' },
    { text: 'SF', value: 'SF' },
    { text: 'NASTR', value: 'NASTR' },
    { text: 'ARMA', value: 'ARMA' },
    { text: 'CORD', value: 'CORD' },
    { text: 'GUA', value: 'GUA' },
    { text: 'ISOL', value: 'ISOL' },
  ]

  olAbilitato.value = (!!conformitaData.value.ol)
  if (conformitaData.value.id !== undefined && conformitaData.value.id !== '') {
    const attivitaResultData = useApi<any>(createUrl(`/qt/conformita/get_attivita/${conformitaData.value.id}`))

    if (attivitaResultData.data !== null)
      listAttivita.value = attivitaResultData.data

    viewAttivita.value = true
  }
})

const setTipoDifetto = async () => {
  const item1 = props.defettiOptions.find(i => i.id === conformitaData.value.difetto)

  conformitaData.value.tipologia_difetto = item1.categoria
}

const getMateriale = async () => {
  const resultData = await useApi<any>(createUrl(`/gp/getMateriale/${conformitaData.value.ol}`))
  const materiale = resultData.data.value.Prodotto
  const descrizione = resultData.data.value.Descrizione

  if (materiale !== undefined) {
    const tmp = descrizione.split(' ', 2)

    stageDisable.value = true
    let iniziali = materiale.substr(0, 2)
    if (iniziali === 'F5' || iniziali === 'F6' || iniziali === 'S4') {
      stageDisable.value = false
      iniziali = ''
    }
    if (iniziali === 'BU')
      iniziali = 'BUF'

    if (iniziali === 'CO')
      iniziali = 'COL'

    conformitaData.value.stage = iniziali
  }
  conformitaData.value.materiale = resultData.data.value.Prodotto
}

const close = () => {
  if (conformitaData.value.disable === true) {
    messageUscita.value = 'Non hai apportato nessuna modifica, sei sicuro di voler uscire?'
    isDialogConfirmVisible.value = true
  }
  else if (conformitaData.value.difetto && conformitaData.value.macchina && conformitaData.value.soluzione && conformitaData.value.bobina && conformitaData.value.ol) {
    messageUscita.value = 'Non hai salvato, sei sicuro di voler uscire?'
    isDialogConfirmVisible.value = true
  }
  else { emit('update:isDialogVisible', false) }
}

const exit = () => {
  isDialogConfirmVisible.value = false
  emit('update:isDialogVisible', false)
}

const errors = ref({})

const onSubmit = () => {
  if (conformitaData.value.disable === true && conformitaData.value.stato === '1' && !permessiAdmin) {
    messageUscita.value = 'Non hai apportato nessuna modifica, sei sicuro di voler uscire?'
    isDialogConfirmVisible.value = true
  }
  else if (conformitaData.value.difetto && conformitaData.value.macchina && conformitaData.value.note && conformitaData.value.bobina && conformitaData.value.ol) {
    errors.value = []
    if (conformitaData.value.id === undefined || conformitaData.value.id === '') {
      conformitaData.value.file_upload = data.value
      emit('conformitaData', conformitaData.value)
      emit('update:isDialogVisible', false)
    }
    else if ((conformitaData.value.stato === '2' && conformitaData.value.soluzione) || (conformitaData.value.stato === '3' && conformitaData.value.approvazione && conformitaData.value.motivazione) || (conformitaData.value.stato === '1' && conformitaData.value.approvazione && permessiAdmin)) {
      emit('conformitaData', conformitaData.value)
      emit('update:isDialogVisible', false)
    }
    else if (conformitaData.value.stato === '2' && !conformitaData.value.soluzione) {
      errors.value.soluzione = 'Campo Obligatorio!'
    }
    else if (conformitaData.value.stato === '3' && (!conformitaData.value.approvazione || !conformitaData.value.motivazione)) {
      errors.value.approvazione = 'Campo Obligatorio!'
      errors.value.motivazione = 'Campo Obligatorio!'
    }
    else if (conformitaData.value.stato === '1' && !conformitaData.value.approvazione && permessiAdmin) {
      errors.value.approvazione = 'Campo Obligatorio!'
      errors.value.motivazione = 'Campo Obligatorio!'
    }
    else {
      alert('OPS.....')
    }
  }
  else {
    errors.value.difetto = 'Campo Obligatorio!'
    errors.value.macchina = 'Campo Obligatorio!'
    errors.value.note = 'Campo Obligatorio!'
    errors.value.ol = 'Campo Obligatorio!'
    errors.value.bobina = 'Campo Obligatorio!'
  }
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

const visualizzaNote = () => {
  viewNoteApprovazione.value = false
  if ((conformitaData.value.stato == 3 && permessiAdmin) || (conformitaData.value.stato == 1 && permessiAdmin))
    viewNoteApprovazione.value = true
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
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

          <VToolbarTitle>{{ $t('Label.Apertura-Non-Conformita') }}</VToolbarTitle>

          <VSpacer />

          <VToolbarItems>
            <VBtn
              v-if="can(DefineAbilities.qt_non_conformita_create.action, DefineAbilities.qt_non_conformita_create.subject)"
              variant="text"
              @click="onSubmit"
            >
              {{ $t('Label.Salva') }}
            </VBtn>
          </VToolbarItems>
        </VToolbar>
      </div>

      <!-- List -->
      <VList
        v-if="viewAttivita"
        lines="two"
      >
        <VListSubheader>{{ $t('Label.Log-Attivita') }}</VListSubheader>
        <VCardText>
          <VRow
            v-for="(value, index) in listAttivita.value"
            :key="value"
            no-gutters
          >
            <VCol
              cols="12"
              md="1"
            >
              <p class="text-primary mb-1">
                {{ formatDate(value.data_soluzione) }}
              </p>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <p class="text-primary mb-1">
                {{ value.user_s }}
              </p>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <p class="text-primary mb-1">
                {{ value.soluzione }}
              </p>
            </VCol>
            <VCol
              cols="12"
              md="1"
            >
              <p class=" mb-1">
                =>
              </p>
            </VCol>
            <VCol
              cols="12"
              md="1"
            >
              <p :class="[(value.esito != 3) ? activeClass : goodClass, errorClass]">
                {{ formatDate(value.data_approvazione) }}
              </p>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <p :class="[(value.esito != 3) ? activeClass : goodClass, errorClass]">
                {{ value.user_a }}
              </p>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <p :class="[(value.esito != 3) ? activeClass : goodClass, errorClass]">
                {{ value.nota_approvazione }}
              </p>
            </VCol>
            <VDivider />
          </VRow>
        </VCardText>
      </VList>
      <VRow class="mt-5 ml-5 mr-5">
        <VCol
          v-if="conformitaData.disable"
          cols="12"
          md="3"
        />
        <VCol
          v-if="conformitaData.disable"
          cols="12"
          md="6"
        >
          <AppSelect
            v-if="(can(DefineAbilities.qt_non_conformita_admin.action, DefineAbilities.qt_non_conformita_admin.subject) && conformitaData.stato === '2') || conformitaData.stato === '3'"
            v-model="conformitaData.stato"
            :items="[{ value: '1', text: t('Label.Riapri') }, { value: '2', text: t('Label.Da-Chiudere') }, { value: '3', text: t('Label.Chiudi') }]"
            item-title="text"
            item-value="value"
            :label="$t('Label.Stato')"
            placeholder="-- Seleziona Stato --"
            :readonly="!!closed"
            @focusout="visualizzaNote"
          />
          <AppSelect
            v-else
            v-model="conformitaData.stato"
            :items="[{ value: '1', text: t('Label.Aperto') }, { value: '2', text: t('Label.Da-Chiudere') }]"
            item-title="text"
            item-value="value"
            :label="$t('Label.Stato')"
            :readonly="stato === '2' ? true : false"
            placeholder="-- Seleziona Stato --"
          />
        </VCol>
        <VCol
          v-if="conformitaData.disable"
          cols="12"
          md="3"
        />

        <VCol
          cols="12"
          md="1"
        />
        <!-- 👉 First Name -->
        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.ol"
            :label="$t('Label.Numero Ordine')"
            :placeholder="$t('Label.Numero Ordine')"
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
            v-model="conformitaData.materiale"
            :label="$t('Label.Cavo')"
            :placeholder="$t('Label.Cavo')"
            readonly
          />
        </VCol>

        <!-- 👉 Colil -->
        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.bobina"
            :label="$t('Label.Bobina')"
            :placeholder="$t('Label.Bobina')"
            :error-messages="errors.bobina"
            :readonly="!!olAbilitato"
          />
        </VCol>

        <!-- 👉 Numero Fo -->
        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.num_fo"
            :label="$t('Label.Numero Fibre')"
            :placeholder="$t('Label.Numero Fibre')"
            readonly
          />
        </VCol>

        <!-- 👉 Stage -->
        <VCol
          cols="12"
          md="2"
        >
          <AppSelect
            v-model="conformitaData.stage"
            :items="listaStage"
            item-title="text"
            item-value="value"
            :label="$t('Label.Stage')"
            :readonly="!!stageDisable"
            placeholder="-- Seleziona Stato --"
          />
        </VCol>
        <VCol
          cols="12"
          md="1"
        />
        <VCol
          cols="12"
          md="2"
        >
          <AppSelect
            v-model="conformitaData.macchina"
            :items="props.macchineOptions"
            :item-title="item => item.titolo"
            :item-value="item => item.id"
            :label="$t('Label.Linea')"
            placeholder="-- Seleziona Linea --"
            :error-messages="errors.macchina"
            :readonly="!!conformitaData.disable"
            :rules="[requiredValidator]"
          />
        </VCol>

        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.physical_l"
            :label="$t('Label.Physical Lenght')"
            placeholder="Physical Lenght"
            :readonly="!!conformitaData.disable"
          />
        </VCol>

        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.optical_l"
            :label="$t('Label.Optical Lenght')"
            placeholder="Optical Lenght"
            :readonly="!!conformitaData.disable"
          />
        </VCol>

        <!-- 👉 Country -->
        <VCol
          cols="12"
          md="2"
        >
          <AppSelect
            v-model="conformitaData.difetto"
            :items="props.defettiOptions"
            :item-title="item => item.titolo"
            :item-value="item => item.id"
            :label="$t('Label.Difetto')"
            placeholder="-- Seleziona Difetto --"
            :error-messages="errors.difetto"
            :readonly="!!conformitaData.disable"
            @focusout="setTipoDifetto"
          />
        </VCol>

        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.diametro"
            type="number"
            :label="$t('Label.Diametro')"
            placeholder="Diametro"
            :readonly="!!conformitaData.disable"
          />
        </VCol>

        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.operator"
            :label="$t('Label.Operatore Buffering')"
            placeholder="Operatore Buffering"
            :readonly="!!conformitaData.disable"
          />
        </VCol>

        <!-- 👉 Fibre -->
        <VCol
          cols="12"
          md="3"
        >
          <AppTextField
            v-model="conformitaData.fibre"
            :label="$t('Label.Affected fiber')"
            placeholder="Affected fiber"
            :readonly="!!conformitaData.disable"
          />
        </VCol>

        <!-- 👉 Tipolofia Fibra -->
        <VCol
          cols="12"
          md="3"
        >
          <AppSelect
            v-model="conformitaData.tipologia_fibra"
            :items="props.fibraTipoOptions"
            :item-title="item => item.titolo"
            :item-value="item => item.id"
            :label="$t('Label.Tipologia Fibra')"
            placeholder="-- Seleziona Tipolofia Fibra --"
            :readonly="!!conformitaData.disable"
          />
        </VCol>

        <!-- 👉 Tipolofia Difetto -->
        <VCol
          cols="12"
          md="3"
        >
          <AppSelect
            v-model="conformitaData.tipologia_difetto"
            :items="[{ value: 'OPTICAL', text: 'OPTICAL' }, { value: 'PHYSICAL', text: 'PHYSICAL' }]"
            item-title="text"
            item-value="value"
            :label="$t('Label.Tipologia Difetto')"
            placeholder="-- Seleziona Tipolofia Fifetto --"
            :readonly="!!conformitaData.disable"
          />
        </VCol>

        <!-- 👉 Tipolofia Motivazione Chiusura -->
        <VCol
          v-if="can(DefineAbilities.qt_non_conformita_admin.action, DefineAbilities.qt_non_conformita_admin.subject) && conformitaData.motivazione_chiusura"
          cols="12"
          md="3"
        >
          <AppSelect
            v-model="conformitaData.motivazione_chiusura"
            :items="[{ value: '1', text: 'Prodotto conforme' }, { value: '2', text: 'NC risolta in reworking' }, { value: '3', text: 'Deroga da parte del cliente' }, { value: '4', text: ' Deroga interna' }]"
            item-title="text"
            item-value="value"
            :label="$t('Label.Motivazione')"
            placeholder="-- Seleziona Motivazione --"
            :error-messages="errors.motivazione"
            :readonly="true"
          />
        </VCol>

        <!-- 👉 Soluzione -->
        <VCol
          v-if="conformitaData.disable"
          cols="12"
          md="6"
        >
          <AppTextarea
            v-model="conformitaData.soluzione"
            :label="$t('Label.Soluzione')"
            placeholder="Soluzione"
            :error-messages="errors.soluzione"
            :rules="[requiredValidator]"
            :readonly="closed || stato === '2' ? true : false"
          />
        </VCol>

        <!-- 👉 Note -->
        <VCol
          cols="12"
          md="6"
        >
          <AppTextarea
            v-model="conformitaData.note"
            :label="$t('Label.Note')"
            :placeholder="$t('Label.Note')"
            :readonly="!!conformitaData.disable"
            :error-messages="errors.note"
          />
        </VCol>

        <!-- 👉 Upload -->
        <VCol
          v-if="!conformitaData.disable"
          cols="12"
          md="6"
        >
          <VFileInput
            accept="image/*,application/pdf"
            :label="$t('Label.File')"
            @change="uploadFile"
          />
        </VCol>

        <!-- 👉 Note Approvazione -->
        <VCol
          v-if="can(DefineAbilities.qt_non_conformita_admin.action, DefineAbilities.qt_non_conformita_admin.subject) && !closed && viewNoteApprovazione"
          cols="12"
          md="6"
        >
          <AppTextarea
            v-model="conformitaData.approvazione"
            :label="$t('Label.Nota-Approvazione')"
            :placeholder="$t('Label.Nota-Approvazione')"
            :error-messages="errors.approvazione"
            :rules="[requiredValidator]"
          />
        </VCol>

        <!-- 👉 Tipolofia Motivazione -->
        <VCol
          v-if="can(DefineAbilities.qt_non_conformita_admin.action, DefineAbilities.qt_non_conformita_admin.subject) && !closed && viewNoteApprovazione && conformitaData.stato == 3"
          cols="12"
          md="3"
        >
          <AppSelect
            v-model="conformitaData.motivazione"
            :items="[{ value: '1', text: 'Prodotto conforme' }, { value: '2', text: 'NC risolta in reworking' }, { value: '3', text: 'Deroga da parte del cliente' }, { value: '4', text: ' Deroga interna' }]"
            item-title="text"
            item-value="value"
            :label="$t('Label.Motivazione')"
            placeholder="-- Seleziona Motivazione --"
            :error-messages="errors.motivazione"
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
