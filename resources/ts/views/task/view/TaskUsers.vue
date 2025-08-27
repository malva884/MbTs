<script setup lang="ts">
import type { VForm } from 'vuetify/components/VForm'
import moment from 'moment'
import type { Task } from '@/views/task/type'
import TaskNote from '@/views/task/view/TaskNote.vue'
import TaskAttivita from '@/views/task/view/TaskAttivita.vue'
import ChatLog from '@/views/task/view/ChatLog.vue'
import {useNotStore} from "@/views/task/view/useNoteStore";

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'taskData', value: Task): void
}

interface Props {
  isDrawerOpen: boolean
  taskData: Task
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const isFormValid = ref(false)
const refForm = ref<VForm>()
const taskTab = ref(null)

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)

  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}
</script>

<template>
  <AppSelect
    v-model="value"
    :items="items"
    item-title="name"
    item-value="name"
    label="Select Item"
    placeholder="Select Item"
    multiple
    clearable
    clear-icon="tabler-x"
  >
    <template #selection="{ item }">
      <VChip>
        <template #prepend>
          <VAvatar
            start
            :image="item.raw.avatar"
          />
        </template>

        <span>{{ item.title }}</span>
      </VChip>
    </template>
  </AppSelect>
</template>

