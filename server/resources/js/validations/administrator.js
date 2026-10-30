export function administratorValidationConfig({
    edit = false,
    owner = false
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
            role: {
                required: !edit,
                pattern: owner
                    ? /^owner$/
                    : /^(manager|sales)$/
            },
            password: {
                required: !edit
            },
            image: {
                required: !edit,
                extension: "jpg|jpeg|png|gif",
                filesize: 500 * 1024,
                imagesize: {
                    width: 250,
                    height: 250
                }
            }
        },

        messages: {
            name: {
                required: "Name is required.",
                minlength: "Name must contain at least 2 characters."
            },
            phone: {
                required: "Phone is required.",
                phone: "Enter a valid phone number."
            },
            email: {
                required: "Email is required.",
                email: "Enter a valid email address."
            },
            role: {
                required: "Role is required.",
                pattern: owner
                    ? "Owner has to stay Owner."
                    : "Role should be Manager or Sales."
            },
            password: {
                required: "Password is required."
            },
            image: {
                required: "Image is required.",
                extension: "Image must be a JPG, JPEG, PNG, or GIF file.",
                filesize: "Image must not exceed 500 KB.",
                imagesize: "Image dimensions must not exceed 250×250."
            }
        },

        errorPlacement: function () {
        },
    };
}
