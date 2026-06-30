<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  id: string
}>()

const { t } = useI18n()

const accessTypes = ref<any[]>([])
const accessResources = ref<any[]>([])
const employeeAccesses = ref<any[]>([])
const loading = ref(false)
const accessDialog = ref(false)
const editAccessId = ref<string | null>(null)
const selectedResource = ref<any>(null)
const selectedPermission = ref('read')

const importDialog = ref(false)
const importType = ref<'google_drive' | 'file_server' | null>(null)
const importLoading = ref(false)
const importFolders = ref<any[]>([])
const selectedImportFolders = ref<any[]>([])
const folderBreadcrumb = ref<any[]>([])

const googleDriveImportForm = ref({
  access_type_id: '',
  parent_folder_id: '',
  shared_drive_only: false,
  drive_id: '',
  folder_name: '',
  default_permission: 'read',
  assign_to_employee: false,
})

const fileServerImportForm = ref({
  server_ip: '',
  share_name: '',
  username: '',
  password: '',
  parent_path: '',
  folder_name: '',
  default_permission: 'read',
  assign_to_employee: false,
})

const fetchAccessTypes = async () => {
  try {
    const { data } = await useApi<any>('/hr/accesses/types')
    accessTypes.value = data.value || []
  }
  catch (e) {
    console.error('Error fetching access types:', e)
  }
}

const fetchAccessResources = async () => {
  try {
    const { data } = await useApi<any>('/hr/accesses/resources')
    accessResources.value = data.value || []
  }
  catch (e) {
    console.error('Error fetching access resources:', e)
  }
}

const fetchEmployeeAccesses = async () => {
  loading.value = true
  try {
    const { data } = await useApi<any>(`/hr/accesses/employee/${props.id}`)
    employeeAccesses.value = data.value || []
  }
  catch (e) {
    console.error('Error fetching employee accesses:', e)
  }
  finally {
    loading.value = false
  }
}

const openAddAccessDialog = () => {
  editAccessId.value = null
  selectedResource.value = null
  selectedPermission.value = 'read'
  accessDialog.value = true
}

const openEditAccessDialog = (access: any) => {
  editAccessId.value = access.id
  selectedResource.value = access.access_resource_id
  selectedPermission.value = access.permission
  accessDialog.value = true
}

const saveEmployeeAccess = async () => {
  if (!selectedResource.value) return

  try {
    if (editAccessId.value) {
      // Update existing access
      await $api(`/hr/accesses/employee/${editAccessId.value}`, {
        method: 'POST',
        body: {
          permission: selectedPermission.value,
          notes: null,
        },
      })
    }
    else {
      // Create new access
      await $api('/hr/accesses/employee', {
        method: 'POST',
        body: {
          employee_id: props.id,
          access_resource_id: selectedResource.value,
          permission: selectedPermission.value,
        },
      })
    }
    accessDialog.value = false
    await fetchEmployeeAccesses()
  }
  catch (e) {
    console.error('Error saving employee access:', e)
    alert('Error: ' + JSON.stringify(e))
  }
}

const deleteEmployeeAccess = async (accessId: string) => {
  if (!confirm('Are you sure you want to remove this access?')) return

  try {
    await $api(`/hr/accesses/employee/${accessId}`, {
      method: 'DELETE',
    })
    await fetchEmployeeAccesses()
  }
  catch (e) {
    console.error('Error deleting employee access:', e)
    alert('Error: ' + JSON.stringify(e))
  }
}

const getPermissionColor = (permission: string) => {
  const colors: Record<string, string> = {
    read: 'info',
    write: 'warning',
    admin: 'error',
  }
  return colors[permission] || 'secondary'
}

const getPermissionLabel = (permission: string) => {
  const labels: Record<string, string> = {
    read: 'Lettura',
    write: 'Scrittura',
    admin: 'Amministratore',
  }
  return labels[permission] || permission
}

const getResourcesByType = (typeId: string) => {
  // Filter resources by type AND only show resources the employee has access to
  return accessResources.value.filter(r => 
    r.access_type_id === typeId && 
    employeeAccesses.value.some(a => a.access_resource_id === r.id)
  )
}

const openImportDialog = async (type: 'google_drive' | 'file_server') => {
  importType.value = type
  importFolders.value = []
  selectedImportFolders.value = []
  folderBreadcrumb.value = []
  googleDriveImportForm.value.parent_folder_id = ''
  googleDriveImportForm.value.drive_id = ''
  googleDriveImportForm.value.folder_name = ''
  googleDriveImportForm.value.default_permission = 'read'
  googleDriveImportForm.value.assign_to_employee = false
  fileServerImportForm.value.server_ip = ''
  fileServerImportForm.value.share_name = ''
  fileServerImportForm.value.username = ''
  fileServerImportForm.value.password = ''
  fileServerImportForm.value.parent_path = ''
  fileServerImportForm.value.folder_name = ''
  fileServerImportForm.value.default_permission = 'read'
  fileServerImportForm.value.assign_to_employee = false

  // Auto-select or create access type
  const typeName = type === 'google_drive' ? 'Google Drive' : 'File Server'
  const existingType = accessTypes.value.find(at => at.name === typeName)

  if (existingType) {
    googleDriveImportForm.value.access_type_id = existingType.id
  }
  else {
    // Create access type automatically
    try {
      const data = await $api<any>('/hr/accesses/types', {
        method: 'POST',
        body: {
          name: typeName,
          description: type === 'google_drive' ? 'Cartelle Google Drive' : 'Cartelle File Server',
          icon: type === 'google_drive' ? 'tabler-brand-google' : 'tabler-server',
          disabled: false,
        },
      })
      await fetchAccessTypes()
      const newType = accessTypes.value.find(at => at.name === typeName)
      if (newType) {
        googleDriveImportForm.value.access_type_id = newType.id
      }
    }
    catch (e) {
      console.error('Error creating access type:', e)
      alert('Error: ' + JSON.stringify(e))
      return
    }
  }

  importDialog.value = true
}

const importGoogleDriveFolders = async () => {
  importLoading.value = true
  try {
    const data = await $api<any>('/hr/accesses/import/google-drive', {
      method: 'POST',
      body: googleDriveImportForm.value,
    })
    importFolders.value = data || []
  }
  catch (e) {
    console.error('Error importing Google Drive folders:', e)
    alert('Error: ' + JSON.stringify(e))
  }
  finally {
    importLoading.value = false
  }
}

const importFileServerFolders = async () => {
  importLoading.value = true
  try {
    const data = await $api<any>('/hr/accesses/import/file-server', {
      method: 'POST',
      body: fileServerImportForm.value,
    })
    importFolders.value = data || []
  }
  catch (e) {
    console.error('Error importing File Server folders:', e)
    alert('Error: ' + JSON.stringify(e))
  }
  finally {
    importLoading.value = false
  }
}

const openFolder = (folder: any) => {
  folderBreadcrumb.value.push(folder)

  if (importType.value === 'google_drive') {
    googleDriveImportForm.value.parent_folder_id = folder.id
    if (folder.isSharedDrive) {
      googleDriveImportForm.value.drive_id = folder.id
    }
    importGoogleDriveFolders()
  }
  else if (importType.value === 'file_server') {
    fileServerImportForm.value.parent_path = folder.path
    importFileServerFolders()
  }
}

const navigateToBreadcrumb = (index: number) => {
  folderBreadcrumb.value = folderBreadcrumb.value.slice(0, index + 1)
  const folder = folderBreadcrumb.value[index]

  if (importType.value === 'google_drive') {
    googleDriveImportForm.value.parent_folder_id = folder ? folder.id : ''
    if (!folder || folder.isSharedDrive) {
      googleDriveImportForm.value.drive_id = folder ? folder.id : ''
    }
    importGoogleDriveFolders()
  }
  else if (importType.value === 'file_server') {
    fileServerImportForm.value.parent_path = folder ? folder.path : ''
    importFileServerFolders()
  }
}

const resetFolderNavigation = () => {
  folderBreadcrumb.value = []
  googleDriveImportForm.value.parent_folder_id = ''
  googleDriveImportForm.value.drive_id = ''
  fileServerImportForm.value.parent_path = ''
}

const bulkImportResources = async () => {
  if (selectedImportFolders.value.length === 0) {
    alert('Seleziona almeno una cartella da importare')
    return
  }

  importLoading.value = true
  try {
    const resources = selectedImportFolders.value.map((folder: any) => ({
      name: folder.name,
      path: folder.path || null,
      drive_file_id: folder.id || null,
      import_method: importType.value === 'google_drive' ? 'google_drive' : 'file_server',
      server_ip: importType.value === 'file_server' ? fileServerImportForm.value.server_ip : null,
    }))

    const currentForm = importType.value === 'google_drive' ? googleDriveImportForm.value : fileServerImportForm.value

    const body: any = {
      access_type_id: googleDriveImportForm.value.access_type_id,
      default_permission: currentForm.default_permission,
      resources,
    }

    if (currentForm.assign_to_employee) {
      body.employee_id = props.id
      body.assign_to_employee = true
    }

    const data = await $api<any>('/hr/accesses/import/bulk', {
      method: 'POST',
      body,
    })

    importDialog.value = false
    await fetchAccessResources()
    if (currentForm.assign_to_employee) {
      await fetchEmployeeAccesses()
      alert(`Importate ${resources.length} risorse e assegnate al dipendente con successo`)
    }
    else {
      alert(`Importate ${resources.length} risorse con successo`)
    }
  }
  catch (e) {
    console.error('Error bulk importing resources:', e)
    alert('Error: ' + JSON.stringify(e))
  }
  finally {
    importLoading.value = false
  }
}

onMounted(() => {
  fetchAccessTypes()
  fetchAccessResources()
  fetchEmployeeAccesses()
})
</script>

<template>
  <div class="employee-accesses">
    <div class="d-flex justify-space-between align-center mb-4">
      <h3 class="text-h6 font-weight-semibold">Gestione Accessi</h3>
      <div class="d-flex gap-2">
        <VBtn
          color="info"
          variant="tonal"
          @click="openImportDialog('google_drive')"
        >
          <VIcon start icon="tabler-brand-google" />
          Importa Google Drive
        </VBtn>
        <VBtn
          color="warning"
          variant="tonal"
          @click="openImportDialog('file_server')"
        >
          <VIcon start icon="tabler-server" />
          Importa File Server
        </VBtn>
        <VBtn
          color="primary"
          variant="tonal"
          @click="openAddAccessDialog"
        >
          <VIcon start icon="tabler-plus" />
          Aggiungi Accesso
        </VBtn>
      </div>
    </div>

    <VProgressCircular
      v-if="loading"
      indeterminate
      color="primary"
      class="ma-4"
    />

    <div v-else-if="accessTypes.length === 0" class="text-center pa-8 text-medium-emphasis">
      <VIcon icon="tabler-lock-open" size="48" class="mb-2" />
      <p>Nessun tipo di accesso configurato</p>
    </div>

    <div v-else class="d-flex flex-column gap-4">
      <VCard
        v-for="accessType in accessTypes"
        :key="accessType.id"
        variant="outlined"
        class="rounded-lg"
      >
        <VCardItem>
          <template #prepend>
            <VAvatar
              color="primary"
              variant="tonal"
              class="me-3"
            >
              <VIcon :icon="accessType.icon || 'tabler-folder'" />
            </VAvatar>
          </template>
          <VCardTitle class="text-h6">{{ accessType.name }}</VCardTitle>
          <VCardSubtitle v-if="accessType.description">{{ accessType.description }}</VCardSubtitle>
        </VCardItem>

        <VDivider />

        <VCardText class="pa-4">
          <div
            v-if="getResourcesByType(accessType.id).length === 0"
            class="text-center text-medium-emphasis pa-4"
          >
            Nessuna risorsa configurata per questo tipo
          </div>

          <div v-else class="d-flex flex-column gap-3">
            <div
              v-for="resource in getResourcesByType(accessType.id)"
              :key="resource.id"
              class="d-flex justify-space-between align-center pa-2 rounded border"
              :class="employeeAccesses.find(a => a.access_resource_id === resource.id) ? 'bg-primary-light' : 'bg-surface'"
            >
              <div class="d-flex flex-column">
                <span class="font-weight-medium">{{ resource.name }}</span>
                <span v-if="resource.path" class="text-caption text-medium-emphasis">{{ resource.path }}</span>
                <span v-if="resource.drive_file_id" class="text-caption text-primary">
                  <VIcon icon="tabler-brand-google" size="14" class="me-1" />
                  Google Drive
                </span>
              </div>

              <div
                v-if="employeeAccesses.find(a => a.access_resource_id === resource.id)"
                class="d-flex align-center gap-2"
              >
                <VChip
                  :color="getPermissionColor(employeeAccesses.find(a => a.access_resource_id === resource.id)?.permission)"
                  size="small"
                  label
                >
                  {{ getPermissionLabel(employeeAccesses.find(a => a.access_resource_id === resource.id)?.permission) }}
                </VChip>
                <VBtn
                  icon="tabler-edit"
                  size="small"
                  variant="text"
                  color="primary"
                  @click="openEditAccessDialog(employeeAccesses.find(a => a.access_resource_id === resource.id))"
                />
                <VBtn
                  icon="tabler-trash"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteEmployeeAccess(employeeAccesses.find(a => a.access_resource_id === resource.id)?.id)"
                />
              </div>

              <VBtn
                v-else
                icon="tabler-plus"
                size="small"
                variant="text"
                color="primary"
                @click="selectedResource = resource.id; openAddAccessDialog()"
              />
            </div>
          </div>
        </VCardText>
      </VCard>
    </div>

    <!-- Add Access Dialog -->
    <VDialog v-model="accessDialog" max-width="500px">
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-4">
          <span>{{ editAccessId ? 'Modifica Permesso' : 'Aggiungi Accesso' }}</span>
          <VBtn icon="tabler-x" variant="text" @click="accessDialog = false" />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-4">
          <VSelect
            v-model="selectedResource"
            :items="accessResources"
            item-title="name"
            item-value="id"
            label="Risorsa"
            placeholder="Seleziona una risorsa"
            :disabled="!!editAccessId"
            :readonly="!!editAccessId"
            required
          />

          <VSelect
            v-model="selectedPermission"
            :items="[
              { title: 'Lettura', value: 'read' },
              { title: 'Scrittura', value: 'write' },
              { title: 'Amministratore', value: 'admin' },
            ]"
            label="Permesso"
            placeholder="Seleziona il livello di permesso"
            required
            class="mt-4"
          />
        </VCardText>

        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="text" @click="accessDialog = false">
            Annulla
          </VBtn>
          <VBtn color="primary" @click="saveEmployeeAccess">
            Salva
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Import Dialog -->
    <VDialog v-model="importDialog" max-width="800px">
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-4">
          <span>{{ importType === 'google_drive' ? 'Importa da Google Drive' : 'Importa da File Server' }}</span>
          <VBtn icon="tabler-x" variant="text" @click="importDialog = false" />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-4">
          <template v-if="importType === 'google_drive'">
            <VTextField
              v-model="googleDriveImportForm.parent_folder_id"
              label="ID Cartella Padre (opzionale)"
              placeholder="Lascia vuoto per importare dalla root"
              class="mt-4"
            />
            <VTextField
              v-model="googleDriveImportForm.folder_name"
              label="Cerca per nome (opzionale)"
              placeholder="Es. Anagrafica"
              class="mt-4"
            />
            <VSelect
              v-model="googleDriveImportForm.default_permission"
              label="Permesso di default"
              :items="[
                { value: 'read', title: 'Lettura' },
                { value: 'write', title: 'Scrittura' },
                { value: 'admin', title: 'Manager' },
              ]"
              class="mt-4"
            />
            <VCheckbox
              v-model="googleDriveImportForm.assign_to_employee"
              label="Assegna a questo dipendente"
              density="compact"
              class="mt-2"
            />
            <VCheckbox
              v-model="googleDriveImportForm.shared_drive_only"
              label="Mostra solo cartelle degli Shared Drives"
              density="compact"
              class="mt-2"
            />
          </template>

          <template v-if="importType === 'file_server'">
            <VTextField
              v-model="fileServerImportForm.server_ip"
              label="IP del Server (opzionale, usa .env se vuoto)"
              placeholder="Es. 192.168.1.100"
              class="mt-4"
            />
            <VTextField
              v-model="fileServerImportForm.share_name"
              label="Nome Share"
              placeholder="Es. shared"
              required
              class="mt-4"
            />
            <VTextField
              v-model="fileServerImportForm.username"
              label="Username (opzionale, usa .env se vuoto)"
              placeholder="Username per accesso al server"
              class="mt-4"
            />
            <VTextField
              v-model="fileServerImportForm.password"
              label="Password (opzionale, usa .env se vuoto)"
              type="password"
              placeholder="Password per accesso al server"
              class="mt-4"
            />
            <VTextField
              v-model="fileServerImportForm.folder_name"
              label="Cerca per nome (opzionale)"
              placeholder="Es. Anagrafica"
              class="mt-4"
            />
            <VSelect
              v-model="fileServerImportForm.default_permission"
              label="Permesso di default"
              :items="[
                { value: 'read', title: 'Lettura' },
                { value: 'write', title: 'Scrittura' },
                { value: 'admin', title: 'Manager' },
              ]"
              class="mt-4"
            />
            <VCheckbox
              v-model="fileServerImportForm.assign_to_employee"
              label="Assegna a questo dipendente"
              density="compact"
              class="mt-2"
            />
          </template>

          <VBtn
            color="primary"
            class="mt-4"
            :loading="importLoading"
            @click="importType === 'google_drive' ? importGoogleDriveFolders() : importFileServerFolders()"
          >
            <VIcon start icon="tabler-search" />
            Cerca Cartelle
          </VBtn>

          <VDivider v-if="importFolders.length > 0" class="my-4" />

          <div v-if="importFolders.length > 0" class="d-flex flex-column gap-2">
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="font-weight-medium">{{ importFolders.length }} cartelle trovate</span>
              <VBtn
                size="small"
                variant="tonal"
                color="primary"
                @click="selectedImportFolders = importFolders.map(f => f)"
              >
                Seleziona Tutte
              </VBtn>
            </div>

            <div class="d-flex align-center gap-2 flex-wrap mb-2">
              <VBtn
                size="small"
                variant="text"
                :disabled="folderBreadcrumb.length === 0"
                @click="navigateToBreadcrumb(-1)"
              >
                <VIcon icon="tabler-home" size="16" />
              </VBtn>
              <template v-for="(folder, index) in folderBreadcrumb" :key="folder.id">
                <VIcon icon="tabler-chevron-right" size="16" />
                <VBtn
                  size="small"
                  variant="text"
                  @click="navigateToBreadcrumb(index)"
                >
                  {{ folder.name }}
                </VBtn>
              </template>
            </div>

            <div class="d-flex flex-column gap-2" style="max-height: 300px; overflow-y: auto;">
              <div
                v-for="folder in importFolders"
                :key="folder.id || folder.path"
                class="d-flex justify-space-between align-center pa-2 rounded border"
              >
                <VCheckbox
                  v-model="selectedImportFolders"
                  :value="folder"
                  :label="folder.name"
                  density="compact"
                  hide-details
                >
                  <template #label>
                    <div class="d-flex align-center gap-2">
                      <VIcon
                        :icon="folder.isSharedDrive ? 'tabler-brand-google-drive' : 'tabler-folder'"
                        size="18"
                        :color="folder.isSharedDrive ? 'primary' : undefined"
                      />
                      <div class="d-flex flex-column">
                        <span class="font-weight-medium">{{ folder.name }}</span>
                        <span v-if="folder.driveName" class="text-caption text-primary">{{ folder.driveName }}</span>
                        <span v-if="folder.path" class="text-caption text-medium-emphasis">{{ folder.path }}</span>
                      </div>
                    </div>
                  </template>
                </VCheckbox>
                <VBtn
                  size="small"
                  variant="text"
                  color="primary"
                  @click="openFolder(folder)"
                >
                  <VIcon icon="tabler-arrow-right" size="16" />
                </VBtn>
              </div>
            </div>
          </div>
        </VCardText>

        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="text" @click="importDialog = false">
            Annulla
          </VBtn>
          <VBtn
            color="primary"
            :disabled="selectedImportFolders.length === 0"
            :loading="importLoading"
            @click="bulkImportResources"
          >
            Importa {{ selectedImportFolders.length }} Risorse
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
