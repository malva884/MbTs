<!-- eslint-disable vue/no-mutating-props -->
<script setup lang="ts">
interface Emit {
  (e: 'removeProduct', value: number): void

}

interface Props {
  id: number
  tipo: number
  data: {
    coil_t: string
    fo_try: number
    km: number
  }
}

const props = withDefaults(defineProps<Props>(), {
  data: () => ({
    coil_t: '',
    fo_try: '',
    km: '',
  }),
})

const emit = defineEmits<Emit>()
const isDialogVisible = ref(false)

const check_km = (km: number) => {
  if (km >= 100.000)
    isDialogVisible.value = true
}

const correzione = () => {
  props.data.km = null
  isDialogVisible.value = false
}

const removeProduct = () => {
  emit('removeProduct', props.id)
}

</script>

<template>
  <!-- eslint-disable vue/no-mutating-props -->
  <div class="add-products-header mb-4 d-none d-md-flex ps-5 pe-16">
    <VRow class="font-weight-medium">
      <VCol
        cols="12"
        md="6"
      >
        <h6 class="text-sm font-weight-medium">
          <span class="text-base">
            {{$t('Label.Bobine')}}
          </span>
        </h6>
      </VCol>
    </VRow>
  </div>

  <VCard
    flat
    border
    class="d-flex flex-row"
  >
    <!-- 👉 Left Form -->
    <div class="pa-5 flex-grow-1">
      <VRow>
        <VCol
          cols="12"
          md="6"
          sm="6"
        >
          <AppTextField
            v-model="props.data.coil_t"
            type="text"
            :label="$t('Label.Numero-Bobina')"
            :rules="(props.tipo == 5420 ? [requiredValidator]:'')"
            :required="props.tipo == 5420"
          />
        </VCol>

        <VCol
          v-if="props.tipo == 5420"
          cols="12"
          md="3"
          sm="3"
        >
          <AppTextField
            v-model="props.data.fo_try"
            type="number"
            :label="$t('Label.Fibre Provate')"
            :rules="[requiredValidator]"
            required
          />
        </VCol>

        <VCol
          cols="12"
          md="3"
          sm="3"
        >
          <AppTextField
            v-model="props.data.km"
            type="number"
            :label="$t('Label.Chilometri')"
            :rules="[requiredValidator]"
            @focusout="check_km(props.data.km)"
            required
          />
        </VCol>

      </VRow>
    </div>

    <!-- 👉 Item Actions -->
    <div class="d-flex flex-column justify-space-between border-s pa-1">
      <IconBtn @click="removeProduct">
        <VIcon
          size="20"
          icon="tabler-x"
        />
      </IconBtn>

    </div>
  </VCard>

  <VDialog
    v-model="isDialogVisible"
    persistent
    class="v-dialog-sm"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />

    <!-- Dialog Content -->
    <VCard title="Attenzione!!">
      <VCardText>
        Attenzione! hai inserito <span class="text-warning km" >{{ props.data.km }} Km </span>  per la Bobina Numero <span class="text-success font-bold">{{props.data.coil_t}} </span>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <VBtn
          color="error"
          variant="tonal"
          @click="correzione"
        >
          Modifica
        </VBtn>
        <VBtn
          color="success"
          @click="isDialogVisible = false">
          Conferma
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style>
.km {
  font-size: 1.17em;
  margin-block-start: 1em;
  margin-block-end: 1em;
  margin-inline-start: 0px;
  margin-inline-end: 0px;
  font-weight: bold;
  unicode-bidi: isolate;
}
</style>
