<script setup lang="ts">
import { VDataTable } from 'vuetify/labs/VDataTable'
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import moment from 'moment'

const route = useRoute()
const { t } = useI18n()

const asset = ref<any>(null)
const loading = ref(true)

const networkDeviceDialog = ref(false)
const networkDeviceForm = ref({
  id: null as string | null,
  asset_id: '',
  ip_address: '',
  mac_address: '',
  device_type: 'Other',
  location: '',
  rack_position: '',
  vlan: '',
  subnet: '',
  notes: '',
  disabled: false,
  monitor_enabled: false,
})
const networkDeviceLoading = ref(false)

const formattedAssignments = computed(() => {
  if (!asset.value?.assignments) return []
  const formatted = asset.value.assignments.map((assignment: any) => {
    const assignedBy = assignment.assigned_by_user as any
    let assignedByName = '--'
    if (assignedBy) {
      try {
        assignedByName = assignedBy.full_name || `${assignedBy.nome || ''} ${assignedBy.cognome || ''}`.trim() || '--'
      }
      catch (e) {
        console.error('Error accessing assigned_by_user:', e)
      }
    }
    return {
      ...assignment,
      assignable_display: assignment.assignable_type === 'App\\Models\\HrEmployee'
        ? `${assignment.assignable?.nome || ''} ${assignment.assignable?.cognome || ''}`.trim() || '--'
        : assignment.assignable_type === 'App\\Models\\ItMachine'
          ? assignment.assignable?.name || '--'
          : '--',
      assigned_by_display: assignedByName,
    }
  })
  return formatted
})

const formattedTransactions = computed(() => {
  if (!asset.value?.transactions) return []
  const formatted = asset.value.transactions.map((transaction: any) => {
    const performedBy = transaction.performed_by_user as any
    return {
      ...transaction,
      performed_by_display: performedBy?.full_name || performedBy?.nome ? `${performedBy.nome || ''} ${performedBy.cognome || ''}`.trim() : '--',
    }
  })
  return formatted
})

const openNetworkDeviceDialog = (isEdit = false) => {
  if (isEdit && asset.value?.network_device) {
    networkDeviceForm.value = {
      id: asset.value.network_device.id,
      asset_id: asset.value.network_device.asset_id,
      ip_address: asset.value.network_device.ip_address || '',
      mac_address: asset.value.network_device.mac_address || '',
      device_type: asset.value.network_device.device_type || 'Other',
      location: asset.value.network_device.location || '',
      rack_position: asset.value.network_device.rack_position || '',
      vlan: asset.value.network_device.vlan || '',
      subnet: asset.value.network_device.subnet || '',
      notes: asset.value.network_device.notes || '',
      disabled: !!asset.value.network_device.disabled,
      monitor_enabled: !!asset.value.network_device.monitor_enabled,
    }
  } else {
    networkDeviceForm.value = {
      id: null,
      asset_id: asset.value?.id || '',
      ip_address: '',
      mac_address: '',
      device_type: 'Other',
      location: '',
      rack_position: '',
      vlan: '',
      subnet: '',
      notes: '',
      disabled: false,
      monitor_enabled: false,
    }
  }
  networkDeviceDialog.value = true
}

const saveNetworkDevice = async () => {
  networkDeviceLoading.value = true
  try {
    const formData = {
      ...networkDeviceForm.value,
      monitor_enabled: networkDeviceForm.value.monitor_enabled,
      disabled: networkDeviceForm.value.disabled,
    }

    if (networkDeviceForm.value.id) {
      await $api(`/it/network-devices/update/${networkDeviceForm.value.id}`, {
        method: 'POST',
        body: formData,
      })
    } else {
      await $api('/it/network-devices/store', {
        method: 'POST',
        body: formData,
      })
    }
    networkDeviceDialog.value = false
    await fetchAsset()
  } catch (e) {
    console.error('Error saving network device:', e)
    alert('Error: ' + JSON.stringify(e))
  } finally {
    networkDeviceLoading.value = false
  }
}

const deleteNetworkDevice = async () => {
  if (!asset.value?.network_device?.id) return
  if (!confirm('Are you sure you want to delete this network device?')) return

  networkDeviceLoading.value = true
  try {
    await $api(`/it/network-devices/${asset.value.network_device.id}`, {
      method: 'DELETE',
    })
    await fetchAsset()
  } catch (e) {
    console.error('Error deleting network device:', e)
    alert('Error: ' + JSON.stringify(e))
  } finally {
    networkDeviceLoading.value = false
  }
}

const isSupplierDialogVisible = ref(false)
const suppliers = ref([])

const supplierFormData = ref({
  supplier_id: '',
  unit_cost: '',
  purchase_date: '',
  order_reference: '',
  product_link: '',
  notes: '',
})

const resolveStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    'Available': 'success',
    'Assigned': 'primary',
    'In Repair': 'warning',
    'Retired': 'secondary',
    'Lost': 'error',
  }

  return colors[status] || 'secondary'
}

const resolveTransactionColor = (type: string) => {
  const colors: Record<string, string> = {
    In: 'success',
    Out: 'warning',
    Transfer: 'info',
    Maintenance: 'warning',
    Return: 'success',
    Retire: 'secondary',
  }

  return colors[type] || 'secondary'
}

const formatDate = (date: string) => {
  if (!date)
    return '--'

  return moment(date).format('DD/MM/YYYY')
}

const formatDomain = (url: string) => {
  if (!url)
    return ''
  try {
    return new URL(url).hostname.replace(/^www\./, '')
  }
  catch {
    return url
  }
}

const formatCurrency = (value: number | string | null | undefined) => {
  if (value === null || value === undefined || value === '')
    return '--'
  const num = typeof value === 'string' ? Number.parseFloat(value) : value
  if (Number.isNaN(num))
    return '--'

  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(num)
}

const fetchAsset = async () => {
  loading.value = true
  try {
    const { data } = await useApi<any>(`/it/assets/${route.params.id}`)
    if (data.value)
      asset.value = data.value
  }
  catch (e) {
    console.error(e)
  }
  finally {
    loading.value = false
  }
}

const fetchSuppliers = async () => {
  try {
    const { data } = await useApi<any>('/it/suppliers')
    if (data.value && data.value.data && Array.isArray(data.value.data))
      suppliers.value = data.value.data
    else if (data.value && Array.isArray(data.value))
      suppliers.value = data.value
    else
      suppliers.value = []
  }
  catch (error) {
    console.error('Error fetching suppliers:', error)
    suppliers.value = []
  }
}

const openSupplierDialog = () => {
  supplierFormData.value = {
    supplier_id: '',
    unit_cost: '',
    purchase_date: '',
    order_reference: '',
    product_link: '',
    notes: '',
  }
  fetchSuppliers()
  isSupplierDialogVisible.value = true
}

const editSupplier = () => {
  const supplier = asset.value?.suppliers?.[0]
  if (!supplier)
    return
  supplierFormData.value = {
    supplier_id: supplier.id,
    unit_cost: supplier.pivot.unit_cost,
    purchase_date: supplier.pivot.purchase_date,
    order_reference: supplier.pivot.order_reference || '',
    product_link: supplier.pivot.product_link || '',
    notes: supplier.pivot.notes || '',
  }
  fetchSuppliers()
  isSupplierDialogVisible.value = true
}

const attachSupplier = async () => {
  loading.value = true
  try {
    const existingSuppliers = asset.value?.suppliers || []
    for (const supplier of existingSuppliers) {
      await $api(`/it/assets/${route.params.id}/detach_supplier/${supplier.id}`, {
        method: 'DELETE',
      })
    }
    await $api(`/it/assets/${route.params.id}/attach_supplier`, {
      method: 'POST',
      body: supplierFormData.value,
    })
    isSupplierDialogVisible.value = false
    fetchAsset()
  }
  catch (e) {
    console.error(e)
    alert(`Error: ${JSON.stringify(e)}`)
  }
  finally {
    loading.value = false
  }
}

const detachSupplier = async (supplierId: string) => {
  if (!confirm(t('Label.ConfermaEliminazione')))
    return

  loading.value = true
  try {
    await $api(`/it/assets/${route.params.id}/detach_supplier/${supplierId}`, {
      method: 'DELETE',
    })
    fetchAsset()
  }
  catch (e) {
    console.error(e)
    alert(`Error: ${JSON.stringify(e)}`)
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchAsset()
})
</script>

<template>
  <VCard
    v-if="!loading && asset"
    flat
    border
  >
    <VCardItem>
      <template #prepend>
        <VAvatar
          color="primary"
          variant="tonal"
          size="40"
        >
          <VIcon icon="tabler-device-laptop" />
        </VAvatar>
      </template>
      <VCardTitle>{{ asset.brand }} {{ asset.model }}</VCardTitle>
      <VCardSubtitle>{{ asset.serial_number || asset.asset_tag }}</VCardSubtitle>
    </VCardItem>

    <VDivider />

    <VCardText class="pa-4">
      <VRow>
        <VCol
          cols="12"
          lg="8"
        >
          <VCard
            flat
            border
          >
            <VCardItem>
              <VCardTitle>{{ t('IT.Asset.Info') }}</VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-4">
              <VRow>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.brand || '--'"
                    :label="t('IT.Asset.Brand')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.model || '--'"
                    :label="t('IT.Asset.Model')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.serial_number || '--'"
                    :label="t('IT.Asset.SerialNumber')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.asset_tag || '--'"
                    :label="t('IT.Asset.AssetTag')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.category?.name || '--'"
                    :label="t('IT.Categories')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.location?.name || '--'"
                    :label="t('IT.Locations')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.quantity ?? 0"
                    :label="t('IT.Asset.Quantity')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="formatDate(asset.purchase_date)"
                    :label="t('IT.Asset.PurchaseDate')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="formatDate(asset.warranty_expiry)"
                    :label="t('IT.Asset.WarrantyExpiry')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <div class="text-caption text-medium-emphasis mb-1">
                    {{ t('IT.Asset.Status') }}
                  </div>
                  <VChip
                    :color="resolveStatusColor(asset.status)"
                    size="small"
                    variant="flat"
                  >
                    {{ t(`IT.Asset.Status.${asset.status.replace(' ', '')}`) }}
                  </VChip>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard
            flat
            border
            class="mt-4"
          >
            <VCardItem>
              <VCardTitle>{{ t('IT.NetworkDevices') }}</VCardTitle>
              <template #append>
                <VChip
                  v-if="asset.network_device?.monitor_enabled"
                  color="success"
                  size="small"
                  class="mr-2"
                >
                  <VIcon start icon="tabler-activity" />
                  {{ t('IT.NetworkDevice.MonitoringActive') }}
                </VChip>
                <VBtn
                  v-if="asset.network_device"
                  icon="tabler-edit"
                  size="small"
                  variant="text"
                  @click="openNetworkDeviceDialog(true)"
                />
                <VBtn
                  v-if="asset.network_device"
                  icon="tabler-trash"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteNetworkDevice"
                />
                <VBtn
                  v-if="!asset.network_device"
                  icon="tabler-plus"
                  size="small"
                  variant="text"
                  @click="openNetworkDeviceDialog(false)"
                />
              </template>
            </VCardItem>
            <VDivider />
            <VCardText
              v-if="asset.network_device"
              class="pa-4"
            >
              <VRow>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.network_device.ip_address || '--'"
                    :label="t('IT.NetworkDevice.IPAddress')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.network_device.mac_address || '--'"
                    :label="t('IT.NetworkDevice.MACAddress')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="t(`IT.NetworkDevice.DeviceType.${asset.network_device.device_type.replace(' ', '')}`)"
                    :label="t('IT.NetworkDevice.DeviceType')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.network_device.location || '--'"
                    :label="t('IT.NetworkDevice.Location')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.network_device.rack_position || '--'"
                    :label="t('IT.NetworkDevice.RackPosition')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.network_device.vlan || '--'"
                    :label="t('IT.NetworkDevice.VLAN')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.network_device.subnet || '--'"
                    :label="t('IT.NetworkDevice.Subnet')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                >
                  <VTextField
                    :model-value="asset.network_device.notes || '--'"
                    :label="t('IT.NetworkDevice.Notes')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
              </VRow>
            </VCardText>
            <VCardText
              v-else
              class="pa-4 text-center text-muted"
            >
              {{ t('IT.NetworkDevice.NoDevice') }}
            </VCardText>
          </VCard>

          <VCard
            flat
            border
            class="mt-4"
          >
            <VCardItem>
              <VCardTitle>{{ t('IT.Assignments') }}</VCardTitle>
            </VCardItem>
            <VDivider />
            <VDataTable
              :headers="[
                { title: t('IT.Assignment.Employee'), key: 'assignable_display' },
                { title: t('IT.Assignment.AssignedBy'), key: 'assigned_by' },
                { title: t('IT.Assignment.AssignedAt'), key: 'assigned_at' },
                { title: t('IT.Assignment.ReturnedAt'), key: 'returned_at' },
                { title: t('IT.Asset.Status'), key: 'status' },
              ]"
              :items="formattedAssignments"
              :items-per-page="5"
            >
              <template #item.assigned_by="{ item }">
                {{ item.assigned_by_display }}
              </template>

              <template #item.assigned_at="{ item }">
                {{ formatDate(item.assigned_at) }}
              </template>

              <template #item.returned_at="{ item }">
                {{ formatDate(item.returned_at) }}
              </template>

              <template #item.status="{ item }">
                <VChip
                  :color="item.status === 'Active' ? 'success' : 'secondary'"
                  size="small"
                  variant="flat"
                >
                  {{ item.status }}
                </VChip>
              </template>
            </VDataTable>
          </VCard>

          <VCard
            flat
            border
            class="mt-4"
          >
            <VCardItem>
              <VCardTitle>{{ t('IT.Transactions') }}</VCardTitle>
            </VCardItem>
            <VDivider />
            <VDataTable
              :headers="[
                { title: t('IT.Transaction.Type'), key: 'type' },
                { title: t('IT.Transaction.FromLocation'), key: 'fromLocation.name' },
                { title: t('IT.Transaction.ToLocation'), key: 'toLocation.name' },
                { title: t('IT.Transaction.PerformedBy'), key: 'performed_by_display' },
                { title: t('IT.Transaction.Date'), key: 'date' },
              ]"
              :items="formattedTransactions"
              :items-per-page="5"
            >
              <template #item.type="{ item }">
                <VChip
                  :color="resolveTransactionColor(item.type)"
                  size="small"
                  variant="flat"
                >
                  {{ t(`IT.Transaction.Type.${item.type}`) }}
                </VChip>
              </template>

              <template #item.fromLocation.name="{ item }">
                {{ item.fromLocation?.name || '--' }}
              </template>

              <template #item.toLocation.name="{ item }">
                {{ item.toLocation?.name || '--' }}
              </template>

              <template #item.date="{ item }">
                {{ formatDate(item.date) }}
              </template>
            </VDataTable>
          </VCard>
        </VCol>

        <VCol
          cols="12"
          lg="4"
        >
          <VCard
            flat
            border
          >
            <VCardItem>
              <VCardTitle>{{ t('IT.Info') }}</VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-4">
              <VRow>
                <VCol
                  cols="12"
                  sm="6"
                  lg="12"
                >
                  <VCard
                    variant="tonal"
                    :color="resolveStatusColor(asset.status)"
                  >
                    <VCardText class="text-center">
                      <div class="text-h4">
                        {{ asset.quantity ?? 0 }}
                      </div>
                      <div class="text-caption">
                        {{ t('IT.Asset.Quantity') }}
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                  lg="12"
                >
                  <VCard
                    variant="tonal"
                    color="primary"
                  >
                    <VCardText class="text-center">
                      <div class="text-h4">
                        {{ t(`IT.Asset.Status.${asset.status.replace(' ', '')}`) }}
                      </div>
                      <div class="text-caption">
                        {{ t('IT.Asset.Status') }}
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard
            flat
            border
            class="mt-4"
          >
            <VCardItem>
              <template #append>
                <VBtn
                  v-if="asset.suppliers?.length"
                  icon="tabler-pencil"
                  size="small"
                  variant="text"
                  @click="editSupplier"
                />
                <VBtn
                  v-if="asset.suppliers?.length"
                  icon="tabler-trash"
                  size="small"
                  variant="text"
                  color="error"
                  @click="detachSupplier(asset.suppliers[0].id)"
                />
                <VBtn
                  v-else
                  icon="tabler-plus"
                  size="small"
                  variant="text"
                  @click="openSupplierDialog"
                />
              </template>
              <VCardTitle>{{ t('IT.Suppliers') }}</VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-4">
              <div
                v-if="!asset.suppliers?.length"
                class="text-center text-disabled pa-4"
              >
                {{ t('Label.NessunDato') }}
              </div>
              <VRow v-else>
                <VCol cols="12">
                  <VTextField
                    :model-value="asset.suppliers[0].name"
                    :label="t('IT.Supplier.Name')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.suppliers[0].contact_person || '--'"
                    :label="t('IT.Supplier.Contact')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.suppliers[0].email || '--'"
                    :label="t('IT.Supplier.Email')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="asset.suppliers[0].phone || '--'"
                    :label="t('IT.Supplier.Phone')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="formatCurrency(asset.suppliers[0].pivot.unit_cost)"
                    :label="t('IT.Asset.UnitCost')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="formatDate(asset.suppliers[0].pivot.purchase_date)"
                    :label="t('IT.Asset.PurchaseDate')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="formatDomain(asset.suppliers[0].pivot.product_link) || '--'"
                    :label="t('IT.Asset.ProductLink')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextarea
                    :model-value="asset.suppliers[0].pivot.notes || '--'"
                    :label="t('IT.Assignment.Notes')"
                    readonly
                    rows="2"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>

  <VProgressCircular
    v-else-if="loading"
    indeterminate
    color="primary"
    class="ma-4"
  />

  <!-- Attach Supplier Dialog -->
  <VDialog
    v-model="isSupplierDialogVisible"
    max-width="600px"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span>{{ t('IT.Supplier.Attach') }}</span>
        <VBtn
          icon="tabler-x"
          variant="text"
          @click="isSupplierDialogVisible = false"
        />
      </VCardTitle>

      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12">
            <VSelect
              v-model="supplierFormData.supplier_id"
              :label="t('IT.Supplier.Name')"
              :items="suppliers"
              item-title="name"
              item-value="id"
              required
            />
          </VCol>

          <VCol
            cols="12"
            sm="6"
          >
            <VTextField
              v-model="supplierFormData.unit_cost"
              type="number"
              :label="t('IT.Asset.UnitCost')"
            />
          </VCol>

          <VCol
            cols="12"
            sm="6"
          >
            <VTextField
              v-model="supplierFormData.purchase_date"
              type="date"
              :label="t('IT.Asset.PurchaseDate')"
            />
          </VCol>

          <VCol cols="12">
            <VTextField
              v-model="supplierFormData.order_reference"
              :label="t('IT.Supplier.OrderReference')"
            />
          </VCol>

          <VCol cols="12">
            <VTextField
              v-model="supplierFormData.product_link"
              :label="t('IT.Asset.ProductLink')"
            />
          </VCol>

          <VCol cols="12">
            <VTextarea
              v-model="supplierFormData.notes"
              :label="t('IT.Assignment.Notes')"
              rows="2"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="text"
          @click="isSupplierDialogVisible = false"
        >
          {{ t('Label.Chiudi') }}
        </VBtn>
        <VBtn
          color="primary"
          :loading="loading"
          @click="attachSupplier"
        >
          {{ t('Label.Salva') }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VDialog v-model="networkDeviceDialog" max-width="600px">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span>{{ networkDeviceForm.id ? t('IT.NetworkDevice.Edit') : t('IT.NetworkDevice.Add') }}</span>
        <VBtn icon="tabler-x" variant="text" @click="networkDeviceDialog = false" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12" sm="6">
            <VTextField
              v-model="networkDeviceForm.ip_address"
              :label="t('IT.NetworkDevice.IPAddress')"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <VTextField
              v-model="networkDeviceForm.mac_address"
              :label="t('IT.NetworkDevice.MACAddress')"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <VSelect
              v-model="networkDeviceForm.device_type"
              :label="t('IT.NetworkDevice.DeviceType')"
              :items="['Router', 'Switch', 'Access Point', 'Server', 'Firewall', 'Other']"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <VTextField
              v-model="networkDeviceForm.location"
              :label="t('IT.NetworkDevice.Location')"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <VTextField
              v-model="networkDeviceForm.rack_position"
              :label="t('IT.NetworkDevice.RackPosition')"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <VTextField
              v-model="networkDeviceForm.vlan"
              :label="t('IT.NetworkDevice.VLAN')"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <VTextField
              v-model="networkDeviceForm.subnet"
              :label="t('IT.NetworkDevice.Subnet')"
            />
          </VCol>
          <VCol cols="12">
            <VTextarea
              v-model="networkDeviceForm.notes"
              :label="t('IT.NetworkDevice.Notes')"
              rows="2"
            />
          </VCol>
          <VCol cols="12">
            <VSwitch
              v-model="networkDeviceForm.disabled"
              :label="t('IT.NetworkDevice.Disabled')"
            />
          </VCol>
          <VCol cols="12">
            <VSwitch
              v-model="networkDeviceForm.monitor_enabled"
              :label="t('IT.NetworkDevice.MonitorEnabled')"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn variant="text" @click="networkDeviceDialog = false">
          {{ t('Label.Chiudi') }}
        </VBtn>
        <VBtn color="primary" :loading="networkDeviceLoading" @click="saveNetworkDevice">
          {{ t('Label.Salva') }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
