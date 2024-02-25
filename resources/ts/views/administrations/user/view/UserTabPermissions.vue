<script lang="ts" setup>
import { VForm } from 'vuetify/components/VForm'

interface Props {
  id: number
}

const props = defineProps<Props>()


const permissions = ref<Permission[]>([
  {
    name: 'User Management',
    read: false,
    write: false,
    create: false,
  },
  {
    name: 'Content Management',
    read: false,
    write: false,
    create: false,
  },
  {
    name: 'Disputes Management',
    read: false,
    write: false,
    create: false,
  },
  {
    name: 'Database Management',
    read: false,
    write: false,
    create: false,
  },
  {
    name: 'Financial Management',
    read: false,
    write: false,
    create: false,
  },
  {
    name: 'Reporting',
    read: false,
    write: false,
    create: false,
  },
  {
    name: 'API Control',
    read: false,
    write: false,
    create: false,
  },
  {
    name: 'Repository Management',
    read: false,
    write: false,
    create: false,
  },
  {
    name: 'Payroll',
    read: false,
    write: false,
    create: false,
  },
])

const isSelectAll = ref(false)
const refPermissionForm = ref<VForm>()
const isSnackbarScrollReverseVisible = ref(false)
let view = false

const fetchPermissions = async () => {
  const usersData = await useApi<any>(createUrl(`/admin/permissions/tab/${props.id}`))

  permissions.value = usersData.data.value.userPermissions
  view = true
}

fetchPermissions()

// permissions.value = resultData.value.data
// totalPermissions = resultData.value.total

const checkedCount = computed(() => {
  let counter = 0

  permissions.value.forEach(permission => {
    Object.entries(permission).forEach(([key, value]) => {
      if (key !== 'name' && value)
        counter++
    })
  })

  return counter
})

const isIndeterminate = computed(() => checkedCount.value > 0 && checkedCount.value < (permissions.value.length * 3))


// select all
watch(isSelectAll, val => {
  permissions.value = permissions.value.map(permission => ({
    ...permission,
    read: val,
    edit: val,
    create: val,
    deleted: val,
    sing: val,
  }))
})

const onSubmit = async () => {
  await $api(`/admin/permissions/set/${props.id}`, {
    method: 'POST',
    body: permissions.value,
  })
  isSnackbarScrollReverseVisible.value = true
}

const onReset = async () => {
  await fetchPermissions()
}
</script>

<template>
  <VCard class="user-tab-notification">
    <VSnackbar
        v-model="isSnackbarScrollReverseVisible"
        transition="scroll-y-reverse-transition"
        location="top central"
        color="success"
    >
      {{ $t('Messaggi.Permessi-Salvati') }}
    </VSnackbar>
    <VCardItem>
      <VCardTitle>{{ $t('Label.Lista-Permessi') }}</VCardTitle>
    </VCardItem>
    <VCardText class="mt-6">
      <!-- 👉 Form -->
      <VForm
        ref="refPermissionForm"
        @submit.prevent="onSubmit">
        <!-- 👉 Role Permissions -->
        <VTable class="permission-table text-no-wrap">
          <!-- 👉 Admin  -->
          <tr>
            <td>
              {{ $t('Label.Moduli') }}
            </td>
            <td colspan="4">
              <div class="d-flex justify-end">
                <VCheckbox
                    v-model="isSelectAll"
                    v-model:indeterminate="isIndeterminate"
                    label="Select All"
                />
              </div>
            </td>
          </tr>

          <!-- 👉 Other permission loop -->
          <template v-if="view"
              v-for="permission in permissions"
              :key="permission.name"
          >
            <tr>
              <td>{{ permission.name }}</td>
              <td>
                <div class="d-flex justify-end">
                  <VCheckbox
                    v-model="permission.create"
                    label="Create"
                  />
                </div>
              </td>
              <td>
                <div class="d-flex justify-end">
                  <VCheckbox
                      v-model="permission.edit"
                      label="Edit"
                  />
                </div>
              </td>
              <td>
                <div class="d-flex justify-end">
                  <VCheckbox
                    v-model="permission.read"
                    label="Read"
                  />
                </div>
              </td>
              <td>
                <div class="d-flex justify-end">
                  <VCheckbox
                    v-model="permission.deleted"
                    label="Deleted"
                  />
                </div>
              </td>
            </tr>
          </template>
        </VTable>

        <!-- 👉 Actions button -->
        <div class="d-flex flex-wrap gap-4">
          <VBtn @click="onSubmit">
            Submit
          </VBtn>

          <VBtn
              color="secondary"
              variant="tonal"
              @click="onReset"
          >
            Cancel
          </VBtn>
        </div>
      </VForm>
    </VCardText>

  </VCard>
</template>
