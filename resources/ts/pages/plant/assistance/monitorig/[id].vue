<script setup lang="ts">

import {useI18n} from 'vue-i18n'
import {VDataTableServer} from "vuetify/labs/VDataTable";

definePage({
  meta: {
    action: 'list',
    subject: 'It-Assistenza',
  },
})

const route = useRoute('target-list-id')

const {t} = useI18n()
const itemsPerPage = ref(10)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const loading = ref(false)
const monitoringItems = ref<any>([])

const loadItems = async () => {
  loading.value = true
  const { data: resultData } = await useApi<any>(createUrl(`/pl/monitoring/list_categoria/${route.params.id}`, {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
    },
  }))

  if (resultData.value !== null) {
    monitoringItems.value = resultData.value
  }
  else {
    monitoringItems.value = []
  }
  loading.value = false
}

const formatDate = (value: string, formatting: Intl.DateTimeFormatOptions = {
  year: '2-digit',
  month: 'numeric',
  day: 'numeric',
  hour: 'numeric',
  minute: 'numeric',
}) => {
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
    <VCol cols="10">
      <VCard :title="route.params.id">
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
        <VTable class="text-no-wrap">
          <thead>
          <tr>
            <th>
              {{$t('Table.Data')}}
            </th>
            <th>
              {{$t('Table.Host-Name')}}
            </th>
            <th>
              {{$t('Table.Titolo')}}
            </th>
            <th>
              {{$t('Table.Gp')}}
            </th>
            <th>
              {{$t('Table.Stl-App')}}
            </th>
            <th>
              {{$t('Table.Portale')}}
            </th>
            <th>
              {{$t('Table.Dc')}}
            </th>
            <th>
              {{$t('Table.OTDR')}}
            </th>
            <th>
              {{$t('Table.Google')}}
            </th>
          </tr>
          </thead>

          <tbody>
          <tr
            v-for="item in monitoringItems"
            :key="item.RowNum"
          >
            <td>
              {{ formatDate(item.data) }}
              </td>
            <td>
              <RouterLink
                :to="{ name: 'plant-asset-view-id', params: { id: item.asset_id } }"
                target="_blank"
                class="font-weight-medium text-link"
              >
                {{ item.hostname }}
              </RouterLink>
            </td>
            <td>
              {{ item.tipo_log }}
            </td>
            <td>
              <div
                v-if="item.gp_stato === 'Connesso'"
                class="d-flex gap-1"
              >
                <VIcon
                  color="success"
                  icon="tabler-wifi"
                />
              </div><div
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
            </td>
            <td>
              <div
                v-if="item.stl_app === 'Connesso'"
                class="d-flex gap-1"
              >
                <VIcon
                  color="success"
                  icon="tabler-wifi"
                />
              </div><div
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
            </td>
            <td>
              <div
                v-if="item.portale_stato === 'Connesso'"
                class="d-flex gap-1"
              >
                <VIcon
                  color="success"
                  icon="tabler-wifi"
                />
              </div><div
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
            </td>
            <td>
              <div
                v-if="item.dc_stato === 'Connesso'"
                class="d-flex gap-1"
              >
                <VIcon
                  color="success"
                  icon="tabler-wifi"
                />
              </div><div
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
            </td>
            <td>
              <div
                v-if="item.ip_uno_stato === 'Connesso'"
                class="d-flex gap-1"
              >
                <VIcon
                  color="success"
                  icon="tabler-wifi"
                />
              </div><div
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
            </td>
            <td>
              <div
                v-if="item.ip_cinque_stato === 'Connesso'"
                class="d-flex gap-1"
              >
                <VIcon
                  color="success"
                  icon="tabler-wifi"
                />
              </div><div
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
            </td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped lang="scss">

</style>
