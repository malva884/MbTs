<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import LoadingStandBy from '@/components/LoadingStandBy.vue'
import type { QuestionarioQualita } from '@/views/quality/fornitore/certificazioni/view/type'

interface Props {
  certificato: object
}

interface Emit {
  (e: 'isSuccess', value: boolean): void
}

const props = defineProps<Props>()

const emit = defineEmits<Emit>()

const defaultItem = ref<QuestionarioQualita>({
  id: '',
  certificato_id: '',
  supplier_id: '',
  stato: true,
  r_1: null,
  r_2: null,
  r_3: null,
  r_4: null,
  r_5: null,
  r_6: null,
  r_7: null,
  r_8: null,
  r_9: null,
  r_10: null,
  r_11: null,
  r_12: null,
  r_13: null,
  r_14: null,
  r_15: null,
  r_16: null,
  r_17: null,
  r_18: null,
  r_19: null,
  r_20: null,
  r_21: null,
})

const editedItem = ref<QuestionarioQualita>(defaultItem.value)
const editedIndex = ref(-1)

const { t } = useI18n()
const notificaVisible = ref(false)
const message = ref('')
const color = ref('')
const check = ref(0)
const loadingPage = ref(false)
const isDialogVisible = ref(false)

const confirm = () => {
  isDialogVisible.value = true
}

const get_questionario = async () => {
  const { data: questionarioObj } = await useApi<any>(createUrl(`qt/certification/getQuestionario/${props.certificato.certificato_id}`,{
    query: {
      fornitore_id: props.certificato.fornitore_id,
    },
  }))

  editedItem.value = { ...questionarioObj.value }
  check.value = check.value + 1
}

get_questionario()
watch(props, () => {
  get_questionario()
})
</script>

<template>
  <LoadingStandBy v-model="loadingPage" />
  <VSnackbar
    v-model="notificaVisible"
    :color="color"
    location="top"
  >
    {{ t(message) }}
  </VSnackbar>
  <VCardTitle class="mb-5 text-primary text-center text-h3">
    {{ `${t('Label.Questionario')} : ${props.certificato.titolo}` }}
  </VCardTitle>
  <VCardText>
    <VForm @submit.prevent="() => {}">
      <VRow :key="check">
        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-1"
              >{{ t('Questionario-9001.d1') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_1"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="si"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="no"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-2"
              >{{ t('Questionario-9001.d2') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_2"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="3"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-3"
              >{{ t('Questionario-9001.d3') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_3"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="3"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-4"
              >{{ t('Questionario-9001.d4') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_4"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="3"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-5"
              >{{ t('Questionario-9001.d5') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_5"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="1"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-6"
              >{{ t('Questionario-9001.d6') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_6"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="1"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-7"
              >{{ t('Questionario-9001.d7') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_7"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="3"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-8"
              >{{ t('Questionario-9001.d8') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_8"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="3"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-9"
              >{{ t('Questionario-9001.d9') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_9"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="3"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-10"
              >{{ t('Questionario-9001.d10') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_10"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="3"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-11"
              >{{ t('Questionario-9001.d11') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_11"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="5"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-12"
              >{{ t('Questionario-9001.d12') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_12"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="7"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-13"
              >{{ t('Questionario-9001.d13') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_13"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="9"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-14"
              >{{ t('Questionario-9001.d14') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_14"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="9"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-15"
              >{{ t('Questionario-9001.d15') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_15"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="5"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-16"
              >{{ t('Questionario-9001.d16') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_16"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="7"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-17"
              >{{ t('Questionario-9001.d17') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_17"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="3"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-18"
              >{{ t('Questionario-9001.d18') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_18"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="3"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-19"
              >{{ t('Questionario-9001.d19') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_19"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="3"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-20"
              >{{ t('Questionario-9001.d20') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_20"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="5"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-21"
              >{{ t('Questionario-9001.d21') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_21"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="1"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>
      </VRow>
    </VForm>
  </VCardText>
</template>

<style scoped lang="scss">
.v-col-12{
  width: 100%;
  padding: 1px!important;
}
</style>
