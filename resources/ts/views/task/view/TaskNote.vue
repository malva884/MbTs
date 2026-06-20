<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { VForm } from 'vuetify/components/VForm'
import { Placeholder } from '@tiptap/extension-placeholder'
import { TextAlign } from '@tiptap/extension-text-align'
import { Underline } from '@tiptap/extension-underline'
import { StarterKit } from '@tiptap/starter-kit'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import { useNotStore } from '@/views/task/view/useNoteStore'
import type { ChatOut } from '@/views/task/note/type'
import moment from 'moment'

interface Props {
  taskId: string
}

const props = defineProps<Props>()
const refForm = ref<InstanceType<typeof VForm> | null>(null)
const isFormValid = ref(false)
const store = useNotStore()

// Caricamento asincrono iniziale dei dati dello store
await store.getChat(props.taskId)

const editor = useEditor({
  content: '',
  extensions: [
    StarterKit,
    TextAlign.configure({
      types: ['heading', 'paragraph'],
    }),
    Placeholder.configure({
      placeholder: 'Scrivi qui la tua nota o aggiornamento...',
    }),
    Underline,
  ],
})

const onSubmit = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid && editor.value) {
      const htmlContent = editor.value.getHTML()

      // Controllo per non inviare note vuote
      if (htmlContent === '<p></p>' || !htmlContent.trim()) return

      await $api(`/task/task_nota/${props.taskId}`, {
        method: 'POST',
        body: {
          nota: htmlContent,
          padre: '',
        },
      })

      await store.getChat(props.taskId)
      editor.value.commands.setContent('', false)

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })
    }
  })
}

const onFormReset = () => {
  editor.value?.commands.setContent('', false)
  refForm.value?.resetValidation()
}

interface MessageGroup {
  senderId: ChatOut['messages'][number]['senderId']
  messages: Omit<ChatOut['messages'][number], 'senderId'>[]
}

const users = ref<Record<string, any>>({})

// Il tuo ID di contatto di riferimento (lasciato null o associato al sistema per calcolare la sponda destra/sinistra della chat)
const contact = computed(() => ({
  id: null,
  avatar: store.activeChat?.contact?.avatar,
}))

const msgGroups = computed(() => {
  let messages: ChatOut['messages'] = []
  const _msgGroups: MessageGroup[] = []

  if (store.activeChat?.chat && store.activeChat.chat.length > 0) {
    messages = store.activeChat.chat
    users.value = store.activeChat.users || {}

    let msgSenderId = messages[0].senderId

    let msgGroup: MessageGroup = {
      senderId: msgSenderId,
      messages: [],
    }

    messages.forEach((msg, index) => {
      if (msgSenderId === msg.senderId) {
        msgGroup.messages.push({
          message: msg.message,
          time: msg.time,
          feedback: msg.feedback,
        })
      } else {
        msgSenderId = msg.senderId
        _msgGroups.push(msgGroup)
        msgGroup = {
          senderId: msg.senderId,
          messages: [
            {
              message: msg.message,
              time: msg.time,
              feedback: msg.feedback,
            },
          ],
        }
      }

      if (index === messages.length - 1)
        _msgGroups.push(msgGroup)
    })
  }

  return _msgGroups
})

function formatDate(date: string): string {
  if (!date) return ''
  return moment(String(date)).format('DD/MM/YYYY HH:mm')
}
</script>

<template>
  <div class="d-flex flex-column gap-y-4">

    <VCard variant="flat" border class="d-flex flex-column bg-var-theme-background">
      <div class="chat-log-container pa-4">
        <div v-if="msgGroups.length === 0" class="d-flex flex-column align-center justify-center text-disabled py-8 gap-y-2">
          <VIcon icon="tabler-message-off" size="32" />
          <span class="text-body-2 font-weight-medium">Nessuna nota presente su questo task.</span>
        </div>

        <div
          v-for="(msgGrp, index) in msgGroups"
          :key="msgGrp.senderId + String(index)"
          class="chat-group-wrapper d-flex align-start mb-4"
          :class="Number(msgGrp.senderId) === contact.id ? 'justify-end' : 'justify-start'"
        >
          <div
            class="d-flex flex-column max-w-75"
            :class="Number(msgGrp.senderId) === contact.id ? 'align-end' : 'align-start'"
          >
            <span class="text-xs font-weight-bold mb-1 text-primary px-1">
              {{ users[msgGrp.senderId]?.name || 'Utente' }}
            </span>

            <div
              v-for="msgData in msgGrp.messages"
              :key="msgData.time"
              class="mb-2 w-100"
            >
              <VCard
                variant="flat"
                class="pa-3 rounded-lg text-body-2 message-bubble shadow-sm"
                :color="Number(msgGrp.senderId) === contact.id ? 'primary' : 'surface'"
                :theme="Number(msgGrp.senderId) === contact.id ? 'dark' : undefined"
                border
              >
                <div v-html="msgData.message" class="tiptap-rendered-content" />

                <div class="d-flex align-center justify-end mt-1 gap-x-1">
                  <span class="text-xxs opacity-70">{{ formatDate(msgData.time) }}</span>
                  <VIcon
                    v-if="Number(msgGrp.senderId) === contact.id"
                    :icon="msgData.feedback?.isSeen ? 'tabler-checks' : 'tabler-check'"
                    size="12"
                    class="opacity-70"
                  />
                </div>
              </VCard>
            </div>
          </div>
        </div>
      </div>
    </VCard>

    <VCard variant="flat" border class="pa-4 bg-surface rounded-lg">
      <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
        <div class="d-flex align-center gap-2 mb-2">
          <VIcon icon="tabler-plus" size="18" class="text-primary" />
          <span class="text-body-2 font-weight-bold text-high-emphasis">Aggiungi un aggiornamento</span>
        </div>

        <div class="tiptap-editor-wrapper border rounded-lg overflow-hidden mb-3">
          <div class="pa-3 bg-var-theme-background border-bottom min-h-100">
            <EditorContent :editor="editor" />
          </div>

          <div v-if="editor" class="d-flex align-center justify-space-between bg-surface pa-2 border-top flex-wrap gap-2">
            <div class="d-flex align-center gap-x-1">
              <VBtn
                icon="tabler-bold"
                variant="text"
                size="small"
                :color="editor.isActive('bold') ? 'primary' : 'secondary'"
                @click="editor.chain().focus().toggleBold().run()"
              />
              <VBtn
                icon="tabler-underline"
                variant="text"
                size="small"
                :color="editor.isActive('underline') ? 'primary' : 'secondary'"
                @click="editor.commands.toggleUnderline()"
              />
              <VBtn
                icon="tabler-italic"
                variant="text"
                size="small"
                :color="editor.isActive('italic') ? 'primary' : 'secondary'"
                @click="editor.chain().focus().toggleItalic().run()"
              />
              <VDivider vertical inset class="mx-1" />
              <VBtn
                icon="tabler-align-left"
                variant="text"
                size="small"
                :color="editor.isActive({ textAlign: 'left' }) ? 'primary' : 'secondary'"
                @click="editor.chain().focus().setTextAlign('left').run()"
              />
              <VBtn
                icon="tabler-align-center"
                variant="text"
                size="small"
                :color="editor.isActive({ textAlign: 'center' }) ? 'primary' : 'secondary'"
                @click="editor.chain().focus().setTextAlign('center').run()"
              />
              <VBtn
                icon="tabler-align-right"
                variant="text"
                size="small"
                :color="editor.isActive({ textAlign: 'right' }) ? 'primary' : 'secondary'"
                @click="editor.chain().focus().setTextAlign('right').run()"
              />
            </div>

            <div class="d-flex align-center gap-x-2">
              <VBtn
                size="small"
                color="secondary"
                variant="tonal"
                prepend-icon="tabler-x"
                class="font-weight-semibold"
                @click="onFormReset"
              >
                Annulla
              </VBtn>
              <VBtn
                type="submit"
                size="small"
                color="success"
                prepend-icon="tabler-send"
                class="font-weight-semibold"
              >
                Invia Nota
              </VBtn>
            </div>
          </div>
        </div>
      </VForm>
    </VCard>

  </div>
</template>

<style lang="scss">
.chat-log-container {
  height: 420px;
  overflow-y: auto;
  overflow-x: hidden;
  display: flex;
  flex-direction: column;
}

.max-w-75 {
  max-width: 75%;
}

.text-xxs {
  font-size: 0.7rem !important;
}

.message-bubble {
  line-height: 1.5;
  word-break: break-word;
}

.tiptap-editor-wrapper {
  border-color: rgba(var(--v-border-color), var(--v-border-opacity)) !important;

  .border-bottom {
    border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }
}

// Stili Interni all'Editor Rich-Text Prosemirror / Tiptap
.ProseMirror {
  padding: 4px;
  min-height: 90px;
  max-height: 200px;
  overflow-y: auto;

  &:focus-visible {
    outline: none !important;
  }

  p.is-editor-empty:first-child::before {
    color: rgba(var(--v-theme-on-surface), var(--v-disabled-opacity));
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
  }
}

.tiptap-rendered-content {
  p {
    margin-bottom: 0px !important;
  }
}
</style>
