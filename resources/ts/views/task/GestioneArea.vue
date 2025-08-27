<script setup lang="ts">
import {useI18n} from 'vue-i18n'
import MenbriArea from "@/views/task/aree/MenbriArea.vue";
import AutorizzazioniArea from "@/views/task/aree/AutorizzazioniArea.vue";
import ImpostazioniArea from "@/views/task/aree/ImpostazioniArea.vue";

interface Emit {
  (e: 'update:isGestioneVisibile', value: boolean): void
}

interface Props {
  areaId: string
  responsabile: boolean
  responsabileArea: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const {t} = useI18n()
const areaTab = ref(null)
const area = ref({})
const tabs = []
const view = ref(false)

const loadArea = async () => {
  const { data: areaData } = await useApi<any>(createUrl(`/task/aree/view/${props.areaId}`))

  area.value = areaData.value
  view.value = true
}

tabs.length = 0
if (props.responsabileArea)
  tabs.push({ icon: 'tabler-settings', title: t('Label.Impostazioni') })

tabs.push(
  { icon: 'tabler-users', title: t('Label.Membri') },
  { icon: 'tabler-key-off', title: t('Label.Autorizzazioni') },
)
loadArea()

watch(props, () => {
  tabs.length = 0
  if (props.responsabileArea)
    tabs.push({ icon: 'tabler-settings', title: t('Label.Impostazioni') })

  tabs.push(
    { icon: 'tabler-users', title: t('Label.Membri') },
    { icon: 'tabler-key-off', title: t('Label.Autorizzazioni') },
  )
  loadArea()
})
</script>

<template>
  <VCard :title="`Grstione Area - ${area.area}`">

    <VCol v-if="view" cols="12">
      <VTabs
        v-model="areaTab"
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
        v-model="areaTab"
        class="mt-6 disable-tab-transition"
        :touch="false"
      >
        <VWindowItem v-if="props.responsabileArea">
          <ImpostazioniArea
            :area-id="props.areaId"
            :responsabile="props.responsabile"
          />
        </VWindowItem>

        <VWindowItem>
          <MenbriArea
            :area-id="props.areaId"
            :responsabile="props.responsabile"
          />
        </VWindowItem>

        <VWindowItem>
          <AutorizzazioniArea
            :area-id="props.areaId"
            :responsabile="props.responsabile"
          />
        </VWindowItem>
      </VWindow>
    </VCol>
  </VCard>
</template>

<style scoped lang="scss">
</style>
