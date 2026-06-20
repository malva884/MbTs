import type { App } from 'vue'

import { createMongoAbility } from '@casl/ability'
import { abilitiesPlugin } from '@casl/vue'
import type { Rule } from './ability'

export default function (app: App) {
  const storedRules = localStorage.getItem('userAbilityRules')
  const userAbilityRules = storedRules ? JSON.parse(storedRules) as Rule[] : []
  const initialAbility = createMongoAbility(userAbilityRules)

  app.use(abilitiesPlugin, initialAbility, {
    useGlobalProperties: true,
  })
}

