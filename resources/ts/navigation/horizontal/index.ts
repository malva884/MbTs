import administration from './administration'
import dashboard from './dashboard'
import calendar from './calendar'

import type { HorizontalNavItems } from '@layouts/types'

export default [...dashboard,...administration, ...calendar] as HorizontalNavItems


