import administration from './administration'
import dashboard from './dashboard'
import quality from './quality'
import reception from './reception'
import production from './production'
import system from './system'
import shipping from './shipping'
import technicalOffice from './technicalOffice'
import task from './task'
import hr from './hr'
import it from './it'

import type { HorizontalNavItems } from '@layouts/types'

export default [...dashboard, ...administration, ...system, ...it, ...shipping, ...hr, ...production, ...reception, ...technicalOffice, ...quality, ...task] as HorizontalNavItems


