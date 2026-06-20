export interface Procedura {
  id: string | null
  procedura: string | null
  descrizione: string | null
  revisione: string | null
  revisione_anno: string | null
  categoria_id: string | null
  processo_id: string | null
  user_id: null
  stato: string | null
  padre_id: null
  data_prova: string | null
  folder_drive: string | null
  id_file_drive: string | null
  id_log_drive: []
  disable: boolean
  uffici: []
  certificati: []
  file_upload: []
}
