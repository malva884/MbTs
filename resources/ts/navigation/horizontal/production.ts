export default [
  {
    title: 'Produzione',
    icon: { icon: 'tabler-building-factory' },
    children: [
      {
        title: 'Fatturato',
        icon: { icon: 'tabler-building-bank' },
        action: 'list',
        subject: 'Qualita-Checker-Report',
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
        ],
      },
      {
        title: 'Spedito',
        icon: { icon: 'tabler-truck' },
        children: [
          {
            title: 'Lista',
            to: 'finance-spedito-list',
            action: 'read',
            subject: 'Finanze-Spedito',
          },
        ],
      },
    ],
  },
]
