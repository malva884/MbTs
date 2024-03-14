
export interface Conformita {
  id: string | null
  report_id: string | null
  user: number | null
  data_apertura: string
  data_chiusura: string
  ol: string
  num_fo: number | null
  stage: string
  bobina: string
  note: string
  macchina: number
  difetto: number
  fibre: string
  soluzione: string
  chiuso: boolean | null
  diametro: number
  tipologia_fibra: string
  operator: string
  physical_l: number
  optical_l: number
  tipologia_difetto: string
}
