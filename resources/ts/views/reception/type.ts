
export interface RpRegisterLog {
  id: string | null
  user: number | null
  evento_id: string
  cod_riferimento: string
  num_fo: number | null
  stage: string
  coil: string
  fo_try: number
  note: string
  not_conformity: boolean |null
}
