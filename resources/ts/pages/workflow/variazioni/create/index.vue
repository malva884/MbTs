<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { VForm } from 'vuetify/components/VForm'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'

definePage({
  meta: {
    action: 'create',
    subject: 'Wf-Variazioni',
  },
})

const { t } = useI18n()
const router = useRouter()
const refForm = ref<VForm>()
const loading = ref(false)
const ol = ref<number | null>(null)
const revisione = ref<number>(0)
const categoria = ref<string | null>(null)
const categorie = ref<any[]>([])
const duplicati = ref<any[]>([])
const checkOlVisible = ref(false)
const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

const editor = useEditor({
  content: '',
  extensions: [
    StarterKit,
    Underline,
  ],
})

const loadCategorie = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/workflow/variazioni/get_categorie'))

  if (resultData.value?.objs)
    categorie.value = resultData.value.objs
}

const checkOl = async () => {
  if (!ol.value)
    return

  const { data: resultData } = await useApi<any>(createUrl('/workflow/variazioni/check', {
    query: { ol: ol.value },
  }))

  duplicati.value = resultData.value || []
  checkOlVisible.value = duplicati.value.length > 0
}

const salva = async () => {
  const valid = await refForm.value?.validate()
  if (!valid?.valid)
    return

  loading.value = true
  try {
    const retuenData = await $api<any>('/workflow/variazioni/store', {
      method: 'POST',
      body: {
        ol: ol.value,
        revisione: revisione.value,
        categoria: categoria.value,
        testo: editor.value?.getHTML() || '',
      },
    })

    message.value = retuenData.message
    color.value = retuenData.color || 'success'
    isSnackbarVisible.value = true

    if (retuenData.success)
      router.push({ name: 'workflow-variazioni-list' })
  }
  catch (e: any) {
    message.value = e?.data?.message || 'Messaggi.Errore'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
  finally {
    loading.value = false
  }
}

const annulla = () => {
  router.push({ name: 'workflow-variazioni-list' })
}

onMounted(() => {
  loadCategorie()
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar
      v-model="isSnackbarVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :timeout="3000"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>

    <VCard
      variant="outlined"
      class="bg-surface border-thin rounded-lg"
    >
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar
            color="primary"
            variant="tonal"
            size="38"
          >
            <VIcon
              icon="tabler-file-plus"
              size="20"
            />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">
              {{ $t('Label.Nuova-Variazione') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ $t('Label.Variazioni') }}
            </div>
          </div>
        </div>
      </VCardText>
      <VDivider />

      <VCardText class="pa-4">
        <VForm
          ref="refForm"
          @submit.prevent="salva"
        >
          <VRow>
            <!-- 👉 OL -->
            <VCol
              cols="12"
              sm="3"
            >
              <AppTextField
                v-model="ol"
                label="OL"
                type="number"
                :rules="[requiredValidator]"
                @focusout="checkOl"
              />
            </VCol>

            <!-- 👉 Revisione -->
            <VCol
              cols="12"
              sm="3"
            >
              <AppTextField
                v-model="revisione"
                :label="$t('Label.Revisione')"
                type="number"
                :rules="[requiredValidator]"
              />
            </VCol>

            <!-- 👉 Categoria -->
            <VCol
              cols="12"
              sm="6"
            >
              <AppSelect
                v-model="categoria"
                :label="$t('Label.Categoria')"
                :items="categorie"
                item-title="categoria"
                item-value="id"
                :rules="[requiredValidator]"
              />
            </VCol>
          </VRow>

          <!-- Alert OL esistente -->
          <VRow v-if="checkOlVisible">
            <VCol cols="12">
              <VAlert
                type="warning"
                variant="tonal"
                density="compact"
                closable
              >
                {{ $t('Label.Variazione-Ol-Esistente') }}
                <span
                  v-for="dup in duplicati"
                  :key="dup.id"
                  class="font-weight-bold"
                >
                  OL {{ dup.ol }} ({{ dup.stato }})
                </span>
              </VAlert>
            </VCol>
          </VRow>

          <!-- 👉 Testo -->
          <VRow>
            <VCol cols="12">
              <p class="text-body-2 text-medium-emphasis mb-2">
                {{ $t('Label.Testo-Variazione') }}
              </p>
              <div class="editor-wrapper border rounded">
                <div
                  v-if="editor"
                  class="editor-toolbar d-flex gap-1 pa-2 border-b"
                >
                  <IconBtn
                    size="small"
                    :color="editor.isActive('bold') ? 'primary' : undefined"
                    @click="editor.chain().focus().toggleBold().run()"
                  >
                    <VIcon icon="tabler-bold" />
                  </IconBtn>
                  <IconBtn
                    size="small"
                    :color="editor.isActive('italic') ? 'primary' : undefined"
                    @click="editor.chain().focus().toggleItalic().run()"
                  >
                    <VIcon icon="tabler-italic" />
                  </IconBtn>
                  <IconBtn
                    size="small"
                    :color="editor.isActive('underline') ? 'primary' : undefined"
                    @click="editor.chain().focus().toggleUnderline().run()"
                  >
                    <VIcon icon="tabler-underline" />
                  </IconBtn>
                  <IconBtn
                    size="small"
                    :color="editor.isActive('bulletList') ? 'primary' : undefined"
                    @click="editor.chain().focus().toggleBulletList().run()"
                  >
                    <VIcon icon="tabler-list" />
                  </IconBtn>
                  <IconBtn
                    size="small"
                    :color="editor.isActive('orderedList') ? 'primary' : undefined"
                    @click="editor.chain().focus().toggleOrderedList().run()"
                  >
                    <VIcon icon="tabler-list-numbers" />
                  </IconBtn>
                </div>
                <EditorContent
                  :editor="editor"
                  class="editor-content pa-3"
                />
              </div>
            </VCol>
          </VRow>

        </VForm>
      </VCardText>
      <VDivider />

      <VCardActions class="pa-4 justify-end">
        <VBtn
          color="error"
          variant="outlined"
          @click="annulla"
        >
          {{ $t('Label.Annulla') }}
        </VBtn>
        <VBtn
          color="primary"
          variant="elevated"
          prepend-icon="tabler-device-floppy"
          :loading="loading"
          @click="salva"
        >
          {{ $t('Label.Salva') }}
        </VBtn>
      </VCardActions>
    </VCard>
  </div>
</template>

<style scoped lang="scss">
.editor-wrapper {
  .editor-toolbar {
    background-color: rgba(var(--v-theme-on-surface), 0.02);
  }

  .editor-content {
    min-height: 200px;

    :deep(.ProseMirror) {
      min-height: 190px;
      outline: none;

      p {
        margin-bottom: 0.5rem;
      }
    }
  }
}
</style>
