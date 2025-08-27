export interface Warehouse {
  id: string | null
  marca: string | null
  descrizione: string
  tipologia: string
  pn_interno: string | null
  pn_oem: string | null
  quantita_minima: number | null
  quantita: number | null
  data_fornitura: string
  prezzo: number
  foto: string | null
}

export interface Provider {
  id: string | null
  tipologia: string | null
  sito: string
  link: string
  prezzo: number | null
}
