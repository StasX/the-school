import { showAlert } from "./invalidFields";
import failure from "./failure";
import AuthFailure from "./authFailure";
import noPermissions from "./noPermissions";

export function courseNotFoundAlert() {
    Swal.fire({
        icon: "error",
        title: "Course not found",
        text: "The requested course could not be found.",
        confirmButtonText: "OK",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-dark"
        }
    });
}

export const CourseFailure = {
    get(xhr) {
        switch (xhr.status) {
            case 401:
                AuthFailure.auth(xhr);
                break;
            case 403:
                noPermissions();
                break;
            case 404:
                courseNotFoundAlert();
                break;
            default:
                failure();
        }
    },

    add(xhr) {
        console.log(xhr);
        switch (xhr.status) {
            case 401:
                AuthFailure.auth(xhr);
                break;
            case 403:
                noPermissions();
                break;
            case 422:
                showAlert(xhr.responseJSON?.message ?? "Please check the entered fields and try again.");
                break;
            default:
                failure();
        }
    },

    update(xhr) {
        console.log(xhr);
        switch (xhr.status) {
            case 401:
                AuthFailure.auth(xhr);
                break;
            case 403:
                noPermissions();
                break;
            case 404:
                courseNotFoundAlert();
                break;
            case 422:
                showAlert(xhr.responseJSON?.message ?? "Please check the entered fields and try again.");
                break;
            default:
                failure();
        }
    },

    remove(xhr) {
        switch (xhr.status) {
            case 401:
                AuthFailure.auth(xhr);
                break;
            case 403:
                noPermissions();
                break;
            case 404:
                courseNotFoundAlert();
                break;
            case 409:
                Swal.fire({
                    icon: "error",
                    title: "Cannot delete course",
                    text: "This course is taken by students and cannot be deleted.",
                    confirmButtonText: "OK",
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: "btn btn-dark"
                    }
                });
                break;
            default:
                failure();
        }
    }
}

