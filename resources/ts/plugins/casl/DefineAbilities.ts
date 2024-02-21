export default {
    user_edit: {    action: 'edit' as const,
                    subject: 'user' as const,
    },
    user_deleted:{
                    action: 'deleted' as const,
                    subject: 'user' as const,
    },
    test:{
        action: 'test' as const,
        subject: 'test' as const,
    }
}

