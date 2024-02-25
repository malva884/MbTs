<script setup lang="ts">
import UserBioPanel from '@/views/administrations/user/view/UserBioPanel.vue'
import UserTabActivities from '@/views/administrations/user/view/UserTabActivities.vue'

import UserTabPermissions from '@/views/administrations/user/view/UserTabPermissions.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'user',
  },
})

const userData = ref({})
const route = useRoute('administrations-user-view-id')

const fetchUser = async () => {
  const resultData = await useApi<any>(createUrl(`/users/view/${route.params.id}`))

  userData.value = resultData.data.value.user
}

fetchUser()

const userTab = ref(null)

const tabs = [
  { icon: 'tabler-activity', title: 'Attività' },
  { icon: 'tabler-lock', title: 'Permessi' },
]
</script>

<template>
  <VRow v-if="userData">
    <VCol
      cols="12"
      md="5"
      lg="4"
    >
      <UserBioPanel :user-data="userData" />
    </VCol>

    <VCol
      cols="12"
      md="7"
      lg="8"
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
          <UserTabActivities :id="route.params.id" />
        </VWindowItem>

        <VWindowItem>
          <UserTabPermissions :id="route.params.id" />
        </VWindowItem>

      </VWindow>
    </VCol>
  </VRow>
  <VCard v-else>
    <VCardTitle class="text-center">
      No User Found
    </VCardTitle>
  </VCard>
</template>
