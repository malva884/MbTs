export default [
  {
    title: 'Hr',
    icon: { icon: 'tabler-users-group' },
    children: [
      {
        title: 'Anagrafica Dipendenti',
        to: 'hr-employee-list',
        action: 'list',
        subject: 'Employee',
      },
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
      {
        title: 'Gestione',
        icon: { icon: 'tabler-settings' },
        children: [
          {
            title: 'Formazioni',
            to: 'hr-gestione-formazioni',
            action: 'list',
            subject: 'Formazioni',
          },
          {
            title: 'Reparti',
            to: 'hr-gestione-reparti',
            action: 'list',
            subject: 'Reparti',
          },
          {
            title: 'Ruoli',
            to: 'hr-gestione-ruoli',
            action: 'list',
            subject: 'Ruoli',
          },
        ],
      },
    ],
  },
]
