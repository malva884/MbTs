
export interface Conformita {
  id: string | null
  report_id: string | null
  user: number | null
  data_apertura: string
  data_chiusura: string
  ol: string
  materiale: string
  num_fo: number | null
  stage: string
  bobina: string
  note: string
  macchina: number
  difetto: number
  fibre: string
  soluzione: string
  stato: string | null
  diametro: number
  tipologia_fibra: string
  operator: string
  physical_l: string
  optical_l: string
  tipologia_difetto: string
  disable: boolean
  approvazione: string
  motivazione: string
  motivazione_chiusura: string
  file_upload: object
}
