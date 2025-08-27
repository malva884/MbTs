<script setup lang="ts">
// eslint-disable-next-line @typescript-eslint/consistent-type-imports
import type { VForm } from 'vuetify/components/VForm'
import type { Cavo } from '@/views/offices/technical/cables/type'


interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'cavoData', value: Cavo): void
}

interface Props {
  cavoData?: Cavo
  isDrawerOpen: boolean
}

const props = withDefaults(defineProps<Props>(), {
  cavoData: () => ({
    id: 0,
    codice: '',
    categoria_id: '',
    categoria: '',
    descrizione: '',
  }),
})


const emit = defineEmits<Emit>()
const cavoData = ref<Cavo>(structuredClone(toRaw(props.cavoData)))
const isFormValid = ref(false)
const refForm = ref<VForm>()

watch(props, () => {
  cavoData.value = structuredClone(toRaw(props.cavoData))
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
      emit('cavoData', cavoData.value)
      emit('update:isDrawerOpen', false)
      nextTick(() => {
        //refForm.value?.reset()
        refForm.value?.resetValidation()
      })

    }
  })
}

const categorieOptions = ref([])

const catOptions = async () => {
  const {data: resultData} = await useApi<any>(createUrl('/to/categorie/get_list', {
    query: {
      modulo: 1,
    },
  }))

  const arr = []

  resultData.value.forEach(value => {
    arr.push({full_name: value.categoria, id: value.id})
  })

  categorieOptions.value = arr
}

catOptions()

const onFormReset = () => {

  cavoData.value = structuredClone(toRaw(props.cavoData))

  emit('update:isDrawerOpen', false)
}

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}
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
          Modifca Cavo
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
            <VCol
                cols="12"
                md="6"
            >
              <AppTextField
                  v-model="cavoData.codice"
                  :rules="[requiredValidator]"
                  :label="$t('Label.Codice')"
                  :placeholder="$t('Label.Codice')"
              />
            </VCol>

            <!-- 👉 Last Name -->
            <VCol
                cols="12"
                md="6"
            >
              <AppTextField
                  v-model="cavoData.descrizione"
                  :rules="[requiredValidator]"
                  :label="$t('Label.Descrizione')"
                  :placeholder="$t('Label.Descrizione')"
              />
            </VCol>

            <!-- 👉 Categoria -->
            <VCol cols="6">
              <AppSelect
                v-model="cavoData.categoria_id"
                :label="$t('Label.Categoria')"
                :placeholder="$t('Label.Categoria')"
                :items="categorieOptions"
                item-title="full_name"
                item-value="id"
                :rules="[requiredValidator]"
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

