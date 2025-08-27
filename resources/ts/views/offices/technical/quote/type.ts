import {Cavo} from '@/views/offices/technical/cables/type'

export interface Cliente {
  id: string
  ragione_sociale: string | null
  codice_sap: | null
}

export interface Bobina {
  id: number
  bobina: string
  capacita: number
  m3: number
  codice_as: string
  costo: number
  costo_medio: number
  peso: string
  dimensioni: string
  lettera: string
}
export interface Preventivo {
  id: string
  user: number | null
  numero: string
  rdo: string
  parametro: string
  cliente_id: string | null
  cliente_obj: Cliente
  cu: number | null
  data_rdo: string
  data_preventivo: string
  nota: string
}

export interface CavoPreventivo {
  id: string
  preventivo: Preventivo
  cavo: Cavo
  codice: string
  descrizione: string
  metri: number | null
  scarto: number | null
  costo_scarto: number | null
  diametro: number | null
  bobina_numero: number | null
  bobina: Bobina
  costo_bobina: number | null
  totale_costo_bobine: number | null
  peso: number | null
  peso_materie: number | null
  m3: number | null
  m3_totale: number | null
  pezzatura: number | null
  costo: number | null
  parametro: number | null
  costo_manodopera: number | null
  somma_materiali: number | null
  costo_materiali: number | null
  netto: number | null
  lordo: number | null
  variante_rame: number | null
  calcolato: number | null
  posizione: number | null
  nota: string
}

export interface StrutturaCavoPreventivo {
  id: string | null
  cavo_id: CavoPreventivo
  centro: string
  materiale: string
  descrizione: string | null
  diametro: number | null
  peso: number | null
  ordinata: number
  elementi: number
  nota: string
  posizione: number
}
