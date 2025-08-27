
export type ActiveNote = {
  chat?: Chat
  contact: ChatContact
} | null

export const useNote = () => {
  const resolveAvatarBadgeVariant = (status: ChatStatus) => {
    if (status === 'online')
      return 'success'
    if (status === 'busy')
      return 'error'
    if (status === 'away')
      return 'warning'

    return 'secondary'
  }

  return {
    resolveAvatarBadgeVariant,
  }
}
