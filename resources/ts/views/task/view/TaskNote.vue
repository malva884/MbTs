<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { Placeholder } from '@tiptap/extension-placeholder'
import { TextAlign } from '@tiptap/extension-text-align'
import { Underline } from '@tiptap/extension-underline'
import { StarterKit } from '@tiptap/starter-kit'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import { useNotStore } from '@/views/task/view/useNoteStore'
import type { ChatOut } from '@/views/task/note/type'

interface Props {
  taskId: string
}

const props = defineProps<Props>()
const refForm = ref<VForm>()
const isFormValid = ref(false)

const editor = useEditor({
  content: '',
  extensions: [
    StarterKit,
    TextAlign.configure({
      types: ['heading', 'paragraph'],
    }),
    Placeholder.configure({
      placeholder: 'Inserire Nota....',
    }),
    Underline,
  ],
})

const onSubmit = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      await $api(`/task/task_nota/${props.taskId}`, {
        method: 'POST',
        body: {
          nota: editor.value?.getHTML(),
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

const store = useNotStore()

await store.getChat(props.taskId)

interface MessageGroup {
  senderId: ChatOut['messages'][number]['senderId']
  messages: Omit<ChatOut['messages'][number], 'senderId'>[]
}

const users = ref({})

const contact = computed(() => ({
  id: null, // store.activeChat?.contact.id,
  avatar: store.activeChat?.contact.avatar,
}))

const msgGroups = computed(() => {
  let messages: ChatOut['messages'] = []

  /*
      let messages: ChatOut['messages'] = [
        {
          message: 'Hi',
          time: 'Mon Dec 10 2018 07:45:00 GMT+0000 (GMT)',
          senderId: 121,
          feedback: {
            isSent: true,
            isDelivered: true,
            isSeen: true,
          },
        },
        {
          message: 'Hello. How can I help You?',
          time: 'Mon Dec 11 2018 07:45:15 GMT+0000 (GMT)',
          senderId: 2,
          feedback: {
            isSent: true,
            isDelivered: true,
            isSeen: true,
          },
        },
        {
          message: 'Can I get details of my last transaction I made last month? 🤔',
          time: 'Mon Dec 11 2018 07:46:10 GMT+0000 (GMT)',
          senderId: 121,
          feedback: {
            isSent: true,
            isDelivered: true,
            isSeen: true,
          },
        },
        {
          message: 'We need to check if we can provide you such information.',
          time: 'Mon Dec 11 2018 07:45:15 GMT+0000 (GMT)',
          senderId: 2,
          feedback: {
            isSent: true,
            isDelivered: true,
            isSeen: true,
          },
        },
        {
          message: 'I will inform you as I get update on this.',
          time: 'Mon Dec 11 2018 07:46:15 GMT+0000 (GMT)',
          senderId: 2,
          feedback: {
            isSent: true,
            isDelivered: true,
            isSeen: true,
          },
        },
        {
          message: 'If it takes long you can mail me at my mail address.',
          time: String(dayBeforePreviousDay),
          senderId: 121,
          feedback: {
            isSent: true,
            isDelivered: false,
            isSeen: false,
          },
        },
        {
          message: 'If it takes long you can mail me at my mail address.',
          time: String(dayBeforePreviousDay),
          senderId: 120,
          feedback: {
            isSent: true,
            isDelivered: false,
            isSeen: false,
          },
        },
        {
          message: 'If it takes long you can mail me at my mail address.',
          time: String(dayBeforePreviousDay),
          senderId: 5,
          feedback: {
            isSent: true,
            isDelivered: false,
            isSeen: false,
          },
        },
      ]
  */
  const _msgGroups: MessageGroup[] = []

  if (store.activeChat!.chat) {
    messages = store.activeChat!.chat
    users.value = store.activeChat!.users

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
      }
      else {
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
</script>

<template>
  <VForm
    ref="refForm"
    v-model="isFormValid"
    @submit.prevent="onSubmit"
  >
    <VCol cols="12">
      <p
        class="text-body-2 text-high-emphasis mb-1"
        style="line-height: 15px;"
      >
        Nota
      </p>
      <div class="border rounded px-3 py-2">
        <EditorContent :editor="editor" />
        <div
          v-if="editor"
          class="d-flex justify-end flex-wrap gap-x-2"
        >
          <VIcon
            icon="tabler-bold"
            :color="editor.isActive('bold') ? 'primary' : 'secondary'"
            size="20"
            @click="editor.chain().focus().toggleBold().run()"
          />

          <VIcon
            :color="editor.isActive('underline') ? 'primary' : 'secondary'"
            icon="tabler-underline"
            size="20"
            @click="editor.commands.toggleUnderline()"
          />

          <VIcon
            :color="editor.isActive('italic') ? 'primary' : 'secondary'"
            icon="tabler-italic"
            size="20"
            @click="editor.chain().focus().toggleItalic().run()"
          />

          <VIcon
            :color="editor.isActive({ textAlign: 'left' }) ? 'primary' : 'secondary'"
            icon="tabler-align-left"
            size="20"
            @click="editor.chain().focus().setTextAlign('left').run()"
          />

          <VIcon
            :color="editor.isActive({ textAlign: 'center' }) ? 'primary' : 'secondary'"
            icon="tabler-align-center"
            size="20"
            @click="editor.chain().focus().setTextAlign('center').run()"
          />

          <VIcon
            :color="editor.isActive({ textAlign: 'right' }) ? 'primary' : 'secondary'"
            icon="tabler-align-right"
            size="20"
            @click="editor.chain().focus().setTextAlign('right').run()"
          />

          <VBtn
            size="x-small"
            color="secondary"
            variant="tonal"
            @click="onFormReset"
          >
            Cancel
          </VBtn>
          <VBtn
            type="submit"
            size="x-small"
          >
            Salva
          </VBtn>
        </div>
      </div>
    </VCol>
  </VForm>

  <VMain
    class="chat-content-container"
    style="--v-layout-left: auto; --v-layout-right: 0px; --v-layout-top: 0px; --v-layout-bottom: 0px; --67389f93-chatContentContainerBg: undefined;"
  >
    <!-- 👉 Right content: Active Chat -->
    <div
      class="d-flex flex-column border border-primary"
      style="overflow-y:scroll; overflow-x:hidden; height:500px;"
    >
      <!-- Chat log -->
      <div class="chat-log pa-1">
        <div
          v-for="(msgGrp, index) in msgGroups"
          :key="msgGrp.senderId + String(index)"
          class="chat-group d-flex align-start"
          :class="[{
            'flex-row-reverse': Number(msgGrp.senderId) === contact.id,
            'mb-1': msgGroups.length - 1 === index,
          }]"
        >

          <div
            class="chat-body d-inline-flex flex-column"
            :class="Number(msgGrp.senderId) === contact.id ? 'align-end' : 'align-start'"
          >
            <div
              v-for="(msgData, msgIndex) in msgGrp.messages"
              :key="msgData.time"
            >
              <div
                class="chat-avatar"
                :class="msgGrp.senderId !== contact.id ? 'ms-4 text-success' : 'me-4 text-success'"
              >

                {{ users[msgGrp.senderId].name }}
              </div>
              <p
                class="chat-content py-1 px-1 elevation-1 ml-5"
                style="background-color: rgb(var(--v-theme-surface));"
                :class="[
                Number(msgGrp.senderId) !== contact.id ? 'chat-left' : 'bg-primary text-white chat-right',
                msgGrp.messages.length - 1 !== msgIndex ? 'mb-0' : 'mb-1',
              ]"
                v-html="msgData.message"
              >

              </p>
              <div class="text-left">
                <span class="text-sm ms-1 text-disabled">{{ formatDate(msgData.time, { year: 'numeric', month: '2-digit', day: 'numeric', hour: 'numeric', minute: 'numeric' }) }}</span>
              </div>
              </div>

          </div>
        </div>
      </div>
    </div>
  </VMain>
</template>

<style lang="scss">
@use "@styles/variables/_vuetify.scss";
@use "@core-scss/base/_mixins.scss";
@use "@layouts/styles/mixins" as layoutsMixins;

// Variables
$chat-app-header-height: 62px;

// Placeholders
%chat-header {
  display: flex;
  align-items: center;
  min-block-size: $chat-app-header-height;
  padding-inline: 11rem;
}

.chat-app-layout {
  border-radius: vuetify.$card-border-radius;

  @include mixins.elevation(vuetify.$card-elevation);

  $sel-chat-app-layout: &;

  @at-root {
    .skin--bordered {
      @include mixins.bordered-skin($sel-chat-app-layout);
    }
  }

  .active-chat-user-profile-sidebar,
  .user-profile-sidebar {
    .v-navigation-drawer__content {
      display: flex;
      flex-direction: column;
    }
  }

  .chat-list-header,
  .active-chat-header {
    @extend %chat-header;
  }

  .chat-list-search {
    .v-field__outline__start {
      flex-basis: 20px !important;
      border-radius: 28px 0 0 28px !important;
    }

    .v-field__outline__end {
      border-radius: 0 28px 28px 0 !important;
    }

    @include layoutsMixins.rtl {
      .v-field__outline__start {
        flex-basis: 20px !important;
        border-radius: 0 28px 28px 0 !important;
      }

      .v-field__outline__end {
        border-radius: 28px 0 0 28px !important;
      }
    }
  }

  .chat-list-sidebar {
    .v-navigation-drawer__content {
      display: flex;
      flex-direction: column;
    }
  }
}

.chat-content-container {
  /* stylelint-disable-next-line value-keyword-case */
  background-color: v-bind(chatContentContainerBg);

}

.chat-user-profile-badge {
  .v-badge__badge {
    /* stylelint-disable liberty/use-logical-spec */
    min-width: 12px !important;
    height: 0.75rem;
    /* stylelint-enable liberty/use-logical-spec */
  }
}

.ProseMirror {
  padding: 0;
  min-block-size: 5vh !important;

  p {
    margin-block-end: 0;
  }
}

.ProseMirror-focused {
  outline: none !important;
}

.element.style {
  --v-layout-left: 456px !important;
  --v-layout-right: 0px;
  --v-layout-top: 0px;
  --v-layout-bottom: 0px;
  --67389f93-chatContentContainerBg: undefined;
}

.chat-log {
  .chat-content {
    border-end-end-radius: 6px;
    border-end-start-radius: 6px;

    &.chat-left {
      border-start-end-radius: 6px;
    }

    &.chat-right {
      border-start-start-radius: 6px;
    }
  }
}
</style>
