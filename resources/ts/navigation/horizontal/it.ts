export default [
  {
    title: 'It',
    icon: { icon: 'tabler-sitemap' },
    children: [
      {
        title: 'IT Asset Management',
        icon: { icon: 'tabler-device-laptop' },
        children: [
          {
            title: 'Assets',
            icon: { icon: 'tabler-package' },
            children: [
              {
                title: 'List',
                to: 'it-assets-list',
              },
              {
                title: 'Bulk Add',
                to: 'it-assets-bulk-add',
              },
            ],
          },
          {
            title: 'Configuration',
            icon: { icon: 'tabler-settings' },
            children: [
              {
                title: 'Categories',
                icon: { icon: 'tabler-category' },
                to: 'it-categories-list',
              },
              {
                title: 'Locations',
                icon: { icon: 'tabler-map-pin' },
                to: 'it-locations-list',
              },
              {
                title: 'Machines',
                icon: { icon: 'tabler-device-desktop' },
                to: 'it-machines-list',
              },
              {
                title: 'Suppliers',
                icon: { icon: 'tabler-building-factory-2' },
                to: 'it-suppliers-list',
              },
            ],
          },
          {
            title: 'Assignments',
            icon: { icon: 'tabler-user-check' },
            children: [
              {
                title: 'List',
                to: 'it-assignments-list',
              },
              {
                title: 'Create',
                to: 'it-assignments-create',
              },
            ],
          },
          {
            title: 'Transactions',
            icon: { icon: 'tabler-arrows-exchange' },
            to: 'it-transactions-list',
          },
          {
            title: 'Network Monitoring',
            icon: { icon: 'tabler-network' },
            to: 'it-network-monitoring',
          },
        ],
      },
      {
        title: 'Magazzino',
        icon: { icon: 'tabler-building-warehouse' },
        to: 'plant-warehouse-list',
        action: 'list',
        subject: 'Plant-Asset',
      },
      {
        title: 'Asset/Maps',
        icon: { icon: 'tabler-topology-star-ring-3' },
        children: [
          {
            title: 'Asset',
            icon: { icon: 'tabler-package' },
            action: 'list',
            subject: 'Plant-Asset',
            children: [
              {
                title: 'List',
                to: 'plant-asset-list',
                action: 'list',
                subject: 'Plant-Asset',
              },
            ],
          },
          {
            title: 'Richieste-Assistenza',
            icon: { icon: 'tabler-tool' },
            action: 'list',
            subject: 'Plant-Asset',
            children: [
              {
                title: 'List',
                to: 'plant-assistance-list',
                action: 'list',
                subject: 'Plant-Asset',
              },
            ],
          },
          {
            title: 'Monitoring',
            icon: { icon: 'tabler-tool' },
            action: 'list',
            subject: 'Plant-Asset',
            children: [
              {
                title: 'Checker',
                to: { name: 'plant-assistance-monitorig-id', params: { id: 'Desktop Checker' } },
                action: 'list',
                subject: 'Plant-Asset',
              },
              {
                title: 'Produzione',
                to: { name: 'plant-assistance-monitorig-id', params: { id: 'Tablet Prodizione' } },
                action: 'list',
                subject: 'Plant-Asset',
              },
            ],
          },
          {
            title: 'Maps',
            icon: { icon: 'tabler-map-star' },
            action: 'list',
            subject: 'Plant-Asset',
            children: [
              {
                title: 'Asset Maps',
                to: 'plant-asset-maps',
                action: 'list',
                subject: 'Plant-Asset',
              },
            ],
          },
          {
            title: 'Gestione',
            icon: { icon: 'tabler-settings' },
            children: [
              {
                title: 'Gruppi-Mappe',
                to: 'plant-gestione-gruppi',
                action: 'admin',
                subject: 'Plant-Asset',
              },
              {
                title: 'Tipologa-Asset',
                to: 'plant-gestione-tipologie',
                action: 'admin',
                subject: 'Plant-Asset',
              },
            ],
          },
        ],
      },
    ],
  },
]
