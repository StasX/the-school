import $ from 'jquery';
import Swal from 'sweetalert2';
import "jquery-validation";
import "jquery-validation/dist/additional-methods";

window.$ = $;
window.jQuery = $;
window.Swal = Swal;

// Enable cookies in AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Add validators
$.validator.addMethod(
    "filesize",
    function (value, element, maxSize) {
        if (this.optional(element)) {
            return true;
        }
        return element.files[0].size <= maxSize;
    },
    "File is too large."
);

$.validator.addMethod(
    "imagesize",
    function (value, element, dimensions) {
        if (this.optional(element)) {
            return true;
        }
        const width = $(element).data("image-width");
        const height = $(element).data("image-height");
        if (width === undefined || height === undefined) {
            return false;
        }
        return (
            width <= dimensions.width &&
            height <= dimensions.height
        );
    },
    "Image dimensions are too large."
);

$.validator.addMethod(
    "integerArray",
    function (value, element) {
        const values = $(element.form)
            .find('input[name="course[]"]:checked')
            .map(function () {
                return $(this).val();
            })
            .get();

        return values.every(val =>
            /^\d+$/.test(val) &&
            Number.isSafeInteger(Number(val)) &&
            Number(val) > 0
        );
    },
    "Courses must contain only integer IDs."
);

$.validator.addMethod(
    "phone",
    function (value, element) {
        if (this.optional(element)) {
            return true;
        }

        const digits = value.replace(/\D/g, "");

        return (
            /^\+?[0-9](?:[0-9\s\-().]*[0-9])?$/.test(value) &&
            digits.length >= 7 &&
            digits.length <= 16
        );
    },
    "Enter a valid phone number."
);
