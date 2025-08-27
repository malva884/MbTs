<script setup lang="ts">

import {useI18n} from "vue-i18n";
import {VDataTableServer} from "vuetify/labs/VDataTable";

interface Props {
  id: number
}

const props = defineProps<Props>()

interface AssetMonitoring {
  id: string | null
  asset_id: string | null
  data: string | null
  tipo_log: string | null
  hostname: string | null
  gp_stato: string | null
  stl_app: string | null
  portale_stato: string | null
  dc_stato: string | null
  ip_uno_stato: string | null
  ip_due_stato: string | null
  ip_tre_stato: string | null
  ip_quatro_stato: string | null
  ip_cinque_stato: string | null
}

const { t } = useI18n()
const itemsPerPage = ref(10)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const loading = ref(false)
const monitoringItems = ref<any>([])

const headers = [
  { title: t('Table.Data'), key: 'data' },
  { title: t('Table.Titolo'), key: 'tipo_log' },
  { title: t('Table.Gp'), key: 'gp_stato', sortable: false },
  { title: t('Table.Stl-App'), key: 'stl_app', sortable: false },
  { title: t('Table.Portale'), key: 'portale_stato', sortable: false },
  { title: t('Table.Dc'), key: 'dc_stato', sortable: false },
  { title: t('Table.OTDR'), key: 'ip_uno_stato', sortable: false },
  //{ title: t('Table.Ip-Due'), key: 'ip_due_stato', sortable: false },
  //{ title: t('Table.Ip-Tre'), key: 'ip_tre_stato', sortable: false },
  //{ title: t('Table.Ip-Quatro'), key: 'ip_quatro_stato', sortable: false },
  { title: t('Table.Google'), key: 'ip_cinque_stato', sortable: false },
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

  const { data: resultData } = await useApi<any>(createUrl(`/pl/monitoring/list/${props.id}`, {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
    },
  }))

  if (resultData.value !== null) {
    monitoringItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  }
  else {
    monitoringItems.value = []
    totalItems.value = 0
  }
  loading.value = false
}

const formatDate = (value: string, formatting: Intl.DateTimeFormatOptions = { year: '2-digit', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric' }) => {
  if (!value)
    return value

  return new Intl.DateTimeFormat('it-IT', formatting).format(new Date(value))
}

setInterval(loadItems, 65000)
onMounted(() => {
  loadItems()
})
</script>

<template>
  <VRow>
    <VCol cols="7">
      <VCard>
        <VCardText>
          <VRow>
            <VCol
              md="4"
            >
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
          :items="monitoringItems"
          :items-length="totalItems"
          :loading="loading"
          density="compact"
          @update:options="updateOptions"
        >
          <!-- date -->
          <template #item.data="{ item }">
            {{ formatDate(item.data) }}
          </template>

          <template #item.gp_stato="{ item }">
            <div
              v-if="item.gp_stato === 'Connesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-wifi"
              />
            </div>
            <div
              v-else-if="item.gp_stato === 'Disconnesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="warning"
                icon="tabler-wifi-off"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>

          <template #item.stl_app="{ item }">
            <div
              v-if="item.stl_app === 'Connesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-wifi"
              />
            </div>
            <div
              v-else-if="item.stl_app === 'Disconnesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="warning"
                icon="tabler-wifi-off"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>

          <template #item.portale_stato="{ item }">
            <div
              v-if="item.portale_stato === 'Connesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-wifi"
              />
            </div>
            <div
              v-else-if="item.portale_stato === 'Disconnesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="warning"
                icon="tabler-wifi-off"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>

          <template #item.dc_stato="{ item }">
            <div
              v-if="item.dc_stato === 'Connesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-wifi"
              />
            </div>
            <div
              v-else-if="item.dc_stato === 'Disconnesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="warning"
                icon="tabler-wifi-off"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>

          <template #item.ip_uno_stato="{ item }">
            <div
              v-if="item.ip_uno_stato === 'Connesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-wifi"
              />
            </div>
            <div
              v-else-if="item.ip_uno_stato === 'Disconnesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="warning"
                icon="tabler-wifi-off"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>

          <template #item.ip_due_stato="{ item }">
            <div
              v-if="item.ip_due_stato === 'Connesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-wifi"
              />
            </div>
            <div
              v-else-if="item.ip_due_stato === 'Disconnesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="warning"
                icon="tabler-wifi-off"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>

          <template #item.ip_tre_stato="{ item }">
            <div
              v-if="item.ip_tre_stato === 'Connesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-wifi"
              />
            </div>
            <div
              v-else-if="item.ip_tre_stato === 'Disconnesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="warning"
                icon="tabler-wifi-off"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>

          <template #item.ip_quatro_stato="{ item }">
            <div
              v-if="item.ip_quatro_stato === 'Connesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-wifi"
              />
            </div>
            <div
              v-else-if="item.ip_quatro_stato === 'Disconnesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="warning"
                icon="tabler-wifi-off"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>

          <template #item.ip_cinque_stato="{ item }">
            <div
              v-if="item.ip_cinque_stato === 'Connesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-wifi"
              />
            </div>
            <div
              v-else-if="item.ip_cinque_stato === 'Disconnesso'"
              class="d-flex gap-1"
            >
              <VIcon
                color="warning"
                icon="tabler-wifi-off"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped lang="scss">

</style>
