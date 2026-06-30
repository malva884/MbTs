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
          {
            title: 'Competenze',
            to: 'hr-gestione-competenze',
            action: 'list',
            subject: 'Competenze',
          },
          {
            title: 'Servizi IT',
            to: 'hr-gestione-servizi',
            action: 'list',
            subject: 'Services',
          },
        ],
      },
      {
        title: 'Valutazioni',
        to: 'hr-competenze-valutazioni',
        icon: { icon: 'tabler-clipboard-check' },
        action: 'list',
        subject: 'Competenze',
      },
      {
        title: 'Matrice',
        to: 'hr-competenze-matrice',
        icon: { icon: 'tabler-table' },
        action: 'list',
        subject: 'Competenze',
      },
      {
        title: 'Scadenze',
        to: 'hr-scadenze',
        icon: { icon: 'tabler-alert-triangle' },
        action: 'report',
        subject: 'Hr-Dipendenti',
      },
    ],
  },
]
