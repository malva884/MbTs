export default [
  {
    title: 'Ufficio-Tecnico',
    icon: { icon: 'tabler-building' },
    children: [
      {
        title: 'Cavi',
        icon: { icon: 'tabler-jump-rope' },
        to: 'offices-technical-cables-list',
        action: 'list',
        subject: 'Cavi',
      },
      {
        title: 'Preventivi',
        icon: { icon: 'tabler-quote' },
        to: 'offices-technical-quote-list',
        action: 'list',
        subject: 'Preventivi',
      },
      {
        title: 'Gestione',
        icon: { icon: 'tabler-settings' },
        children: [
          {
            title: 'Bobine',
            to: 'offices-technical-gestione-bobine',
            action: 'admin',
            subject: 'Cavi',
          },
          {
            title: 'Categoria',
            to: 'offices-technical-gestione-categorie',
            action: 'list',
            subject: 'Users',
          },
          {
            title: 'Centri-Di-Costo',
            to: 'offices-technical-gestione-centri',
            action: 'list',
            subject: 'Cavi',
          },
          {
            title: 'Clienti',
            to: 'offices-technical-gestione-clienti',
            action: 'list',
            subject: 'Cavi',
          },
          {
            title: 'Materiali',
            to: 'offices-technical-gestione-materiali',
            action: 'list',
            subject: 'Cavi',
          },
        ],
      },
    ],
  },
]
