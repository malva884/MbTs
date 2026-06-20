export default [
  {
    title: 'Workflow',
    icon: { icon: 'tabler-checklist' },
    children: [
      {
        title: 'Commesse',
        icon: { icon: 'tabler-checklist' },
        to: 'workflow-commesse-list',
        action: 'list',
        subject: 'Wf-Commesse',
      },
      {
        title: 'Procedure',
        icon: { icon: 'tabler-checklist' },
        to: 'workflow-procedure-list',
        action: 'list',
        subject: 'Wf-Procedure',
      },
      {
        title: 'Gestione',
        icon: { icon: 'tabler-settings' },
        children: [
          {
            title: 'Categorie',
            to: 'workflow-categorie-list',
            action: 'admin',
            subject: 'Workflow',
          },
          {
            title: 'Certificazioni',
            to: 'workflow-gestione-procedure-certificazioni-list',
            action: 'admin',
            subject: 'Workflow',
          },
          {
            title: 'Ruoli',
            to: 'workflow-ruoli-list',
            action: 'admin',
            subject: 'Workflow',
          },
          {
            title: 'Uffici',
            to: 'workflow-gestione-procedure-uffici-list',
            action: 'admin',
            subject: 'Workflow',
          },
          {
            title: 'Utenti',
            to: 'workflow-utenti-list',
            action: 'admin',
            subject: 'Workflow',
          },
        ],
      },
    ],
  },
]
