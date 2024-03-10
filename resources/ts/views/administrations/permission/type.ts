// Permission
export interface Permission {
  id: number | null
  name: string
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
