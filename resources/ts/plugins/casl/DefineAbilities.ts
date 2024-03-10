// eslint-disable-next-line valid-appcardcode-code-prop
export default {
  user_edit: {
    action: 'edit' as const,
    subject: 'Users' as const,
  },
  user_deleted: {
    action: 'deleted' as const,
    subject: 'Users' as const,
  },
  qt_checker_reprot_create: {
    action: 'create' as const,
    subject: 'Qualita-Checker-Report' as const,
  },
  qt_checker_reprot_edit: {
    action: 'edit' as const,
    subject: 'Qualita-Checker-Report' as const,
  },
  qt_checker_reprot_deleted: {
    action: 'deleted' as const,
    subject: 'Qualita-Checker-Report' as const,
  },
  test: {
    action: 'test' as const,
    subject: 'test' as const,
  },
}
