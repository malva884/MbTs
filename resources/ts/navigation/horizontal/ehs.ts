export default [
  {
    title: 'EHS',
    icon: { icon: 'tabler-shield-exclamation' },
    children: [
      {
        title: 'Eventi',
        icon: { icon: 'tabler-list-details' },
        children: [
          {
            title: 'Lista Eventi',
            to: 'ehs-list',
            action: 'list',
            subject: 'Ehs-Eventi',
          },
          {
            title: 'Nuovo Evento',
            to: 'ehs-new',
            action: 'create',
            subject: 'Ehs-Eventi',
          },
        ],
      },
      {
        title: 'Dashboard',
        icon: { icon: 'tabler-chart-bar' },
        children: [
          {
            title: 'Grafici',
            to: 'ehs-dashboard',
            action: 'report',
            subject: 'Ehs-Eventi',
          },
        ],
      },
      {
        title: 'Gestione',
        icon: { icon: 'tabler-settings' },
        children: [
          {
            title: 'Tabelle Supporto',
            to: 'ehs-gestione',
            action: 'admin',
            subject: 'Ehs-Eventi',
          },
        ],
      },
    ],
  },
]
