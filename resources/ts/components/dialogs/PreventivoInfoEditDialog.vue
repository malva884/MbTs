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

    <VCard class="pa-sm-8 pa-5">
      <!-- 👉 Title -->
      <VCardItem class="text-center">
        <VCardTitle class="text-h3 mb-3">
          Modifca Preventivo
        </VCardTitle>

      </VCardItem>

      <VCardText class="mt-6">
        <!-- 👉 Form -->
        <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
        >
          <VRow>
            <!-- 👉 First Name -->
            <!-- 👉 Numero -->
            <VCol cols="4">
              <AppTextField
                v-model="preventivoData.numero"
                :label="$t('Label.Numero')"
                :placeholder="$t('Label.Numero')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <!-- 👉 CU -->
            <VCol cols="4">
              <AppTextField
                v-model="preventivoData.cu"
                type="number"
                :label="$t('Label.Base-Cu')"
                :placeholder="$t('Label.Base-Cu')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <!-- 👉 Parametro -->
            <VCol cols="4">
              <AppTextField
                v-model="preventivoData.parametro"
                type="number"
                :label="$t('Label.Parametro')"
                :placeholder="$t('Label.Parametro')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <!-- 👉 Cliente -->
            <VCol cols="12">
              <AppSelect
                v-model="preventivoData.cliente_id"
                :label="$t('Label.Cliente')"
                :placeholder="$t('Label.Cliente')"
                :items="clientiOptions"
                :item-title="item => item.ragione_sociale"
                :item-value="item => item.id"
                :rules="[requiredValidator]"
              />
            </VCol>

            <!-- 👉 Rdo -->
            <VCol cols="6">
              <AppTextField
                v-model="preventivoData.rdo"
                :label="$t('Label.Rdo')"
                :placeholder="$t('Label.Rdo')"
                :rules="[requiredValidator]"
              />
            </VCol>

            <!-- 👉 Data Rdo -->
            <VCol cols="6">
              <AppTextField
                v-model="preventivoData.data_rdo"
                :label="$t('Label.Del')"
                :placeholder="$t('Label.Del')"
                :rules="[requiredValidator]"
              />
            </VCol>

            <!-- 👉 Nota -->
            <VCol cols="6">
              <AppTextField
                v-model="preventivoData.nota"
                :label="$t('Label.Nota')"
                :placeholder="$t('Label.Nota')"

              />
            </VCol>
            <!-- 👉 Submit and Cancel -->
            <VCol
                cols="12"
                class="d-flex flex-wrap justify-center gap-4"
            >
              <VBtn type="submit">
                Salva
              </VBtn>

              <VBtn
                  color="secondary"
                  variant="tonal"
                  @click="onFormReset"
              >
                Cancel
              </VBtn>
            </VCol>
          </VRow>
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

