import { invalidFields } from "./invalidFields";
import failure from "./failure";
import AuthFailure from "./authFailure";
import noPermissions from "./noPermissions";
import { courseNotFoundAlert } from "./courseFailure";

function studentNotFoundAlert() {
    Swal.fire({
        icon: "error",
        title: "Student not found",
        text: "The requested student could not be found.",
        confirmButtonText: "OK",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-dark"
        }
    });
}

const StudentFailure = {
    get(xhr) {
        switch (xhr.status) {
            case 401:
                AuthFailure.auth(xhr);
                break;
            case 403:
                noPermissions();
                break;
            case 404:
                studentNotFoundAlert();
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
                invalidFields([xhr.responseJSON.message]);
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
                studentNotFoundAlert();
                break;
            case 422:
                invalidFields([xhr.responseJSON.message]);
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
                studentNotFoundAlert();
                break;
            default:
                failure();
        }
    },

    unsubscribe(xhr) {
        switch (xhr.status) {
            case 401:
                AuthFailure.auth(xhr);
                break;
            case 403:
                noPermissions();
                break;
            case 404:
                if (xhr.responseJSON?.message === "Student not found") {
                    studentNotFoundAlert();
                } else if (xhr.responseJSON?.message === "Course not found") {
                    courseNotFoundAlert();
                } else {
                    failure();
                }
                break;
            default:
                failure();
        }
    }
}

export default StudentFailure;
