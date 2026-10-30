export function courseValidationConfig({
    edit = false,
} = {}) {
    return {
        rules: {
            name: {
                required: true,
                minlength: 8,
                maxlength: 32,
            },
            description: {
                required: true,
                minlength: 8,
                maxlength: 500,
            },
            image: {
                required: !edit,
                extension: "jpg|jpeg|png|gif",
                filesize: 1024 * 1024,
                imagesize: {
                    width: 350,
                    height: 350,
                }
            }
        },
        messages: {
            name: {
                required: "Name is required.",
                minlength: "Name must contain at least 2 characters.",
                maxlength: "Name too long",
            },
            description: {
                required: "Description is required.",
                minlength: "Description must contain at least 2 characters.",
                maxlength: "Description too long",
            },
            image: {
                required: "Image is required.",
                extension: "Image have to be jpg, png, gif file.",
                filesize: "Image must not exceed 500 KB.",
                imagesize: "Image too large."
            }
        },

        errorPlacement: function () {
        },
    }
}
