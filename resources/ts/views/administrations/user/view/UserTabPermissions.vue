<script lang="ts" setup>
import { VForm } from 'vuetify/components/VForm'
import type { Permission } from '@/views/administrations/permission/type'

interface Props {
  id: number
}

const props = defineProps<Props>()
const permissions = ref<Permission[]>([])
const isSelectAll = ref(false)
const refPermissionForm = ref<VForm>()
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')

const fetchPermissions = async () => {
  const usersData = await useApi<Permission>(createUrl(`/admin/permissions/tab/${props.id}`))

  permissions.value = usersData.data.value.userPermissions
}

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
    list: val,
    read: val,
    edit: val,
    create: val,
    deleted: val,
    import: val,
    sing: val,
    report: val,
    notification: val,
  }))
})

const onSubmit = async () => {
  const retuenData = await $api(`/admin/permissions/set/${props.id}`, {
    method: 'POST',
    body: permissions.value,
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
}

const onReset = async () => {
  await fetchPermissions()
}

onMounted(() => {
  fetchPermissions()
})
</script>

<template>
  <VCard class="user-tab-notification">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
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
            <td colspan="8">
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
          <template
              v-for="permission in permissions"
              :key="permission.name"
          >
            <tr>
              <td>{{ permission.name }}</td>
              <td>
                <div class="d-flex justify-end" v-if="permission.list !== null && permission.list !== undefined" >
                  <VCheckbox
                    v-model="permission.list"
                    label="Lista"
                  />
                </div>
              </td>
              <td>
                <div class="d-flex justify-end" v-if="permission.create !== null && permission.create !== undefined" >
                  <VCheckbox
                    v-model="permission.create"
                    label="Create"
                  />
                </div>
              </td>
              <td>
                <div class="d-flex justify-end" v-if="permission.edit !== null && permission.edit !== undefined">
                  <VCheckbox
                      v-model="permission.edit"
                      label="Edit"
                  />
                </div>
              </td>
              <td>
                <div class="d-flex justify-end" v-if="permission.read !== null && permission.read !== undefined">
                  <VCheckbox
                    v-model="permission.read"
                    label="Read"
                  />
                </div>
              </td>
              <td>
                <div class="d-flex justify-end" v-if="permission.notification !== null && permission.notification !== undefined">
                  <VCheckbox
                    v-model="permission.notification"
                    label="Notification"
                  />
                </div>
              </td>
              <td>
                <div class="d-flex justify-end" v-if="permission.sing !== null && permission.sing !== undefined">
                  <VCheckbox
                    v-model="permission.sing"
                    label="Sing"
                  />
                </div>
              </td>
              <td>
                <div class="d-flex justify-end" v-if="permission.report !== null && permission.report !== undefined">
                  <VCheckbox
                    v-model="permission.report"
                    label="Report"
                  />
                </div>
              </td>
              <td>
                <div class="d-flex justify-end" v-if="permission.import !== null && permission.import !== undefined">
                  <VCheckbox
                    v-model="permission.import"
                    label="Import"
                  />
                </div>
              </td>
              <td>
                <div class="d-flex justify-end" v-if="permission.deleted !== null && permission.deleted !== undefined">
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
