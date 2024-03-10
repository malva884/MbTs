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
            title: 'Report',
            to: 'quality-checker-reports-list',
            action: 'list',
            subject: 'Qualita-Checker-Report',
          },
        ],
      },
      {
        title: 'Fai',
        icon: { icon: 'tabler-lock' },
        action: ['list | create'],
        subject: 'Qualita-Fai',
        children: [
          {
            title: 'Report',
            to: 'quality-fai-list',
            action: 'list',
            subject: 'Qualita-Fai',
          },
        ],
      },
    ],
  },
]
