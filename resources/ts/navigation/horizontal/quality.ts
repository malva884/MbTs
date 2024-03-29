export default [
  {
    title: 'Quality',
    icon: { icon: 'tabler-brand-apple' },
    children: [
      {
        title: 'Checker',
        icon: { icon: 'tabler-lock' },
        action: 'list',
        subject: 'Qualita-Checker-Report',
        children: [
          {
            title: 'Rapportini',
            to: 'quality-checker-reports-list',
            action: 'list',
            subject: 'Qualita-Checker-Report',
          },
        ],
      },
      {
        title: 'Conformità',
        icon: { icon: 'tabler-lock' },
        action: 'list',
        subject: 'Qualita-Conformita',
        children: [
          {
            title: 'Lista',
            to: 'quality-conformita-list',
            action: 'list',
            subject: 'Qualita-Conformita',
          },
        ],
      },
      {
        title: 'Fai',
        icon: { icon: 'tabler-lock' },
        action: 'list',
        subject: 'Qualita-Fai',
        children: [
          {
            title: 'List',
            to: 'quality-fai-list',
            action: 'list',
            subject: 'Qualita-Fai',
          },
        ],
      },
    ],
  },
]
