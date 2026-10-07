export interface Variazione {
  id: string
  ol: string
  stato: string
  creator: number
  creator_name?: string
  testo: string | null
  revisione: number | null
  categoria_id: string | null
  categoria?: string
  data_approvazione: string | null
  end_date: string | null
  tipologia: number | null
  folder_drive: string | null
  id_file_drive: string | null
  id_log_drive: string | null
  visibile: boolean
  viewed?: boolean
  approval_action?: string | null
  approvals?: any[]
  viewers?: any[]
  role_id?: string | null
  created_at: string | null
  updated_at?: string | null
}
