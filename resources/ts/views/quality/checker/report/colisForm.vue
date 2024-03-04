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
    coil: '',
    fo_try: null,
  })

}

// 👉 Remove Product edit section
const removeProduct = (id: number) => {alert('si')
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
          class="my-4 ma-sm-4"
      >
        <InvoiceProductEdit
            :id="index"
            :data="col"
            @remove-product="removeProduct"
        />
      </div>

      <div class="mt-4 ma-sm-4">
        <VBtn @click="addItem">
          Add Item
        </VBtn>
      </div>
    </VCardText>

  </VCard>
</template>
