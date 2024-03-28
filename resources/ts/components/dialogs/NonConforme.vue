<script setup lang="ts">
interface ConformitaData {
  id: string | null
  report_id: string | null
  user: number | null
  data_apertura: string
  data_chiusura: string
  ol: string
  materiale: string
  num_fo: number | null
  stage: string
  bobina: string
  note: string
  macchina: number
  difetto: number
  fibre: string
  soluzione: string
  chiuso: boolean | null
  diametro: number
  tipologia_fibra: string
  operator: string
  physical_l: number
  optical_l: number
  tipologia_difetto: string
  disable: number
}

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'conformitaData', value: ConformitaData): void
}

interface Props {
  conformitaData?: ConformitaData
  macchineOptions: object
  defettiOptions: object
  fibraTipoOptions: object
  isDialogVisible: boolean
}

const props = withDefaults(defineProps<Props>(), {
  conformitaData: () => ({
    id: 0,
    ol: '',
  }),
})

const emit = defineEmits<Emit>()
const isDialogConfirmVisible = ref(false)
const conformitaData = ref<ConformitaData>(structuredClone(toRaw(props.conformitaData)))
const messageUscita = ref('')

watch(props, () => {
  errors.value = []
  conformitaData.value = structuredClone(toRaw(props.conformitaData))
})

const setTipoDifetto = async () => {
  const item1 = props.defettiOptions.find(i => i.id === conformitaData.value.difetto)

  conformitaData.value.tipologia_difetto = item1.categoria
}

const onReset = () => {
  conformitaData.value = structuredClone(toRaw(props.conformitaData))

  emit('update:isDialogVisible', false)
}

const close = () => {
  if (conformitaData.value.disable == true) {
    messageUscita.value = 'Non hai apportato nessuna modifica, sei sicuro di voler uscire?'
    isDialogConfirmVisible.value = true
  }
  else if (conformitaData.value.difetto && conformitaData.value.macchina && conformitaData.value.soluzione) {
    messageUscita.value = 'Non hai salvato, sei sicuro di voler uscire?'
    isDialogConfirmVisible.value = true
  }
  emit('update:isDialogVisible', false)

}

const exit = () => {
  isDialogConfirmVisible.value = false
  emit('update:isDialogVisible', false)
}

const errors = ref({})

const onSubmit = () => {
  if (conformitaData.value.disable === true && conformitaData.value.chiuso === '0') {
    messageUscita.value = 'Non hai apportato nessuna modifica, sei sicuro di voler uscire?'
    isDialogConfirmVisible.value = true
  }
  else if (conformitaData.value.difetto && conformitaData.value.macchina && conformitaData.value.soluzione) {
    errors.value = []

    emit('conformitaData', conformitaData.value)
    emit('update:isDialogVisible', false)
  }
  else {
    errors.value.difetto = 'Campo Obligatorio!'
    errors.value.macchina = 'Campo Obligatorio!'
    errors.value.soluzione = 'Campo Obligatorio!'
  }
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

          <VToolbarTitle>Apertura Non Conformità</VToolbarTitle>

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
      <!--VList lines="two">
        <VListSubheader>User Controls</VListSubheader>
        <VListItem
          title="Content filtering"
          subtitle="Set the content filtering level to restrict apps that can be downloaded"
        />
        <VListItem
          title="Password"
          subtitle="Require password for purchase or use password to restrict purchase"
        />
      </VList -->
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
            v-model="conformitaData.chiuso"
            :items="[{ value: '0', text: 'Aperto' }, { value: '1', text: 'Chiuso' }]"
            item-title="text"
            item-value="value"
            :label="$t('Label.Stato')"
            placeholder="-- Seleziona Tipolofia Fifetto --"
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
            readonly
          />
        </VCol>

        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.materiale"
            :label="$t('Label.Cavo')"
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
            :label="$t('Label.Bobbina')"
            readonly
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
            readonly
          />
        </VCol>

        <!-- 👉 Stage -->
        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.stage"
            :label="$t('Label.Stage')"
            readonly
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
            type="number"
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
            type="number"
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
            :rules="[requiredValidator]"
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
          md="4"
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
            :rules="[requiredValidator]"
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

        <!-- 👉 Soluzione -->
        <VCol
          cols="12"
          md="6"
        >
          <AppTextarea
            v-model="conformitaData.soluzione"
            :label="$t('Label.Soluzione')"
            placeholder="Soluzione"
            :error-messages="errors.soluzione"
            :readonly="!!conformitaData.disable"
            :rules="[requiredValidator]"
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
            placeholder="Note"
            :readonly="!!conformitaData.disable"
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
    <!-- Dialog Activator -->
    <template #activator="{ props }">
      <VBtn v-bind="props">
        Conferma
      </VBtn>
    </template>

    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isDialogConfirmVisible = !isDialogConfirmVisible" />

    <!-- Dialog Content -->
    <VCard title="Conferma Uscita?">
      <VCardText>
       {{messageUscita}}
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
