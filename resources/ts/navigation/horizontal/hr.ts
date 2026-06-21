export default [
  {
    title: 'Hr',
    icon: { icon: 'tabler-users-group' },
    children: [
      {
        title: 'Richieste Dipendenti',
        icon: { icon: 'list-details' },
		action: 'list',
        subject: 'Hr-Richieste',
        children: [
		  {
            title: 'Panoramica-Centro',
            to: 'hr-richieste-view',
            action: 'read',
            subject: 'Hr-Richieste',
          },
          {
            title: 'Richieste-Dipendenti',
            to: 'hr-richieste-list',
            action: 'list',
            subject: 'Hr-Richieste',
          },
          {
            title: 'Gestione',
            children: [
              {
                title: 'Approvatori',
                to: 'hr-richieste-gestione-list',
                action: 'admin',
                subject: 'Hr-Richieste',
              },
              {
                title: 'Centro-Di-Costo',
                to: 'hr-richieste-gestione-centro-list',
                action: 'admin',
                subject: 'Hr-Richieste',
              },
            ],
          },
        ],
      },
    ],
  },
]
