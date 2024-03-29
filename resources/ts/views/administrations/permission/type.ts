// Permission
export interface Permission {
  id: number | null
  name: string
  admin: boolean
  list: boolean
  create: boolean
  edit: boolean
  read: boolean
  import: boolean
  deleted: boolean
  sing: boolean
  report: boolean
  notification: boolean
}
