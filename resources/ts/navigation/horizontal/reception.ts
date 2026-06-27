export default [
  {
    title: 'Reception',
    icon: { icon: 'tabler-user-hexagon' },
    children: [
      {
        title: 'Visitatori',
        icon: { icon: 'tabler-lock' },
        action: 'list',
        subject: 'Reception-Register',
        children: [
          {
            title: 'Calendar',
            to: 'calendar-calendar',
            action: 'create',
            subject: 'Reception-Register',
            icon: { icon: 'tabler-calendar' },
          },
          {
            title: 'Entrate/Uscite',
            to: 'reception-register-attivita',
            action: 'list',
            subject: 'Reception-Register',
            icon: { icon: 'tabler-door' },
          },
          {
            title: 'Lista',
            to: 'reception-register-list',
            action: 'list',
            subject: 'Reception-Register',
          },
        ],
      },
    ],
  },
]
