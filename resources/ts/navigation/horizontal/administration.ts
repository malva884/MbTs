export default [
  {
    title: 'Amministrazione',
    icon: { icon: 'tabler-brand-apple' },
    children: [
      {
        title: 'User',
        icon: { icon: 'tabler-users' },
        action: 'read',
        subject: 'Users',
        children: [
          {
            title: 'List',
            to: 'administrations-users-list',
            action: 'read',
            subject: 'Users',
          },

        ],
      },
      {
        title: 'Roles/Permissions',
        icon: { icon: 'tabler-lock' },
        action: 'read',
        subject: 'Permessi',
        children: [
          {
            title: 'Ruoli',
            to: null,
            action: 'read',
            subject: 'Users',
          },
          {
            title: 'Permessi',
            to: 'administrations-permissions',
            action: 'read',
            subject: 'Permessi',
          },
        ],
      },
	  {
        title: 'Notifiche Sistema',
        icon: { icon: 'tabler-bell-plus' },
        to: 'administrations-notifications-list',
        action: 'read',
        subject: 'Users',
      },
      {
        title: 'Utenti Esterni',
        icon: { icon: 'tabler-users' },
        action: 'read',
        subject: 'Users',
        children: [
          {
            title: 'Notifiche',
            to: 'administrations-users-esterni-notifiche',
            action: 'read',
            subject: 'Users',
          },

        ],
      },
    ],
  },
]
