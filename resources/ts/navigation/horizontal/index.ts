import administration from './administration'
import dashboard from './dashboard'
import quality from './quality'
import reception from './reception'
import production from './production'

import type { HorizontalNavItems } from '@layouts/types'

export default [...dashboard, ...administration, ...production, ...reception, ...quality] as HorizontalNavItems


