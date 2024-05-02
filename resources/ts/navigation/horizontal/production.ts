export default [
  {
    title: 'Produzione',
    icon: { icon: 'tabler-building-factory' },
    children: [
      {
        title: 'Fatturato',
        icon: { icon: 'tabler-building-bank' },
        action: 'list',
        subject: 'Finanze-Fatturato',
        children: [
          {
            title: 'Lista',
            to: 'finance-fatturato-list',
            action: 'read',
            subject: 'Finanze-Fatturato',
          },
          {
            title: 'Controlo Quantità',
            to: 'finance-fatturato-check',
            action: 'create',
            subject: 'Finanze-Fatturato',
          },
          {
            title: 'Report',
            icon: { icon: 'tabler-checkup-list' },
            action: 'list',
            subject: 'Finanze-Fatturato',
            children: [
              {
                title: 'Generico',
                to: 'finance-fatturato-report',
                action: 'read',
                subject: 'Finanze-Fatturato',
              },
              {
                title: 'Clienti',
                to: 'finance-fatturato-report-clienti',
                action: 'read',
                subject: 'Finanze-Fatturato',
              },
            ],
          },
        ],
      },
      {
        title: 'Spedito',
        icon: { icon: 'tabler-truck' },
        action: 'read',
        subject: 'Finanze-Spedito',
        children: [
          {
            title: 'Spedito',
            to: 'finance-spedito-list',
            action: 'read',
            subject: 'Finanze-Spedito',
          },
          {
            title: 'Merce In Viaggio',
            to: 'finance-viaggio-list',
            action: 'read',
            subject: 'Finanze-Spedito',
          },
        ],
      },
    ],
  },
]
