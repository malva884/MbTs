// eslint-disable-next-line valid-appcardcode-code-prop
import type { ActiveNote } from './useNote'
import type { ChatContact, ChatContactWithChat, ChatMessage, ChatOut } from '@/views/task/note/type'

interface State {
  chatsContacts: ChatContactWithChat[]
  contacts: ChatContact[]
  profileUser: ChatContact | undefined
  activeChat: ActiveNote
}

export const useNotStore = defineStore('chat', {
  // ℹ️ arrow function recommended for full type inference
  state: (): State => ({
    contacts: [],
    chatsContacts: [],
    profileUser: undefined,
    activeChat: null,
  }),
  actions: {
    async fetchChatsAndContacts(q: string) {
      const { data, error } = await useApi<any>(createUrl('/apps/chat/chats-and-contacts', {
        query: {
          q,
        },
      }))

      if (error.value) {
        console.log(error.value)
      }
      else {
        const { chatsContacts, contacts, profileUser } = data.value

        this.chatsContacts = chatsContacts
        this.contacts = contacts
        this.profileUser = profileUser
      }
    },

    async getChat(userId: ChatContact['id']) {

      const res = await useApi<any>(createUrl(`/task/task_nota/${userId}`))

      this.activeChat = res.data.value
    },

    async sendMsg(message: ChatMessage['message']) {
      const senderId = this.profileUser?.id

      const response = await $api(`task/task_nota/${this.activeChat?.contact.id}`, {
        method: 'POST',
        body: { message, senderId },
      })

      const { msg, chat }: { msg: ChatMessage; chat: ChatOut } = response

      // ? If it's not undefined => New chat is created (Contact is not in list of chats)
      if (chat !== undefined) {
        const activeChat = this.activeChat!

        this.chatsContacts.push({
          ...activeChat.contact,
          chat: {
            id: chat.id,
            lastMessage: [],
            unseenMsgs: 0,
            messages: [msg],
          },
        })

        if (this.activeChat) {
          this.activeChat.chat = {
            id: chat.id,
            messages: [msg],
            unseenMsgs: 0,
            userId: this.activeChat?.contact.id,
          }
        }
      }
      else {
        this.activeChat?.chat?.messages.push(msg)
      }

      // Set Last Message for active contact
      const contact = this.chatsContacts.find(c => {
        if (this.activeChat)
          return c.id === this.activeChat.contact.id

        return false
      }) as ChatContactWithChat

      contact.chat.lastMessage = msg
    },
  },
})
