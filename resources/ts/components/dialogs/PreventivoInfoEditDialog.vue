<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import type { Preventivo } from '@/views/offices/technical/quote/type'
import { Cliente } from '@/views/offices/technical/quote/type'

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'preventivoData', value: Preventivo): void
}

interface Props {
  preventivoData?: Preventivo
  isDrawerOpen: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const preventivoData = ref<Preventivo>(structuredClone(toRaw(props.preventivoData)))
const isFormValid = ref(false)
const refForm = ref<VForm>()
const clientiOptions = ref([])

watch(props, () => {
  preventivoData.value = structuredClone(toRaw(props.preventivoData))
})

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)

  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      nextTick(() => {
        emit('preventivoData', preventivoData.value)
        emit('update:isDrawerOpen', false)
        //refForm.value?.reset()
        refForm.value?.resetValidation()
      })
    }
  })
}


const { data: clientiData } = await useApi<any>(createUrl('/to/clienti/get_list/'))

const onFormReset = () => {

  preventivoData.value = structuredClone(toRaw(props.cavoData))

  emit('update:isDrawerOpen', false)
}

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}

onMounted(() => {
  clientiOptions.value = clientiData.value
})
</script>

<template>
  <VDialog
      :width="$vuetify.display.smAndDown ? 'auto' : 1000"
      :model-value="props.isDrawerOpen"
      @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Dialog close btn -->
    <DialogCloseBtn @click="closeNavigationDrawer" />

    <VCard>
      <VCardItem class="py-3">
        <template #prepend>
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-file-invoice" size="20" />
          </VAvatar>
        </template>
        <VCardTitle>Modifica Preventivo</VCardTitle>
        <VCardSubtitle>N° {{ preventivoData?.numero }}</VCardSubtitle>
      </VCardItem>
      <VDivider />

      <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
        <!-- Sezione: Dati Principali -->
        <VCardText class="pb-2 pt-4">
          <div class="dialog-section-label text-primary">
            <VIcon icon="tabler-clipboard-list" size="16" />
            <span>Dati Principali</span>
          </div>
          <VRow class="mt-2">
            <VCol cols="12" sm="4">
              <AppTextField
                v-model="preventivoData.numero"
                :label="$t('Label.Numero')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField
                v-model="preventivoData.cu"
                type="number"
                :label="$t('Label.Base-Cu')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField
                v-model="preventivoData.parametro"
                type="number"
                :label="$t('Label.Parametro')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12">
              <AppSelect
                v-model="preventivoData.cliente_id"
                :label="$t('Label.Cliente')"
                :items="clientiOptions"
                :item-title="item => item.ragione_sociale"
                :item-value="item => item.id"
                :rules="[requiredValidator]"
              />
            </VCol>
          </VRow>
        </VCardText>

        <!-- Sezione: Riferimento -->
        <VDivider />
        <VCardText class="pb-2 pt-3">
          <div class="dialog-section-label text-warning">
            <VIcon icon="tabler-file-description" size="16" />
            <span>Riferimento RDO</span>
          </div>
          <VRow class="mt-2">
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="preventivoData.rdo"
                :label="$t('Label.Rdo')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="preventivoData.data_rdo"
                :label="$t('Label.Del')"
                :rules="[requiredValidator]"
              />
            </VCol>
          </VRow>
        </VCardText>

        <!-- Sezione: Note -->
        <VDivider />
        <VCardText class="pb-2 pt-3">
          <div class="dialog-section-label text-info">
            <VIcon icon="tabler-note" size="16" />
            <span>Note</span>
          </div>
          <VRow class="mt-2">
            <VCol cols="12">
              <AppTextField v-model="preventivoData.nota" :label="$t('Label.Nota')" />
            </VCol>
          </VRow>
        </VCardText>

        <VDivider />
        <VCardText class="d-flex justify-end gap-3 py-3">
          <VBtn variant="tonal" color="secondary" prepend-icon="tabler-x" @click="onFormReset">Annulla</VBtn>
          <VBtn type="submit" color="primary" prepend-icon="tabler-device-floppy">Salva</VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.dialog-section-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.permission-table {
  td {
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding-block: 0.5rem;

    .v-checkbox {
      min-inline-size: 4.75rem;
    }

    &:not(:first-child) {
      padding-inline: 0.5rem;
    }

    .v-label {
      white-space: nowrap;
    }
  }
}
</style>

