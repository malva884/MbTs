import { createMongoAbility } from '@casl/ability'

// export type Actions = 'create' | 'read' | 'update' | 'delete' | 'manage'
export type Actions = 'admin' | 'list' | 'create' | 'edit' | 'read' | 'import' | 'sing' | 'report' | 'notification' | 'deleted' | 'manage'

// ex: Post, Comment, User, etc. We haven't used any of these in our demo though.
// export type Subjects = 'Post' | 'Comment' | 'all'
export type Subjects = 'Difetti' | 'Fibre-Tipologie' | 'Finanze-Fatturato' | 'Finanze-Spedito' | 'Macchinari' | 'Permessi' | 'Qualita-Checker-Report' | 'Qualita-Conformita' | 'Qualita-Fai' | 'Qualita-Prove-Tipo' | 'Reception-Register' | 'Users' | 'Hr-Presenze'

export interface Rule { action: Actions; subject: Subjects }

export const ability = createMongoAbility<[Actions, Subjects]>()
