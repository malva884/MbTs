export interface DocumentState {
  presente: boolean
  valido: boolean // TRUE se approvato dal Reparto Qualità
  validato_da: string | null
  data_validazione: string | null
}

export interface QualityValidationResponse {
  idoneita: DocumentState
  giudizio: DocumentState
  riga_completa: boolean // TRUE se entrambi sono validi -> Riga Verde
}
