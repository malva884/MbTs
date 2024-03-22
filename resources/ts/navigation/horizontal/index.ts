import administration from './administration'
import dashboard from './dashboard'
import calendar from './calendar'
import quality from './quality'
import reception from './reception'

import type { HorizontalNavItems } from '@layouts/types'

export default [...dashboard,...administration, ...quality, ...calendar, ...reception] as HorizontalNavItems


