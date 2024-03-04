export default [
  {
    title: 'Quality',
    icon: { icon: 'tabler-brand-apple' },
    children: [
      {
        title: 'Checker',
        icon: { icon: 'tabler-lock' },
        action: 'read',
        subject: 'Administration',
        children: [
          {
            title: 'Report',
            to: 'quality-checker-reports-list',
            action: 'read',
            subject: 'Administration',
          },
        ],
      }
    ],
  },
]
