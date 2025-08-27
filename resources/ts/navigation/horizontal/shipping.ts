export default [
  {
    title: 'Spedizzioni',
    icon: { icon: 'tabler-truck-delivery' },
    children: [
      {
        title: 'Picking List',
        icon: { icon: 'tabler-list-check' },
        action: 'list',
        subject: 'shipping_picking_create',
        to: 'shipping-picking-list',
      },
    ],
  },
]
