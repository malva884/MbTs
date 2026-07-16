export default [
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
            to: 'system-jobs-dashboard',
            action: 'view',
            subject: 'System',
          },
          {
            title: 'Queue Jobs',
            icon: { icon: 'tabler-list' },
            to: 'system-jobs-queue',
            action: 'view',
            subject: 'System',
          },
          {
            title: 'Cron Jobs',
            icon: { icon: 'tabler-calendar' },
            to: 'system-jobs-cron',
            action: 'view',
            subject: 'System',
          },
          {
            title: 'Logs',
            icon: { icon: 'tabler-file-text' },
            to: 'system-jobs-logs',
            action: 'view',
            subject: 'System',
          },
        ],
      },
      {
        title: 'Notifiche Sistema',
        icon: { icon: 'tabler-bell-plus' },
        to: 'administrations-notifications-list',
        action: 'admin',
        subject: 'Users',
      },
      {
        title: 'Cartelle Condivise',
        icon: { icon: 'tabler-brand-google-drive' },
        children: [
          {
            title: 'Cartelle',
            icon: { icon: 'tabler-folders' },
            to: 'system-folder-list',
            action: 'admin',
            subject: 'Users',
          },
          {
            title: 'Permessi Cartelle',
            icon: { icon: 'tabler-accessible' },
            to: 'administrations-notifications-list',
            action: 'admin',
            subject: 'Users',
          },
        ],
      },
    ],
  },
]
