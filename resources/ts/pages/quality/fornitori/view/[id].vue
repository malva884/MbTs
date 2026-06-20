<script setup lang="ts">
import type { VForm } from 'vuetify/components/VForm'
import EditFornitore from '@/views/quality/fornitore/editFornitore.vue'
import FornitoreTabCertificazioni from '@/views/quality/fornitore/view/FornitoreTabCertificazioni.vue'
import FornitoreTabRating from '@/views/quality/fornitore/view/FornitoreTabRating.vue'
import FornitoreTabLog from '@/views/quality/fornitore/view/FornitoreTabLog.vue'
import FornitoreTabUtenti from '@/views/quality/fornitore/view/FornitoreTabUtenti.vue'
import FornitoreTabAvvisi from '@/views/quality/fornitore/view/FornitoreTabAvvisi.vue'
import {types} from "sass";
import Null = types.Null;

definePage({
  meta: {
    action: 'list',
    subject: 'Qt-Supplier',
  },
})

const route = useRoute('quality-fornitori-view-id')
const { t } = useI18n()
const refForm = ref<VForm>()
const editDialog = ref(false)
const notificaVisible = ref(false)
const loadingPage = ref(false)
const view = ref(false)
const deleteDialog = ref(false)
const idFornitore = ref()
const color = ref()
const message = ref()
const item = ref({})
const isSnackbarScrollReverseVisible = ref(false)

const fornitireTab = ref(null)

const tabs = [
  { icon: 'tabler-timeline', title: t('Label.Rating') },
  { icon: 'tabler-files', title: t('Label.Appalti') },
  { icon: 'tabler-file-certificate', title: t('Label.Certificazioni') },
  { icon: 'tabler-ce-off', title: t('Label.Non-Conformita') },
  { icon: 'tabler-brand-days-counter', title: t('Label.Ritardi') },
  { icon: 'tabler-bell-ringing', title: t('Label.Avvisi') },
  { icon: 'tabler-users', title: t('Label.Utenti-Portale') },
  { icon: 'tabler-line-height', title: t('Label.Log') },
]

const loadItem = async () => {
  const { data: supplierData } = await useApi<any>(createUrl(`/qt/supplier/${route.params.id}`))

  item.value = supplierData.value
  view.value = true
}

loadItem()

const save = async (data: object) => {
  loadingPage.value = true
  let path = '/qt/supplier/stored/'
  if (item.value.id)
    path = `/qt/supplier/update/${item.value.id}`

  const retuenData = await $api(path, {
    method: 'POST',
    body: data,
  })

  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })

  loadItem()
  message.value = retuenData.message
  color.value = retuenData.color
  notificaVisible.value = true
  loadingPage.value = false
}

const approva = () => {
  item.value.qualificato = (item.value.qualificato === '0' ? '1' : '0')
  save(item.value)
}

function openDrivePage() {
  window.open(`https://drive.google.com/drive/u/0/folders/${item.value.folderID}`, '_blank')
}

const openDeleted = (fornitoreId: string) => {
  idFornitore.value = fornitoreId
  deleteDialog.value = true
}

const editItem = () => {
  editDialog.value = true
}

const resolverApprovato = (stato: string) => {
  if (stato === '1')
    return { label: 'Label.Qualificato', color: 'success' }
  else
    return { label: 'Label.Non-Qualificato', color: 'warning' }
}

const resolverCritico = (stato: string) => {
  if (stato === '1')
    return { label: 'Label.Si', color: 'warning' }
  else
    return { label: 'Label.No', color: 'success' }
}

const closeDelete = () => {
  idFornitore.value = null
  deleteDialog.value = false
}

const deleteItemConfirm = async () => {
  loadingPage.value = true

  const retuenData = await $api(`/qt/supplier/delete/${idFornitore.value}`, {
    method: 'delete',
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true

  deleteDialog.value = false
  loadingPage.value = false
}
</script>

<template>
  <VSnackbar
    v-model="notificaVisible"
    :color="color"
    location="top"
  >
    {{ t(message) }}
  </VSnackbar>
  <VCardTitle class="text-h5">
    {{ t('Label.Fornitore') }}
  </VCardTitle>

  <VRow>
    <VCol
      md="3"
      cols="12"
    >
      <VSnackbar
        v-model="isSnackbarScrollReverseVisible"
        transition="scroll-y-reverse-transition"
        location="top central"
        :color="color"
      >
        {{ $t(message) }}
      </VSnackbar>
      <VCard class="mb-6">
        <VCardText>
          <p class="text-sm text-disabled">
            Info
          </p>

          <VList class="card-list text-medium-emphasis">
            <VListItem>
              <VListItemTitle>
                <span class="d-flex align-center">
                  <VIcon
                    icon="tabler-point"
                    size="24"
                    class="me-2"
                  />
                  <div class="text-body-1 font-weight-medium me-2">{{ t('Label.Fornitore') }}:</div>
                  <div>{{ item.ragioneSociale }}</div>
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <span class="d-flex align-center">
                  <VIcon
                    icon="tabler-point"
                    size="24"
                    class="me-2"
                  />
                  <div class="text-body-1 font-weight-medium me-2">{{ t('Label.Codice-Sap') }}:</div>
                  <div>{{ item.codiceSap }}</div>
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <span class="d-flex align-center">
                  <VIcon
                    icon="tabler-point"
                    size="24"
                    class="me-2"
                  />
                  <div class="text-body-1 font-weight-medium me-2">{{ t('Label.Qualificato') }}:</div>
                  <VChip
                    :color="resolverApprovato(item.qualificato).color"
                    variant="elevated"
                  >
                    {{ t(resolverApprovato(item.qualificato).label) }}
                  </VChip>

                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <span class="d-flex align-center">
                  <VIcon
                    icon="tabler-point"
                    size="24"
                    class="me-2"
                  />
                  <div class="text-body-1 font-weight-medium me-2">{{ t('Label.Email') }}:</div>
                  <div>{{ item.email }}</div>
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <span class="d-flex align-center">
                  <VIcon
                    icon="tabler-point"
                    size="24"
                    class="me-2"
                  />
                  <div class="text-body-1 font-weight-medium me-2">{{ t('Label.Citta') }}:</div>
                  <div>{{ item.citta }}</div>
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <span class="d-flex align-center">
                  <VIcon
                    icon="tabler-point"
                    size="24"
                    class="me-2"
                  />
                  <div class="text-body-1 font-weight-medium me-2">{{ t('Label.Indirizzo') }}:</div>
                  <div>{{ `${item.citta}, ${item.cap}` }}</div>
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <span class="d-flex align-center">
                  <VIcon
                    icon="tabler-point"
                    size="24"
                    class="me-2"
                  />
                  <div class="text-body-1 font-weight-medium me-2">{{ t('Label.Nazione') }}:</div>
                  <div>{{ item.nazione }}</div>
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <span class="d-flex align-center">
                  <VIcon
                    icon="tabler-point"
                    size="24"
                    class="me-2"
                  />
                  <div class="text-body-1 font-weight-medium me-2">{{ t('Label.Categoria') }}:</div>
                  <div>{{ item.categoria }}</div>
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <span class="d-flex align-center">
                  <VIcon
                    icon="tabler-point"
                    size="24"
                    class="me-2"
                  />
                  <div class="text-body-1 font-weight-medium me-2">{{ t('Label.Prezzo') }}:</div>
                  <div>{{ item.prezzo }}</div>
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <span class="d-flex align-center">
                  <VIcon
                    icon="tabler-point"
                    size="24"
                    class="me-2"
                  />
                  <div class="text-body-1 font-weight-medium me-2">{{ t('Label.Critico') }}:</div>
                  <VChip
                    :color="resolverCritico(item.critico).color"
                    variant="elevated"
                  >
                    {{ t(resolverCritico(item.critico).label) }}
                  </VChip>
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <span class="d-flex align-center">
                  <VIcon
                    icon="tabler-point"
                    size="24"
                    class="me-2"
                  />
                  <div class="text-body-1 font-weight-medium me-2">{{ t('Label.Servizio') }}:</div>
                  <div>{{ item.servizio }}</div>
                </span>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>
        <!-- 👉 Edit and Suspend button -->
        <VCardText class="d-flex justify-center gap-x-4">
          <VBtn
            variant="elevated"
            @click="editItem"
          >
            Edit
          </VBtn>
        </VCardText>
      </VCard>

      <VCard class="mb-6">
        <VCardText>
          <VBtn
            block
            color="warning"
            @click="approva"
            :disabled="!(item.servizio && item.prezzo)"
          >
            {{ item.qualificato === '1' ? t('Button.Non-Qualificato') : t('Button.Qualificato') }}
          </VBtn>
        </VCardText>
      </VCard>

      <VCard class="mb-6">
        <VCardText>
          <VBtn
            block
            @click="openDrivePage"
          >
            {{ t('Button.Cartella-Fornitore') }}
            <IconBtn>
              <VIcon icon="tabler-brand-google-drive" />
            </IconBtn>
          </VBtn>
        </VCardText>
      </VCard>

      <VCard class="mb-6">
        <VCardText>
          <VBtn
            color="error"
            block
            @click="openDeleted(item.id)"
          >
            {{ t('Button.Elimina-Fornitore') }}
            <IconBtn>
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
    <VCol
      cols="12"
      md="9"
      lg="9"
    >
      <VTabs
        v-model="fornitireTab"
        class="v-tabs-pill"
      >
        <VTab
          v-for="tab in tabs"
          :key="tab.icon"
        >
          <VIcon
            :size="18"
            :icon="tab.icon"
            class="me-1"
          />
          <span>{{ tab.title }}</span>
        </VTab>
      </VTabs>

      <VWindow
        v-model="fornitireTab"
        class="mt-6 disable-tab-transition"
        :touch="false"
      >
        <VWindowItem>
          <FornitoreTabRating />
        </VWindowItem>

        <VWindowItem />

        <VWindowItem>
          <FornitoreTabCertificazioni
            :fornitore-id="item.id"
            :key-tab="fornitireTab"
          />
        </VWindowItem>

        <VWindowItem>
          <FornitoreTabRating />
        </VWindowItem>

        <VWindowItem>
          <FornitoreTabRating />
        </VWindowItem>

        <VWindowItem>
          <FornitoreTabAvvisi
            :fornitore-id="item.id"
            :key-tab="fornitireTab"
          />
        </VWindowItem>

        <VWindowItem>
          <FornitoreTabUtenti
            :fornitore-id="item.id"
            :key-tab="fornitireTab"
          />
        </VWindowItem>

        <VWindowItem>
          <FornitoreTabLog
            :fornitore-id="item.id"
            :key-tab="fornitireTab"
          />
        </VWindowItem>
      </VWindow>
    </VCol>
  </VRow>

  <EditFornitore
    v-model:isDrawerOpen="editDialog"
    :fornitore="item"
    @fornitore="save"
  />

  <!-- 👉 Delete Dialog  -->
  <VDialog
    v-model="deleteDialog"
    max-width="500px"
  >
    <VCard>
      <VCardTitle>
        Sei sicuro di voler eliminare?
      </VCardTitle>

      <VCardActions>
        <VSpacer/>

        <VBtn
          color="error"
          variant="outlined"
          @click="closeDelete"
        >
          Cancel
        </VBtn>

        <VBtn
          color="success"
          variant="elevated"
          @click="deleteItemConfirm"
        >
          OK
        </VBtn>

        <VSpacer/>
      </VCardActions>
    </VCard>
  </VDialog>

  <LoadingStandBy v-model="loadingPage" />
</template>

<style scoped lang="scss">

</style>
