export default [
  {
    title: 'Reception',
    icon: { icon: 'tabler-brand-apple' },
    children: [
      {
        title: 'Visitatori',
        icon: { icon: 'tabler-lock' },
        action: 'list',
        subject: 'Reception-Register',
        children: [
          {
            title: 'Calendar',
            to: 'calendar-auth',
            action: 'create',
            subject: 'Reception-Register',
            icon: { icon: 'tabler-calendar' },
          },
          {
            title: 'Entrate/Uscite',
            to: 'reception-register-attivita',
            action: 'list',
            subject: 'Reception-Register',
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
