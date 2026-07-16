export default [
  {
    title: 'Hr',
    icon: { icon: 'tabler-users-group' },
    children: [
      {
        title: 'Anagrafica',
        icon: { icon: 'tabler-user' },
        children: [
          {
            title: 'Dipendenti',
            to: 'hr-employee-list',
            action: 'list',
            subject: 'Employee',
          },
        ],
      },
      {
        title: 'Richieste',
        icon: { icon: 'tabler-list-details' },
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
        title: 'Gestione HR',
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
        icon: { icon: 'tabler-clipboard-check' },
        children: [
          {
            title: 'Valutazioni',
            to: 'hr-competenze-valutazioni',
            action: 'list',
            subject: 'Competenze',
          },
          {
            title: 'Matrice',
            to: 'hr-competenze-matrice',
            action: 'list',
            subject: 'Competenze',
          },
        ],
      },
      {
        title: 'Presenze',
        icon: { icon: 'tabler-calendar-event' },
        children: [
          {
            title: 'Matrice Presenze',
            to: 'hr-presenze-matrice',
            action: 'list',
            subject: 'Hr-Presenze',
          },
        ],
      },
      {
        title: 'Scadenze',
        icon: { icon: 'tabler-alert-triangle' },
        children: [
          {
            title: 'Scadenze',
            to: 'hr-scadenze',
            action: 'report',
            subject: 'Hr-Dipendenti',
          },
        ],
      },
      {
        title: 'Report',
        icon: { icon: 'tabler-chart-pie' },
        children: [
          {
            title: 'Report Richieste',
            to: 'hr-report',
            action: 'report',
            subject: 'Hr-Dipendenti',
          },
          {
            title: 'Report Straordinari',
            to: 'hr-report-straordinari',
            action: 'list',
            subject: 'Hr-Straordinari',
          },
          {
            title: 'Costi Straordinari',
            to: 'hr-report-costi-straordinari',
            action: 'list',
            subject: 'Hr-Straordinari',
          },
        ],
      },
    ],
  },
]
