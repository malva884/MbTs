import administration from './administration'
import dashboard from './dashboard'
import quality from './quality'
import reception from './reception'

import type { HorizontalNavItems } from '@layouts/types'

export default [...dashboard, ...administration, ...quality, ...reception] as HorizontalNavItems


