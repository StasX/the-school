export function studentValidationConfig({
    edit = false,
} = {}) {
    return {
        rules: {
            name: {
                required: true,
                minlength: 2,
                maxlength: 32,
            },
            phone: {
                required: true,
                phone: true,
                maxlength: 16,
            },
            email: {
                required: true,
                email: true
            },
            image: {
                required: !edit,
                extension: "jpg|jpeg|png|gif",
                filesize: 500 * 1024,
                imagesize: {
                    width: 250,
                    height: 250
                }
            },
            "courses[]": {
                integerArray: true
            }
        },
        messages: {
            name: {
                required: "Name is required.",
                minlength: "Name must contain at least 2 characters.",
                maxlength: "Name too long",
            },
            phone: {
                required: "Phone is required.",
                phone: "Enter a valid phone number.",
                maxlength: "Phone too long",
            },
            email: {
                required: "Email is required.",
                email: "Enter a valid email address."
            },
            image: {
                required: "Image is required.",
                extension: "Image have to be jpg, png, gif file.",
                filesize: "Image must not exceed 500 KB.",
                imagesize: "Image too large."
            },
            "courses[]": {
                integerArray: "Courses must contain only integer IDs."
            },
        },

        errorPlacement: function () {
        },
    }
}
