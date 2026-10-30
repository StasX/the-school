export const loginValidationConfig = {
    rules: {
        user: {
            required: true,
            email: true,
            maxlength: 64,
            minlength: 7,
        },
        password: {
            required: true,
            minlength: 8,
            maxlength: 32,
        },
    },
    messages: {
        user: {
            required: "Username is required.",
            email: "Please enter a valid email address.",
            maxlength: "Username is too long.",
            minlength: "Username is too short.",
        },
        password: {
            required: "Password is required.",
            minlength: "Password must contain at least 8 characters.",
            maxlength: "Password is too long.",
        },

    },

    errorPlacement: function () {
    },
}

