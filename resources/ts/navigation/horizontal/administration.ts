export default [
  {
    title: 'Amministrazione',
    icon: { icon: 'tabler-brand-apple' },
    children: [
      {
        title: 'User',
        icon: { icon: 'tabler-users' },
        children: [
          {
            title: 'List',
            to: 'administrations-users-list',
            action: 'read',
            subject: 'Administration',
          },

        ],
      },
      {
        title: 'Roles/Permissions',
        icon: { icon: 'tabler-lock' },
        action: 'read',
        subject: 'Administration',
        children: [
          {
            title: 'Ruoli',
            to: null,
            action: 'read',
            subject: 'Administration',
          },
          {
            title: 'Permessi',
            to: 'administrations-permissions',
            action: 'read',
            subject: 'Administration',
          },
        ],
      },
      {
        title: 'Home',
        to: { name: 'root' },
        action: 'read',
        subject: 'Administration',
        icon: { icon: 'tabler-smart-home' },
      },
      {
        title: 'Second page',
        to: { name: 'second-page' },
        action: 'read',
        subject: 'Administration',
        icon: { icon: 'tabler-file' },
      }
    ],
  },
]
