export interface RpRegisterLogNotifiche {
  id: string | null
  user: number | null
}
export interface RpRegisterLog {
  id: string | null
  user: number | null
  evento_id: string
  cod_riferimento: string
  cod_tessera: string
  attivo: number | null
  nome: string
  email: string
  azienda: string
  password_wifi: string | null
  username_wifi: string | null
  entrata: boolean |null
  stampa: boolean |null
  data_scadenza: string |null
  data_prevista: string |null
  wifi: boolean
  user_interni: string[]
  referenti: RpRegisterLogNotifiche
}
