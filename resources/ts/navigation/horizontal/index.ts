import administration from './administration'
import dashboard from './dashboard'
import calendar from './calendar'
import quality from './quality'

import type { HorizontalNavItems } from '@layouts/types'

export default [...dashboard,...administration, ...quality, ...calendar] as HorizontalNavItems


