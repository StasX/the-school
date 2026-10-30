import { invalidFields } from "./invalidFields";
import failure from "./failure";
import AuthFailure from "./authFailure";
import noPermissions from "./noPermissions";

function administratorNotFoundAlert() {
    Swal.fire({
        icon: "error",
        title: "Administrator not found",
        text: "The requested administrator could not be found.",
        confirmButtonText: "OK",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-dark"
        }
    });
}

const AdministratorFailure = {
    get(xhr) {
        switch (xhr.status) {
            case 401:
                AuthFailure.auth(xhr);
                break;
            case 403:
                noPermissions();
                break;
            case 404:
                administratorNotFoundAlert();
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
                administratorNotFoundAlert();
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
                administratorNotFoundAlert();
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
                administratorNotFoundAlert();
                break;
            default:
                failure();
        }
    }
}

export default AdministratorFailure;
