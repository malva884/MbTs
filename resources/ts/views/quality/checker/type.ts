
export interface Coils {
  coil: string
  coil_t: string | null
  fo_try: number | null
  km: number | null
}

export interface ReprotChecker {
  id: string | null
  user: number | null
  date_create: string
  ol: string
  materiale: string | null
  tipo_cavo: string | null
  num_fo: number | null
  km: number | null
  coils: Coils[]
  stage: string
  coil: string
  fo_try: number
  lavorazione: number
  note: string
  not_conformity: number |null
}
