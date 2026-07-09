<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'

interface PermissionData {
  id: number | null
  name: string
  module?: string
  permissionType?: string
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
    module: '',
    permissionType: '',
  }),
})

const emit = defineEmits<Emit>()
const permissionData = ref<PermissionData>(structuredClone(toRaw(props.permissionData)))
const isFormValid = ref(false)
const refForm = ref<VForm>()

interface SelectOption {
  title: string
  value: string
}

const moduleOptions = ref<SelectOption[]>([])
const permissionTypeOptions = ref<SelectOption[]>([])

const fetchModuleOptions = async () => {
  try {
    const { data } = await useApi<SelectOption[]>('/admin/permissions/modules')
    moduleOptions.value = data.value ?? []
  } catch (error) {
    console.error('Error fetching module options:', error)
  }
}

const fetchPermissionTypeOptions = async () => {
  try {
    const { data } = await useApi<SelectOption[]>('/admin/permissions/types')
    permissionTypeOptions.value = data.value ?? []
  } catch (error) {
    console.error('Error fetching permission type options:', error)
  }
}

watch(() => props.isDialogVisible, (visible) => {
  if (visible) {
    fetchModuleOptions()
    fetchPermissionTypeOptions()
    permissionData.value = structuredClone(toRaw(props.permissionData))
  }
})

const onReset = () => {
  permissionData.value = structuredClone(toRaw(props.permissionData))
  emit('update:isDialogVisible', false)
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      // Build permission name from module and type only for new permissions
      if (!permissionData.value.id && permissionData.value.module && permissionData.value.permissionType) {
        permissionData.value.name = `${permissionData.value.module}.${permissionData.value.permissionType}`
      }

      emit('permissionData', permissionData.value)
      emit('update:isDialogVisible', false)
      nextTick(() => {
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
          {{ permissionData.id ? $t('Label.Modifica-Permesso') : $t('Label.Aggiungi-Permesso') }}
        </VCardTitle>
        <VCardSubtitle>
          {{ permissionData.id ? $t('Label.Modifica-Permesso') : $t('Label.Aggiungi-Permesso') }}  {{ $t('Label.Descrizione') }}
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
            :title="$t('Label.Attenzione')"
            variant="tonal"
            class="mb-6"
          >
            {{ $t('Label.Avviso-Modifica-Permesso') }}
          </VAlert>

          <!-- Module Selection -->
          <div class="mb-3">
            <AppSelect
              v-model="permissionData.module"
              :items="moduleOptions"
              item-title="title"
              item-value="value"
              :label="$t('Label.Moduli')"
              :placeholder="$t('Label.Moduli')"
              density="compact"
              clearable
            />
          </div>

          <!-- Permission Type Selection -->
          <div class="mb-3">
            <AppSelect
              v-model="permissionData.permissionType"
              :items="permissionTypeOptions"
              item-title="title"
              item-value="value"
              :label="$t('Label.Tipo-Permesso')"
              :placeholder="$t('Label.Tipo-Permesso')"
              density="compact"
              clearable
            />
          </div>

          <!-- 👉 Permission name (auto-filled or manual) -->
          <div class="d-flex align-end gap-3 mb-3">
            <AppTextField
              v-model="permissionData.name"
              density="compact"
              :label="$t('Label.Nome-Permesso')"
              :placeholder="$t('Label.Nome-Permesso')"
              readonly
            />
          </div>

        </VForm>
      </VCardText>

      <VCardActions class="d-flex justify-end gap-3">
        <VBtn
          color="error"
          variant="outlined"
          @click="onReset"
        >
          {{ $t('Label.Annulla') }}
        </VBtn>

        <VBtn
          color="success"
          variant="elevated"
          @click="onSubmit"
        >
          {{ permissionData.id ? $t('Label.Aggiorna') : $t('Label.Crea') }}
        </VBtn>
      </VCardActions>
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
