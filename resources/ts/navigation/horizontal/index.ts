import administration from './administration'
import dashboard from './dashboard'
import quality from './quality'
import reception from './reception'
import production from './production'
import system from './system'
import technicalOffice from './technicalOffice'
import task from './task'
import workflow from './workflow'
import hr from './hr'
import it from './it'


import type { HorizontalNavItems } from '@layouts/types'

export default [...dashboard, ...administration, ...production, ...system, ...it, ...hr, ...workflow, ...reception, ...technicalOffice, ...quality, ...task] as HorizontalNavItems


