export interface Clienti {
  codice: string | null
  cliente: number | null
}
export interface FilterClienti {
  materiale: string | null
  tipologia: string | null
  clienti: Clienti
}
