
export interface CavoPreventivo {
  id: number
  codice: string
  categoria: string
  categoria_id: string
  descrizione: string
  nota: string
  norma: string
  created_at: string
}

export interface StrutturaCavoPreventivo {
  id: string | null
  cavo_id: Cavo
  centro: string
  materiale: string
  descrizione: string | null
  diametro: number | null
  peso: number | null
  ordinata: number
  elementi: number
  nota: string
  posizione: number
  costo: number
  costo_materia_prima: number
  costo_lavorazione: number
  ore_macchina: number
  costo_centro: number
}
