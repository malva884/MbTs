import administration from './administration'
import dashboard from './dashboard'
import ehs from './ehs'
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

export default [...dashboard, ...administration, ...production, ...system, ...it, ...hr, ...ehs, ...workflow, ...reception, ...technicalOffice, ...quality, ...task] as HorizontalNavItems


