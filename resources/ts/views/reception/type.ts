
export interface RpRegisterLog {
  id: string | null
  user: string | null
  evento_id: string
  cod_riferimento: string
  attivo: number | null
  nome: string
  email: string
  cod_tessera: string
  password_wifi: string
  username_wifi: boolean |null
  entrata: boolean |null
  stampa: boolean |null
}
