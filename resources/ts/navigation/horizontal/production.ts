export default [
  {
    title: 'Produzione',
    icon: { icon: 'tabler-building-factory' },
    children: [
      {
        title: 'Dashboard',
        icon: { icon: 'tabler-dashboard' },
        action: 'list',
        subject: 'Produzione-Business-Intelligence',
        children: [
          {
            title: 'Macchine',
            to: 'production-dashboard-machines',
            action: 'list',
            subject: 'Produzione-Business-Intelligence',
          },
          {
            title: 'Report Plant',
            to: 'production-dashboard-report-plant',
            action: 'report',
            subject: 'Produzione-Performance',
          },
        ],
      },
      {
        title: 'Fatturato',
        icon: { icon: 'tabler-building-bank' },
        action: 'read',
        subject: 'Finanze-Fatturato',
        children: [
          {
            title: 'Lista',
            to: 'finance-fatturato-list',
            action: 'read',
            subject: 'Finanze-Fatturato',
          },
          {
            title: 'Controlo Quantità',
            to: 'finance-fatturato-check',
            action: 'create',
            subject: 'Finanze-Fatturato',
          },
          {
            title: 'Target',
            to: { name: 'target-list-id', params: { id: '1' } },
            action: 'create',
            subject: 'Finanze-Fatturato',
          },
          {
            title: 'Report',
            icon: { icon: 'tabler-checkup-list' },
            action: 'list',
            subject: 'Finanze-Fatturato',
            children: [
              {
                title: 'Generico',
                to: 'finance-fatturato-report',
                action: 'read',
                subject: 'Finanze-Fatturato',
              },
              {
                title: 'Clienti',
                to: 'finance-fatturato-report-clienti',
                action: 'read',
                subject: 'Finanze-Fatturato',
              },
            ],
          },
        ],
      },
      {
        title: 'Spedito',
        icon: { icon: 'tabler-truck' },
        action: 'read',
        subject: 'Finanze-Spedito',
        children: [
          {
            title: 'Spedito',
            to: 'finance-spedito-list',
            action: 'read',
            subject: 'Finanze-Spedito',
          },
          {
            title: 'Merce In Viaggio',
            to: 'finance-viaggio-list',
            action: 'read',
            subject: 'Finanze-Spedito',
          },
          {
            title: 'Target',
            to: { name: 'target-list-id', params: { id: '2' } },
            action: 'read',
            subject: 'Finanze-Fatturato',
          },

        ],
      },
      {
        title: 'Magazzino',
        icon: { icon: 'tabler-building-warehouse' },
        action: 'list',
        subject: 'Produzione-Magazzino',
        children: [
          {
            title: 'Lista Mensile',
            to: 'production-warehouse-list',
            action: 'list',
            subject: 'Produzione-Magazzino',
          },
          {
            title: 'Magazzino',
            to: 'production-warehouse-view-magazzino',
            action: 'create',
            subject: 'Produzione-Magazzino',
          },
          {
            title: 'Target',
            to: { name: 'target-list-id', params: { id: '4' } },
            action: 'admin',
            subject: 'Finanze-Fatturato',
          },
        ],
      },
      {
        title: 'Agp',
        icon: { icon: 'tabler-target' },
        action: 'report',
        subject: 'Produzione-Performance',
        children: [
          {
            title: 'Produzione',
            to: 'target-agp-list-production',
          },
          {
            title: 'Fatturato',
            to: 'target-agp-list-revenue',
          },
        ],
      },
      {
        title: 'Gp',
        icon: { icon: 'tabler-brand-codepen' },
        action: 'admin',
        subject: 'Produzione-Business-Intelligence',
        children: [
          {
            title: 'Strisciate',
            to: 'production-gp-list',
            action: 'list',
            subject: 'Produzione-Magazzino',
          },
        ],
      },
      {
        title: 'Report',
        icon: { icon: 'tabler-device-desktop-analytics' },
        children: [
          {
            title: 'Dati Di Produzione',
            to: 'production-bi',
            action: 'report',
            subject: 'Produzione-Business-Intelligence',
          },
          {
            title: 'Performance',
            to: 'production-performance-report',
            action: 'admin',
            subject: 'Produzione-Performance',
          },
          {
            title: 'KPI',
            to: 'production-kpi-report',
            action: 'report',
            subject: 'Produzione-Kpi',
          },
          {
            title: 'Bobine Prodotte',
            to: 'production-data-coil',
            action: 'report',
            subject: 'Produzione-Kpi',
          },
        ],
      },
      {
        title: 'Giacenze',
        icon: { icon: 'tabler-stack-3' },
        children: [
          {
            title: 'Materiali',
            to: 'production-stock-materiali',
            action: 'report',
            subject: 'Produzione-Performance',
          },
          {
            title: 'Gestione-Categorie',
            to: 'production-stock-category',
            action: 'report',
            subject: 'Produzione-Performance',
          },
        ],
      },
    ],
  },
]
