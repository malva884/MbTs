<script setup lang="ts">
import InvoiceProductEdit from './InvoiceProductEdit.vue'

export interface editedItem {
  coils: Coils[]
}

interface Props {
  data: editedItem
}

export interface Coils {
  coil: string
  fo_try: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'push', value: Coils): void
  (e: 'remove', id: number): void
}>()

// 👉 Add item function
const addItem = () => {
  emit('push', {
    coil_t: '',
    fo_try: null,
  })

}

// 👉 Remove Product edit section
const removeProduct = (id: number) => {
  emit('remove', id)
}
</script>

<template>
  <VCard>
    <VDivider />
    <!-- 👉 Add purchased products -->
    <VCardText class="add-products-form">

      <div
          v-for="(col, index) in props.data.coils"
          :key="col.coil"
          class="my-1 ma-sm-1"
      >
        <InvoiceProductEdit
            :id="index"
            :data="col"
            @remove-product="removeProduct"
        />
      </div>

      <div class="mt-1 ma-sm-1">
        <VBtn @click="addItem">
            <VIcon
                    size="20"
                    icon="tabler-plus"
            />
           Bobbina
        </VBtn>
      </div>
    </VCardText>

  </VCard>
</template>
