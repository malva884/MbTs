export interface Task {
  id: string | null
  area_id: string | null
  padre: string | null
  responsabile_id: string
  responsabile: boolean
  utente_id: string
  codice: string
  stato: string
  reparto_id: string | null
  mansione_id: string | null
  titolo: string
  descrizione: string | null
  data_chiusura: string
  data_scadenza: string
  data_scadenza_iniziale: string
  giorni_dopo_scadenza: string
  completamento: number | null
  priorieta: string
  richiedente: string
  numero: number
  near_miss_id: string
  path_drive: string
  created_at: string
  users: {}
  full_name: string
}
