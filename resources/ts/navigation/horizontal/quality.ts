export default [
  {
    title: 'Qualita',
    icon: { icon: 'tabler-checkbox' },
    children: [
      {
        title: 'Checker',
        icon: { icon: 'tabler-report' },
        action: 'list',
        subject: 'Qualita-Checker-Report',
        children: [
          {
            title: 'Rapportini',
            to: 'quality-checker-reports-list',
            action: 'list',
            subject: 'Qualita-Checker-Report',
          },
          {
            title: 'Rapportini Rame',
            to: 'quality-checker-reports-list-copper',
            action: 'list',
            subject: 'Qualita-Report-Rame',
          },
          {
            title: 'Report',
            to: 'quality-checker-reports-list',
            action: 'report',
            subject: 'Qualita-Checker-Report',
            children: [
              {
                title: 'Checker',
                to: 'quality-conformita-list',
                action: 'report',
                subject: 'Qualita-Checker-Report',
              },
            ],
          },
        ],
      },
      {
        title: 'Conformità',
        icon: { icon: 'tabler-ce-off' },
        action: 'list',
        subject: 'Qualita-Conformita',
        children: [
          {
            title: 'Lista',
            to: 'quality-conformita-list',
            action: 'list',
            subject: 'Qualita-Conformita',
          },
          {
            title: 'Report',
            to: 'quality-conformita-list',
            action: 'report',
            subject: 'Qualita-Conformita',
          },
        ],
      },
      {
        title: 'Dashboard',
        icon: { icon: 'tabler-checkup-list' },
        action: 'list',
        subject: 'Qualita-Fai',
        to: 'quality-documenti-dashboard-report',
      },
      {
        title: 'Controllo Qualità',
        icon: { icon: 'tabler-checkup-list' },
        action: 'create',
        subject: 'Wf-Document',
        to: 'quality-documenti-dashboard-document',
      },
      {
        title: 'Fai',
        icon: { icon: 'tabler-checkup-list' },
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
      {
        title: 'Fornitori',
        icon: { icon: 'tabler-building-store' },
        action: 'list',
        subject: 'Qt-Supplier',
        children: [
          {
            title: 'Lista',
            to: 'quality-fornitori-list',
            action: 'list',
            subject: 'Qt-Supplier',
          },
          {
            title: 'Rating',
            to: 'quality-fornitori-rating-list',
            action: 'list',
            subject: 'Qt-Supplier',
          },
          {
            title: 'Certificazioni',
            to: 'quality-fornitori-certificazione-list',
            action: 'list',
            subject: 'Qt-Supplier',
          },
        ],
      },
      {
        title: 'Laboratorio',
        icon: { icon: 'tabler-cell' },
        action: 'list',
        subject: 'Qualita-Prove-Tipo',
        children: [
          {
            title: 'Prove di Tipo',
            to: 'quality-prove-tipo-list',
            action: 'list',
            subject: 'Qualita-Prove-Tipo',
          },
          {
            title: 'Prove Cpr',
            to: 'quality-prove-cpr-list',
            action: 'list',
            subject: 'Qualita-Prove-Tipo',
          },
        ],
      },
      {
        title: 'Template',
        icon: { icon: 'tabler-printer' },
        children: [
          {
            title: 'Strumenti',
            to: 'template-quality-strumenti',
            action: 'list',
            subject: 'Macchinari',
          },
        ],
      },
      {
        title: 'Gestione',
        icon: { icon: 'tabler-settings' },
        children: [
          {
            title: 'Lista Difetti',
            to: 'quality-gestione-difetti',
            action: 'list',
            subject: 'Difetti',
          },
          {
            title: 'Lista Macchinari',
            to: 'quality-gestione-macchine',
            action: 'list',
            subject: 'Macchinari',
          },
          {
            title: 'Lista Tipologia Fibre',
            to: 'quality-gestione-fibre',
            action: 'list',
            subject: 'Fibre-Tipologie',
          },
          {
            title: 'Categorie',
            to: 'quality-gestione-categorie',
            action: 'list',
            subject: 'Qualita-Fai',
          },
        ],
      },
    ],
  },
]
