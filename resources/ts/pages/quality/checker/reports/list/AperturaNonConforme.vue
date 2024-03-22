<script lang="ts" setup>
import type {Conformita} from '@/views/quality/conformita/type'

interface Props {
  item: object
}

const props = defineProps<Props>()
const conformitaData: Conformita = ref([])
const conformita = ref<Conformita[]>()

const title = ref('Apri')
const color = ref('primary')


const isDialogVisible = ref(false)

const notConformityButton = async () => {
  if(props.item.not_conformity === '1'){
    title.value = 'Chiudi'
    color.value = 'warning'
  }
}

const listaMacchinari = [
  {text: 'TR 45 MM1', value: 1},
  {text: 'TR 45 MM2', value: 2},
  {text: 'TR 45 MM3', value: 3},
  {text: 'TR 45 MM4', value: 4},
  {text: 'TR 45 R F.O.', value: 5},
  {text: 'TR 45 GAS', value: 6},
]

const listaDifetti = [
  {text: 'BDS', value: 1},
  {text: 'STEP', value: 2},
  {text: 'BC', value: 3},
  {text: 'HA', value: 4},
  {text: 'BREAK', value: 5},
]

const save = async () => {

  conformitaData.value.report_id = props.item.id
  conformitaData.value.ol = props.item.ol
  conformitaData.value.bobina = props.item.coil
  conformitaData.value.num_fo = props.item.num_fo
  conformitaData.value.stage = props.item.stage
  console.log( conformitaData.value)
  const retuenData = await $api('/qt/conformita/store', {
    method: 'POST',
    body: conformitaData,
  })

   //console.log(conformitaData)

  //isDialogVisible.value = false

}

const close = async () => {

  conformitaData.value = ref({})
  isDialogVisible.value = false

}

onMounted(() => {
  notConformityButton()
})


const notConformityColor = (val: string) => {
  let color = 'primary'
  let variant = 'outlined'
  if (val === '1') {
    color = 'warning'
    variant = 'outlined'
  }

  return {variant, color}
}
</script>

<template>
  <VDialog
    v-model="isDialogVisible"
    fullscreen
    :scrim="false"
    transition="dialog-bottom-transition"
  >
    <!-- Dialog Activator -->
    <template #activator="{ props }">
      <VBtn v-bind="props" :color="color">
        {{ title }}
      </VBtn>
    </template>

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
              @click="save"
            >
              Save
            </VBtn>
          </VToolbarItems>
        </VToolbar>
      </div>

      <!-- List -->
      <VList lines="two">
        <VListSubheader>User Controls</VListSubheader>
        <VListItem
          title="Content filtering"
          subtitle="Set the content filtering level to restrict apps that can be downloaded"
        />
        <VListItem
          title="Password"
          subtitle="Require password for purchase or use password to restrict purchase"
        />
      </VList>

      <VDivider/>
      <!-- Form -->
      <VRow class="mt-5 ml-5 mr-5">
        <VCol
          cols="12"
          md="2"
        ></VCol>
        <!-- 👉 First Name -->
        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformita.ol"
            :value="props.item.ol"
            :label="$t('Label.Numero Ordine')"
            :readonly="true"
          />
        </VCol>

        <!-- 👉 Colil -->
        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.bobina"
            :value="props.item.coil"
            :label="$t('Label.Bobbina')"
            :readonly="true"
          />
        </VCol>

        <!-- 👉 Numero Fo -->
        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.num_fo"
            :value="props.item.num_fo"
            :label="$t('Label.Numero Fibre')"
            :readonly="true"
          />
        </VCol>

        <!-- 👉 Stage -->
        <VCol
          cols="12"
          md="2"
        >
          <AppTextField
            v-model="conformitaData.stage"
            :value="props.item.stage"
            :label="$t('Label.Stage')"
            :readonly="true"
          />
        </VCol>
        <VCol
          cols="12"
          md="2"
        ></VCol>
        <!-- 👉 City -->
        <VCol
          cols="12"
          md="2"
        >
          <AppSelect
            v-model="conformitaData.macchina"
            :items="listaMacchinari"
            item-title="text"
            item-value="value"
            :label="$t('Label.Linea')"
            placeholder="-- Seleziona Linea --"
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
          />
        </VCol>

        <!-- 👉 Country -->
        <VCol
          cols="12"
          md="2"
        >
          <AppSelect
            v-model="conformitaData.difetto"
            :items="listaDifetti"
            item-title="text"
            item-value="value"
            :label="$t('Label.Difetto')"
            placeholder="-- Seleziona Difetto --"
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
          />
        </VCol>

        <!-- 👉 Tipolofia Fibra -->
        <VCol
          cols="12"
          md="3"
        >
          <AppSelect
            v-model="conformitaData.tipologia_fibra"
            :items="listaDifetti"
            item-title="text"
            item-value="value"
            :label="$t('Label.Tipologia Fibra')"
            placeholder="-- Seleziona Tipolofia Fibra --"
          />
        </VCol>

        <!-- 👉 Tipolofia Difetto -->
        <VCol
          cols="12"
          md="3"
        >
          <AppSelect
            v-model="conformitaData.tipologia_difetto"
            :items="[{value:'OPTICAL',text: 'OPTICAL'},{value:'PHYSICAL',text: 'PHYSICAL'}]"
            item-title="text"
            item-value="value"
            :label="$t('Label.Tipologia Difetto')"
            placeholder="-- Seleziona Tipolofia Fifetto --"
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
          />
        </VCol>
      </VRow>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.dialog-bottom-transition-enter-active,
.dialog-bottom-transition-leave-active {
  transition: transform 0.2s ease-in-out;
}

.full-screen-dialog-list{
  .v-list-item[tabindex="-2"].v-list-item--active{
    .v-list-item-action{
      .v-icon{
        color: #fff;
      }
    }
  }

}
</style>
