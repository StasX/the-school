import { invalidCredentialsMessage } from '../messages/loginFailure';

export const loginValidationConfig = {
    rules: {
        user: {
            required: true,
            email: true,
            maxlength: 64,
        },
        password: {
            required: true,
            minlength: 8,
            maxlength: 32,
        },
    },
    messages: {
        user: invalidCredentialsMessage,
        password: invalidCredentialsMessage,

    },

    errorPlacement: function () {
    },
}

