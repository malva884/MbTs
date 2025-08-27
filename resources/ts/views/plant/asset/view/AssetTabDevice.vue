<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { ref } from 'vue'
import moment from 'moment'

interface Props {
  id: number
}

const props = defineProps<Props>()

const { t } = useI18n()
const itemsPerPage = ref(10)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const loading = ref(false)
const dialogNota = ref(false)
const items = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const itemEdit = ref({})

const headers = [
  { title: t('Table.Device'), key: 'descrizione' },
  { title: t('Table.Quantita'), key: 'quantita' },
  { title: t('Table.Stato'), key: 'stato' },
  { title: t('Table.Tipologia'), key: 'tipologia' },
  { title: t('Table.Descrizione'), key: 'descrizione_log' },
  { title: t('Table.Data'), key: 'created_at' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl(`/pl/asset/devices/${props.id}`, {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
    },
  }))

  if (resultData.value !== null) {
    items.value = resultData.value.data
    totalItems.value = resultData.value.total
  }
  else {
    items.value = []
    totalItems.value = 0
  }
  loading.value = false
}

const deviceBroken = async (idDevice: string) => {
  const retuenData = await $api(`/pl/warehouse/deviceBroken/${idDevice}`, {
    method: 'POST',
    body: {
      deviceId: idDevice,
    },
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
  loadItems()
}

const deviceReturned = async (idDevice: string) => {
  const retuenData = await $api(`/pl/warehouse/deviceReturned/${idDevice}`, {
    method: 'POST',
    body: {
      deviceId: idDevice,
    },
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
  loadItems()
}

const editNota = (obj: any) => {
  itemEdit.value = obj
  dialogNota.value = true
  console.log(itemEdit.value.descrizione_log)
}

const saveNota = async () => {
  const retuenData = await $api(`/pl/warehouse/deviceNota/${itemEdit.value.id}`, {
    method: 'POST',
    body: {
      descrizione: itemEdit.value.descrizione_log,
    },
  })

  dialogNota.value = false
  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
  loadItems()
}

function formatDate(date: string): string {
  return moment(String(date)).format('YYYY-MM-DD')
}
</script>

<template>
  <VRow>
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
    <VCol cols="12">
      <VCard>
        <VCardText>
          <VRow>
            <VCol md="4">
              <AppTextField
                v-model="search"
                placeholder="Search ..."
                append-inner-icon="tabler-search"
                single-line
                hide-details
                dense
                outlined
                density="compact"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          :headers="headers"
          :items="items"
          :items-length="totalItems"
          :loading="loading"
          density="compact"
          @update:options="updateOptions"
        >
          <!-- date -->
          <template #item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>

          <template #item.stato="{ item }">
            <div
              v-if="item.ritirato === '1'"
              class="d-flex gap-1"
            >
              <VIcon
                color="warning"
                icon="tabler-registered"
              />
            </div>
            <div
              v-else-if="item.dismesso === '1'"
              class="d-flex gap-1"
            >
              <VIcon
                color="error"
                icon="tabler-trash"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-thumb-up"
              />
            </div>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <IconBtn
                v-if="item.ritirato === '0' && item.dismesso === '0'"
                color="primary"
                @click="editNota(item)"
              >
                <VIcon
                  icon="tabler-edit"
                  :title="$t('Label.Nota')"
                />
              </IconBtn>

              <IconBtn
                v-if="item.ritirato === '0' && item.dismesso === '0' && (item.tipologia !== 'Laptop' && item.tipologia !== 'Desktop')"
                color="warning"
                @click="deviceReturned(item.id)"
              >
                <VIcon
                  icon="tabler-registered"
                  :title="$t('Label.Ritira')"
                />
              </IconBtn>

              <IconBtn
                v-if="item.ritirato === '0' && item.dismesso === '0' && (item.tipologia !== 'Laptop' && item.tipologia !== 'Desktop')"
                color="error"
                @click="deviceBroken(item.id)"
              >
                <VIcon
                  icon="tabler-trash"
                  :title="$t('Label.Guasto')"
                />
              </IconBtn>
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

  <VDialog
    v-model="dialogNota"
    max-width="600"
    persisten
  >

    <!-- Dialog close btn -->
    <DialogCloseBtn @click="dialogNota = !dialogNota" />

    <!-- Dialog Content -->
    <VCard :title="$t('Label.Nota')">
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            sm="12"
            md="12"
          >
            <AppTextField
              v-model="itemEdit.descrizione_log"
              :label="$t('Label.Nota')"
              :placeholder="$t('Label.Nota')"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="dialogNota = false"
        >
          Close
        </VBtn>
        <VBtn @click="saveNota">
          Save
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped lang="scss">

</style>
