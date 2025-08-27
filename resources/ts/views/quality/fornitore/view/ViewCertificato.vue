<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { watch } from 'vue'

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'itemData', value: object): void
}
interface Props {
  itemData: object
  isDrawerOpen: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const { t } = useI18n()
const file = ref()
const viewFile = ref(false)
const avvisoDialog = ref(false)
const refForms = ref<VForm>()

const defaultItem = ref<any>({
  id: '',
  supplier_id: '',
  titolo: '',
  testo: '',
  scadenza: '',
  visualizata: null,
  user_id: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    supplier_id: '',
    titolo: '',
    testo: '',
    scadenza: '',
    visualizata: null,
    user_id: '',
  }
}

const noticeItem = ref<any>(defaultItem.value)
const noticeIndex = ref(-1)

const approve = async (result: string) => {
  // eslint-disable-next-line vue/no-mutating-props
  props.itemData.approvato = result
  const retuenData = await $api(`/qt/certification/approve/supplier/${props.itemData.id}`, {
    method: 'POST',
    body: props.itemData,
  })

  emit('itemData', props.itemData)
  emit('update:isDrawerOpen', false)
}

const loadFile = () => {
  file.value = `https://drive.google.com/file/d/${props.itemData.file_id}/preview`
  viewFile.value = true
}

loadFile()

const newItem = () => {
  new_defaultItem()
  noticeIndex.value = -1
  noticeItem.value = { ...defaultItem.value }

  noticeItem.value.supplier_id = props.itemData.fornitore_id
  noticeItem.value.certificato_id = props.itemData.id
  avvisoDialog.value = true
}

const certificatoRespinto = () => {
  refForms.value?.validate().then(async ({ valid }) => {
    if (valid) {
      props.itemData.avviso = { ...noticeItem.value }
      avvisoDialog.value = false
      approve('0')
    }
  })
}

const close = () => {
  emit('update:isDrawerOpen', false)
}

watch(props, () => {
  loadFile()
})
</script>

<template>
  <VDialog
    :model-value="props.isDrawerOpen"
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

          <VToolbarTitle>{{ props.itemData.titolo }}</VToolbarTitle>

          <VSpacer/>

          <VToolbarItems>
            <VBtn
              variant="text"
              @click="close"
            >
              {{ t('Label.Chiudi') }}
            </VBtn>
          </VToolbarItems>
        </VToolbar>
      </div>
      <VRow v-if="viewFile">
        <VCol
          md="9"
          cols="12"
        >
          <VCard class="fullPage">
            <VCardText class="fullPage">
              <iframe
                :src="file"
                height="100%"
                width="100%"
                allowfullscreen
              >
              </iframe>
            </VCardText>
          </VCard>
        </VCol>
        <VCol
          cols="12"
          md="3"
        >
          <VCard>
            <VCardText>
              <!-- 👉 Send Invoice Trigger button -->
              <VChip
                size="x-large"
                color="primary"
                variant="elevated"
                class="mb-4"
              >
                {{ t('Label.Scadenza') + ': ' + props.itemData.scadenza }}
              </VChip>
              <!-- 👉  Rifiuta  -->
              <VBtn
                block
                prepend-icon="tabler-alert-hexagon"
                class="mb-4"
                color="error"
                @click="newItem"
              >
                {{ t('Button.Rifiuta') }}
              </VBtn>

              <!-- 👉  Approva  -->
              <VBtn
                block
                prepend-icon="tabler-check"
                color="success"
                @click="approve('1')"
              >
                {{ t('Button.Approva') }}
              </VBtn>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCard>
  </VDialog>

  <VDialog
    v-model="avvisoDialog"
    max-width="900"
    persistent=""
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="avvisoDialog = !avvisoDialog" />

    <!-- Dialog Content -->
    <VCard :title="t('Label.Avviso')">
      <VCardText>
        <VContainer>
          <VForm
            ref="refForms"
            @submit.prevent="certificatoRespinto"
          >
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="noticeItem.titolo"
                  :label="t('Label.Titolo')"
                  :placeholder="t('Label.Titolo')"
                  :rules="[requiredValidator]"
                />
              </vcol>

              <VCol cols="12">
                <TiptapEditor
                  v-model="noticeItem.testo"
                  class="border rounded basic-editor"
                  :label="t('Label.Avviso')"
                  :placeholder="t('Label.Avviso')"
                  :rules="[requiredValidator]"
                />
              </VCol>
            </VRow>
            <VRow class="mt-8">
              <VCardActions>
                <VSpacer />

                <VBtn
                  type="reset"
                  color="error"
                  variant="outlined"
                  @click="avvisoDialog = false"
                >
                  Cancel
                </VBtn>

                <VBtn
                  type="submit"
                  color="success"
                  variant="elevated"
                  @click="refForms?.validate()"
                >
                  Save
                </VBtn>
              </VCardActions>
            </VRow>
          </VForm>
        </VContainer>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.dialog-bottom-transition-enter-active,
.dialog-bottom-transition-leave-active {
  transition: transform 0.2s ease-in-out;
}

.fullPage {
  height: 100% !important;
}
</style>
