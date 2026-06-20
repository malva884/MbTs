
export interface Professional {
  id: string | null
  employee_id: string
  formazione: string
  data_formazione: string
  path_driver: string
  utente_id: number
  tipologia: number
}

export interface Mandatory {
  id: string
  employee_id: string
  formazione_id: number
  data_scadenza: string
  path_driver: string
  utente_id: number
}

export interface Skill {
  id: string
  employee_id: string
  formazione_id: number
  data_scadenza: string
  path_driver: string
  utente_id: number
}
