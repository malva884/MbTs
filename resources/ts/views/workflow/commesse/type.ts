export interface Commessa {
  id: string
  commessa: string
  commessa_sistema: string
  stato: string
  revisione: number | null
  id_commessa_padre: string | null
  data_approvazione: string | null
  tipologia: number | null
  categoria_id: string
  creator: number
  folder_drive: string | null
  id_file_drive: string | null
  visibile: boolean
  id_log_drive: string | null
  created_at: string | null
}
