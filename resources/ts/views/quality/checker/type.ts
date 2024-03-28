
export interface Coils {
  coil: string
  coil_t: string | null
  fo_try: number | null
}

export interface ReprotChecker {
  id: string | null
  user: number | null
  date_create: string
  ol: string
  num_fo: number | null
  coils: Coils[]
  stage: string
  coil: string
  fo_try: number
  note: string
  not_conformity: number |null
}
