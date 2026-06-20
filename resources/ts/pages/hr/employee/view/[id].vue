<script setup lang="ts">
import EmployeeBioPanel from '@/views/hr/employee/view/EmployeeBioPanel.vue'
import EmployeeTabTranings from '@/views/hr/employee/view/EmployeeTabTranings.vue'
import EmployeeTabAttendances from '@/views/hr/employee/view/EmployeeTabAttendances.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Users',
  },
})

const dipendenteData = ref({})
const hidden = ref(false)
const route = useRoute('hr-employee-view-id')

const fetchUser = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/hr/dipendenti/view/${route.params.id}`))

  dipendenteData.value = resultData.value
}

fetchUser()

const userTab = ref(null)

const tabs = [
  { icon: 'tabler-book', title: 'Formazioni' },
  { icon: 'tabler-calendar-event', title: 'Presenze' },
]

const hiddenEmploee = () => {
  hidden.value = true
}

const viewEmploee = () => {
  hidden.value = false
}
</script>

<template>
  <VRow v-if="dipendenteData">
    <VCol
      v-if="!hidden"
      cols="12"
      md="4"
      lg="3"
    >
      <EmployeeBioPanel
        :employee-data="dipendenteData"
        @update:hidden="hiddenEmploee"
      />
    </VCol>
    <VCol
      v-else
      cols="1"
      md="1"
      lg="1"
    >
      <VBtn
        variant="text"
        icon="tabler-user"
        @click="viewEmploee"
      />
    </VCol>

    <VCol
      cols="12"
      md="hidden ? '9' : '8'"
      :lg="hidden ? '10' : '9'"
    >
      <VTabs
        v-model="userTab"
        class="v-tabs-pill"
      >
        <VTab
          v-for="tab in tabs"
          :key="tab.icon"
        >
          <VIcon
            :size="18"
            :icon="tab.icon"
            class="me-1"
          />
          <span>{{ tab.title }}</span>
        </VTab>
      </VTabs>

      <VWindow
        v-model="userTab"
        class="mt-6 disable-tab-transition"
        :touch="false"
      >

        <VWindowItem>
          <EmployeeTabTranings :id="route.params.id" />
        </VWindowItem>

        <!--VWindowItem>
          <EmployeeTabAttendances :id="route.params.id" />
        </VWindowItem -->
      </VWindow>
    </VCol>
  </VRow>
  <VCard v-else>
    <VCardTitle class="text-center">
      No User Found
    </VCardTitle>
  </VCard>
</template>
