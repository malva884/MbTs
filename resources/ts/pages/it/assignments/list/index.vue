<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { createUrl } from '@/@core/composable/createUrl'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import QRCodeVue3 from 'qrcode-vue3'

const { t } = useI18n()

const serverItems = ref([])
const loading = ref(true)
const totalItems = ref(0)
const assetFilter = ref('')
const employeeFilter = ref('')
const statusFilter = ref('')
const page = ref(1)
const itemsPerPage = ref(25)
const sortBy = ref(['assigned_at'])
const orderBy = ref('desc')

const showLabelDialog = ref(false)
const labelHtml = ref('')
const labelAsset = ref<any>(null)

const showCreateDialog = ref(false)
const createLoading = ref(false)
const asset_id = ref('')
const assignmentType = ref('employee')
const employee_id = ref('')
const machine_id = ref('')
const assigned_quantity = ref(1)
const notes = ref('')
const barcodeInput = ref('')
const barcodeInputRef = ref<HTMLInputElement | null>(null)

const assets = ref([])
const employees = ref([])
const machines = ref([])

const headers = computed(() => [
  { title: t('IT.Asset.SerialNumber'), key: 'asset.serial_number', sortable: false },
  { title: t('IT.Asset.AssetTag'), key: 'asset.asset_tag', sortable: false },
  { title: t('IT.Assignment.AssignTo'), key: 'assignable', sortable: false },
  { title: t('IT.Assignment.AssignedQuantity'), key: 'assigned_quantity', sortable: true },
  { title: t('IT.Assignment.AssignedAt'), key: 'assigned_at', sortable: true },
  { title: t('IT.Assignment.Status'), key: 'status', sortable: true },
  { title: t('Label.Azioni'), key: 'actions', sortable: false },
])

const statusOptions = [
  { title: t('IT.Assignment.Status.Active'), value: 'Active' },
  { title: t('IT.Assignment.Status.Returned'), value: 'Returned' },
]

const resolveStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    Active: 'success',
    Returned: 'secondary',
  }
  return colors[status] || 'primary'
}

const fetchItems = async () => {
  loading.value = true
  try {
    const { data } = await useApi<any>(createUrl('/it/assignments', {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        asset_id: assetFilter.value,
        employee_id: employeeFilter.value,
        status: statusFilter.value,
      },
    }))
    serverItems.value = data.value?.data ?? []
    totalItems.value = data.value?.total ?? 0
  } catch (error) {
    console.error('Error fetching assignments:', error)
  } finally {
    loading.value = false
  }
}

const returnAsset = async (id: string) => {
  const quantity = prompt(t('IT.Assignment.ReturnQuantity'))
  if (quantity) {
    try {
      await $api(`/it/assignments/return/${id}`, {
        method: 'POST',
        body: {
          returned_quantity: parseInt(quantity),
        },
      })
      fetchItems()
    } catch (error) {
      console.error('Error returning asset:', error)
    }
  }
}

const printLabel = () => {
  if (!labelAsset.value) return

  const asset = labelAsset.value
  const qrValue = asset.serial_number || asset.asset_tag || asset.id

  const printWindow = window.open('', '_blank')
  printWindow.document.write(`
    <html>
      <head>
        <title>Stampa Etichetta</title>
        <style>
          @media print {
            body { margin: 0; }
          }
          .hero-image {
            height: 188.97637795px;
            width: 264.56692913px;
            background-color: white;
            color: black;
          }
          .label-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
          }
          .label-left {
            flex: 0 0 60%;
          }
          .label-right {
            flex: 0 0 40%;
            display: flex;
            justify-content: flex-end;
          }
          .logo-stl {
            max-width: 100%;
            height: auto;
          }
          .serial-text {
            font-weight: 500;
            font-size: 0.700rem;
            margin: 0.5rem 0 0 0;
          }
          .asset-text {
            font-weight: 500;
            font-size: 0.700rem;
            margin: 0;
          }
          .qr-code {
            width: 90px;
            height: 90px;
          }
          .label-footer {
            text-align: center;
            margin-top: 0.5rem;
          }
          .phone-text {
            font-weight: 500;
            font-size: 1.100rem;
            margin: 0;
          }
          .logo-mb {
            height: 30px;
            width: 300px;
            max-width: 100%;
          }
        </style>
      </head>
      <body onload="window.print(); window.close();">
        <div class="hero-image">
          <div class="label-row">
            <div class="label-left">
              <img src="/images/custom/logo_stl.jpg" alt="Logo STL" class="logo-stl" />
              <p class="serial-text">S.no ${asset.serial_number || 'N/A'}</p>
              <p class="asset-text">ITALY ${asset.asset_tag || 'N/A'}</p>
            </div>
            <div class="label-right">
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=${qrValue}" alt="QR Code" class="qr-code" />
            </div>
          </div>
          <div class="label-footer">
            <p class="phone-text">+39 - 030 - 9771911</p>
            <img src="/images/custom/logo_mb.png" alt="Logo MB" class="logo-mb" />
          </div>
        </div>
      </body>
    </html>
  `)
  printWindow.document.close()
}

const printAssetLabel = async (assetId: string) => {
  try {
    const assetResponse = await $api('/it/assets/' + assetId)
    labelAsset.value = assetResponse
    showLabelDialog.value = true
  } catch (error) {
    console.error('Error fetching asset for label:', error)
  }
}

const assetOptions = computed(() => assets.value.map(asset => ({
  title: asset.serial_number || asset.asset_tag || asset.id,
  value: asset.id,
})))

const employeeOptions = computed(() => employees.value.map(employee => ({
  ...employee,
  title: employee.titolo,
  value: employee.id,
})))

const machineOptions = computed(() => machines.value.map(machine => ({
  title: `${machine.code} - ${machine.name}`,
  value: machine.id,
})))

const fetchAssets = async () => {
  try {
    const { data } = await useApi<any>(createUrl('/it/assets', {
      query: { status: 'Available', itemsPerPage: 1000 },
    }))
    assets.value = data.value?.data ?? []
  } catch (error) {
    console.error('Error fetching assets:', error)
  }
}

const fetchEmployees = async () => {
  try {
    const { data: resultDataEmployees } = await useApi<any>('/hr/dipendenti/get_dipendenti')
    employees.value = resultDataEmployees.value ?? []
  } catch (error) {
    console.error('Error fetching employees:', error)
  }
}

const fetchMachines = async () => {
  try {
    const { data } = await useApi<any>('/it/machines')
    machines.value = data.value?.data ?? []
  } catch (error) {
    console.error('Error fetching machines:', error)
  }
}

const openCreateDialog = () => {
  asset_id.value = ''
  assignmentType.value = 'employee'
  employee_id.value = ''
  machine_id.value = ''
  assigned_quantity.value = 1
  notes.value = ''
  barcodeInput.value = ''
  showCreateDialog.value = true
  nextTick(() => {
    barcodeInputRef.value?.focus()
  })
}

const onBarcodeScan = async () => {
  const code = barcodeInput.value.trim()
  if (!code) return

  // Search for asset by serial_number or asset_tag in the local list
  const asset = assets.value.find(a =>
    a.serial_number?.toLowerCase() === code.toLowerCase() ||
    a.asset_tag?.toLowerCase() === code.toLowerCase()
  )

  if (asset) {
    asset_id.value = asset.id
    barcodeInput.value = ''
    return
  }

  // If not found locally, search via API among available assets
  try {
    const { data } = await useApi<any>(createUrl('/it/assets', {
      query: { search: code, status: 'Available', itemsPerPage: 1 },
    }))
    if (data.value?.data && data.value.data.length > 0) {
      const foundAsset = data.value.data[0]
      assets.value.push(foundAsset)
      asset_id.value = foundAsset.id
      barcodeInput.value = ''
    }
    else {
      alert(t('IT.Asset.NotFound'))
      barcodeInput.value = ''
    }
  }
  catch (error) {
    console.error('Error searching asset:', error)
  }
}

const createAssignment = async () => {
  if (!asset_id.value) {
    alert(assets.value.length === 0
      ? 'Nessun asset disponibile: crea almeno un asset con stato Available.'
      : 'Seleziona un asset.')
    return
  }

  const assignableId = assignmentType.value === 'employee' ? employee_id.value : machine_id.value
  if (!assignableId) {
    alert(assignmentType.value === 'employee' ? 'Seleziona un dipendente.' : 'Seleziona una macchina.')
    return
  }

  const qty = Number(assigned_quantity.value)
  if (Number.isNaN(qty) || qty < 1) {
    alert('La quantità assegnata deve essere un numero maggiore di 0.')
    return
  }

  createLoading.value = true
  try {
    const response = await $api('/it/assignments/store', {
      method: 'POST',
      body: {
        asset_id: asset_id.value,
        assignable_type: assignmentType.value === 'employee' ? 'App\\Models\\HrEmployee' : 'App\\Models\\ItMachine',
        assignable_id: assignableId,
        assigned_quantity: qty,
        notes: notes.value,
      },
    })

    showCreateDialog.value = false
    fetchItems()
  } catch (error: any) {
    const messages = error?.data?.errors
      ? Object.values(error.data.errors).flat().join('\n')
      : (error?.data?.message || error?.message)

    console.error('Error creating assignment:', error)
    alert(messages || 'Errore durante il salvataggio.')
  } finally {
    createLoading.value = false
  }
}

fetchAssets()
fetchEmployees()
fetchMachines()
fetchItems()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-clipboard-list" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ t('IT.Assignments') }}</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} assegnazioni registrate</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="openCreateDialog"
          >
            {{ t('Label.Add') }}
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <VCol cols="12" sm="4">
            <VTextField
              v-model="assetFilter"
              :label="t('IT.Asset')"
              prepend-inner-icon="tabler-search"
              clearable
              clear-icon="tabler-x"
              @keyup.enter="fetchItems"
              @click:clear="fetchItems"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <VTextField
              v-model="employeeFilter"
              :label="t('HR.Employee')"
              prepend-inner-icon="tabler-search"
              clearable
              clear-icon="tabler-x"
              @keyup.enter="fetchItems"
              @click:clear="fetchItems"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <VSelect
              v-model="statusFilter"
              :label="t('IT.Assignment.Status')"
              :items="statusOptions"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="fetchItems"
              @click:clear="fetchItems"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        v-model:sort-by="sortBy"
        v-model:sort-desc="orderBy"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        density="comfortable"
        hover
        @update:options="fetchItems"
      >
      <template #item.status="{ item }">
        <VChip :color="resolveStatusColor(item.status)" size="small">
          {{ item.status }}
        </VChip>
      </template>
      <template #item.assigned_at="{ item }">
        {{ item.assigned_at ? new Date(item.assigned_at).toLocaleDateString('it-IT') : 'N/A' }}
      </template>
      <template #item.assignable="{ item }">
        <div v-if="item.assignable">
          <VChip size="small" :color="item.assignable_type === 'App\\Models\\ItMachine' ? 'info' : 'primary'" class="mb-1">
            {{ item.assignable_type === 'App\\Models\\ItMachine' ? t('IT.Machine') : t('HR.Employee') }}
          </VChip>
          <div class="text-caption">
            {{ item.assignable_type === 'App\\Models\\ItMachine' ? item.assignable.code + ' - ' + item.assignable.name : item.assignable.nome_completo }}
          </div>
        </div>
        <div v-else-if="item.employee">
          <VChip size="small" color="primary" class="mb-1">
            {{ t('HR.Employee') }}
          </VChip>
          <div class="text-caption">
            {{ item.employee.nome_completo }}
          </div>
        </div>
      </template>
      <template #item.actions="{ item }">
        <VBtn
          icon
          size="small"
          color="primary"
          @click="printAssetLabel(item.asset.id)"
        >
          <VIcon icon="tabler-printer" />
        </VBtn>
        <VBtn
          v-if="item.status === 'Active'"
          icon
          size="small"
          color="warning"
          @click="returnAsset(item.id)"
        >
          <VIcon icon="tabler-arrow-back-up" />
        </VBtn>
      </template>
    </VDataTableServer>
    </VCard>
  </div>

  <VDialog v-model="showLabelDialog" max-width="500">
    <VCard>
      <VCardTitle>Stampa Etichetta</VCardTitle>
      <VCardText>
        <div v-if="labelAsset" class="hero-image">
          <div class="label-row">
            <div class="label-left">
              <img src="/images/custom/logo_stl.jpg" alt="Logo STL" class="logo-stl" />
              <p class="serial-text">S.no {{ labelAsset.serial_number || 'N/A' }}</p>
              <p class="asset-text">ITALY {{ labelAsset.asset_tag || 'N/A' }}</p>
            </div>
            <div class="label-right">
              <QRCodeVue3
                :value="labelAsset.serial_number || labelAsset.asset_tag || labelAsset.id"
                :width="90"
                :height="90"
                :qr-options="{ typeNumber: 0, mode: 'Byte', errorCorrectionLevel: 'H' }"
                :image-options="{ hideBackgroundDots: true, imageSize: 0.4, margin: 0 }"
                :dots-options="{
                  type: 'dots',
                  color: '#000000',
                  gradient: {
                    type: 'linear',
                    rotation: 0,
                    colorStops: [
                      { offset: 0, color: '#000000' },
                      { offset: 1, color: '#000000' },
                    ],
                  },
                }"
                :background-options="{ color: '#ffffff' }"
                :corners-square-options="{ type: 'dot', color: '#000000' }"
                :corners-dot-options="{ type: undefined, color: '#000000' }"
              />
            </div>
          </div>
          <div class="label-footer">
            <p class="phone-text">+39 - 030 - 9771911</p>
            <img src="/images/custom/logo_mb.png" alt="Logo MB" class="logo-mb" />
          </div>
        </div>
      </VCardText>
      <VCardActions>
        <VBtn @click="printLabel">Stampa</VBtn>
        <VBtn color="primary" @click="showLabelDialog = false">Chiudi</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VDialog v-model="showCreateDialog" max-width="600">
    <VCard>
      <VCardText class="d-flex align-center justify-space-between py-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-clipboard-plus" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ t('IT.Assignment.Create') }}</div>
            <div class="text-caption text-medium-emphasis">Nuova assegnazione asset</div>
          </div>
        </div>
        <VBtn icon="tabler-x" variant="text" @click="showCreateDialog = false" />
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VForm @submit.prevent="createAssignment">
          <VRow>
            <VCol cols="12">
              <VTextField
                ref="barcodeInputRef"
                v-model="barcodeInput"
                :label="t('IT.Asset.ScanBarcode')"
                placeholder="Scan barcode or select from list"
                @keydown.enter.prevent="onBarcodeScan"
                clearable
                clear-icon="tabler-x"
              />
            </VCol>

            <VCol cols="12">
              <VSelect
                v-model="asset_id"
                :label="t('IT.Asset')"
                :items="assetOptions"
                item-title="title"
                item-value="value"
                required
                clearable
                clear-icon="tabler-x"
              />
            </VCol>

            <VCol cols="12">
              <VRadioGroup v-model="assignmentType" inline>
                <VRadio :label="t('IT.Assignment.AssignToEmployee')" value="employee" />
                <VRadio :label="t('IT.Assignment.AssignToMachine')" value="machine" />
              </VRadioGroup>
            </VCol>

            <VCol cols="12">
              <VSelect
                v-if="assignmentType === 'employee'"
                v-model="employee_id"
                :label="t('HR.Employee')"
                :items="employeeOptions"
                item-title="title"
                item-value="value"
                required
                clearable
                clear-icon="tabler-x"
              />
              <VSelect
                v-else
                v-model="machine_id"
                :label="t('IT.Machine')"
                :items="machineOptions"
                item-title="title"
                item-value="value"
                required
                clearable
                clear-icon="tabler-x"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="assigned_quantity"
                :label="t('IT.Assignment.AssignedQuantity')"
                type="number"
                min="1"
                required
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="notes"
                :label="t('IT.Assignment.Notes')"
                rows="3"
              />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12">
              <VBtn
                type="submit"
                color="primary"
                :loading="createLoading"
                block
              >
                {{ t('Label.Save') }}
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style>
.hero-image {
  height: 188.97637795px;
  width: 264.56692913px;
  background-color: white;
  color: black;
}
.label-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.label-left {
  flex: 0 0 60%;
}
.label-right {
  flex: 0 0 40%;
  display: flex;
  justify-content: flex-end;
}
.logo-stl {
  max-width: 100%;
  height: auto;
}
.serial-text {
  font-weight: 500;
  font-size: 0.700rem;
  margin: 0.5rem 0 0 0;
}
.asset-text {
  font-weight: 500;
  font-size: 0.700rem;
  margin: 0;
}
.qr-code {
  width: 90px;
  height: 90px;
}
.label-footer {
  text-align: center;
  margin-top: 0.5rem;
}
.phone-text {
  font-weight: 500;
  font-size: 1.100rem;
  margin: 0;
}
.logo-mb {
  height: 30px;
  width: 300px;
  max-width: 100%;
}
</style>
