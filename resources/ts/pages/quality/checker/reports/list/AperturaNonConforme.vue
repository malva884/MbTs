<script lang="ts" setup>
import type {ReprotChecker} from '@/views/quality/checker/type'
import type {Conformita} from '@/views/quality/conformita/type'

interface Props {
  itemData: ReprotChecker[]
}

const props = defineProps<Props>()
const conformita: Conformita = ref({})
const title = ref('Apri')
const color = ref('primary')


const isDialogVisible = ref(false)

const notConformityButton = async () => {
  if(props.itemData.not_conformity === '1'){
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
            @click="isDialogVisible = false"
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
              @click="isDialogVisible = false"
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
        <DemoOtpInputHidden
          v-model="props.itemData.id"

        />
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
            v-model="props.itemData.ol"
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
            v-model="props.itemData.coil"
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
            v-model="props.itemData.num_fo"
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
            v-model="props.itemData.stage"
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
            v-model="conformita.macchina"
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
            v-model="conformita.physical_l"
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
            v-model="conformita.optical_l"
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
            v-model="conformita.difetto"
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
            v-model="conformita.diametro"
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
            v-model="conformita.operator"
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
            v-model="conformita.fibre"
            :label="$t('Label.Affected fiber')"
            placeholder="Affected fiber"
          />
        </VCol>

        <!-- 👉 Tipolofia -->
        <VCol
          cols="12"
          md="3"
        >
          <AppSelect
            v-model="conformita.tipologia"
            :items="listaDifetti"
            item-title="text"
            item-value="value"
            :label="$t('Label.Tipologia Fibra')"
            placeholder="-- Seleziona Tipolofia Fibra --"
          />
        </VCol>

        <!-- 👉 Fibre -->
        <VCol
          cols="12"
          md="6"
        >
          <AppTextarea
            v-model="conformita.soluzione"
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
            v-model="conformita.note"
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
