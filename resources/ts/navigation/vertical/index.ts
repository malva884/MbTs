export default [
  {
    title: 'Home',
    to: { name: 'root' },
    icon: { icon: 'tabler-smart-home' },
  },
  {
    title: 'Second page',
    to: { name: 'second-page' },
    icon: { icon: 'tabler-file' },
  },
  {
    title: 'Sistema',
    icon: { icon: 'tabler-settings' },
    children: [
      {
        title: 'Gestione Job & Cron',
        icon: { icon: 'tabler-clock' },
        children: [
          {
            title: 'Dashboard',
            icon: { icon: 'tabler-dashboard' },
            to: { name: 'system-jobs-dashboard' },
          },
          {
            title: 'Queue Jobs',
            icon: { icon: 'tabler-list' },
            to: { name: 'system-jobs-queue' },
          },
          {
            title: 'Cron Jobs',
            icon: { icon: 'tabler-calendar' },
            to: { name: 'system-jobs-cron' },
          },
          {
            title: 'Logs',
            icon: { icon: 'tabler-file-text' },
            to: { name: 'system-jobs-logs' },
          },
          {
            title: 'Failed Jobs',
            icon: { icon: 'tabler-alert-circle' },
            to: { name: 'system-jobs-failed' },
          },
        ],
      },
    ],
  },
]
