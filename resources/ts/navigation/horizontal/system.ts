export default [
  {
    title: 'Sistema',
    icon: { icon: 'tabler-settings' },
    children: [
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
