<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'

interface PermissionData {
  id: number | null
  name: string
}

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'permissionData', value: PermissionData): void
}

interface Props {
  permissionData?: PermissionData
  isDialogVisible: boolean
}

const props = withDefaults(defineProps<Props>(), {
  permissionData: () => ({
    id: 0,
    name: '',
  }),
})

const emit = defineEmits<Emit>()
// eslint-disable-next-line @typescript-eslint/no-unused-vars
const permissionData = ref<PermissionData>(structuredClone(toRaw(props.permissionData)))
const isFormValid = ref(false)
const refForm = ref<VForm>()

watch(props, () => {
  permissionData.value = structuredClone(toRaw(props.permissionData))
})

const onReset = () => {
  permissionData.value = structuredClone(toRaw(props.permissionData))
  // eslint-disable-next-line vue/require-explicit-emits
  emit('update:isDialogVisible', false)
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('permissionData', permissionData.value)
      emit('update:isDialogVisible', false)
      nextTick(() => {
        //refForm.value?.reset()
        refForm.value?.resetValidation()
      })
    }
  })
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 600"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <!-- 👉 dialog close btn -->
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-sm-8 pa-5">
      <!-- 👉 Title -->
      <VCardItem class="text-center">
        <VCardTitle class="text-h5">
          {{ permissionData.id ? 'Edit' : 'Add' }} Permission
        </VCardTitle>
        <VCardSubtitle>
          {{ permissionData.id ? 'Edit' : 'Add' }}  permission as per your requirements.
        </VCardSubtitle>
      </VCardItem>

      <VCardText class="mt-1">
        <!-- 👉 Form -->
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <VAlert
            type="warning"
            title="Warning!"
            variant="tonal"
            class="mb-6"
          >
            By editing the permission name, you might break the system permissions functionality. Please ensure you're absolutely certain before proceeding.
          </VAlert>

          <!-- 👉 Role name -->
          <div class="d-flex align-end gap-3 mb-3">
            <AppTextField
              v-model="permissionData.name"
              :rules="[requiredValidator]"
              density="compact"
              label="Permission Name"
              placeholder="Enter Permission Name"
            />

            <VBtn type="submit">
              Salva
            </VBtn>
          </div>

          <VCheckbox label="Set as core permission" />
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.permission-table {
  td {
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding-block: 0.5rem;
    padding-inline: 0;
  }
}
</style>
