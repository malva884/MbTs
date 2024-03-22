export default [
  {
    title: 'Reception',
    icon: { icon: 'tabler-brand-apple' },
    children: [
      {
        title: 'Visitatori',
        icon: { icon: 'tabler-lock' },
        action: 'list',
        subject: 'Qualita-Checker-Report',
        children: [
          {
            title: 'Entrate/Uscite',
            to: 'reception-register-attivita',
            action: 'list',
            subject: 'Qualita-Checker-Report',
          },
        ],
      }
    ],
  },
]
