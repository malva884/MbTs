
export interface Esterno {
  id: string
  nome: string
  email: string
  done?: boolean
}

export interface Event {
  id?: string
  title: string
  start: string
  end: string
  allDay: boolean
  url?: string
  extendedProps: {
    calendar?: string
    location: string
    description: string
    guests: string[]
    esterni: Esterno[]
  }
}

export interface NewEvent {
  title: string
  start: string
  end: string
  allDay: boolean
  url?: string
  extendedProps: {
    calendar?: string
    location: string
    description: string
    guests: string[]
    esterni: Esterno[]
  }
}
