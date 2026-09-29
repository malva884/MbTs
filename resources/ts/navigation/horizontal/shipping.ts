export default [
  {
    title: 'Spedizioni',
    icon: { icon: 'tabler-truck-delivery' },
    children: [
      {
        title: 'Picking List',
        icon: { icon: 'tabler-list-check' },
        action: 'list',
        subject: 'Shipping-Picking-List',
        to: 'shipping-picking-list',
      },
      {
        title: 'Listini',
        icon: { icon: 'tabler-currency-euro' },
        action: 'list',
        subject: 'Spedizioni-Listini',
        to: 'shipping-listini',
      },
      {
        title: 'DDT',
        icon: { icon: 'tabler-file-invoice' },
        action: 'list',
        subject: 'Spedizioni-Ddt',
        to: 'shipping-ddt',
      },
    ],
  },
]
