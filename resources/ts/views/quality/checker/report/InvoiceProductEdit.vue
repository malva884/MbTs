<!-- eslint-disable vue/no-mutating-props -->
<script setup lang="ts">
interface Emit {
  (e: 'removeProduct', value: number): void

}

interface Props {
  id: number
  data: {
    coil_t: string
    fo_try: number
  }
}

const props = withDefaults(defineProps<Props>(), {
  data: () => ({
    coil_t: '',
    fo_try: '',
  }),
})

const emit = defineEmits<Emit>()

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
            Bobbine
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
              label="Numero Bobbina"
              :rules="[requiredValidator]"
              required
          />
        </VCol>

        <VCol
          cols="12"
          md="6"
          sm="6"
        >
          <AppTextField
            v-model="props.data.fo_try"
            type="number"
            label="Fibre Provate"
            :rules="[requiredValidator]"
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
</template>
