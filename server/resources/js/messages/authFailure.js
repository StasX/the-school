import { invalidCredentials, showAlert } from "./invalidFields";

const AuthFailure = {
    login(xhr) {
        switch (xhr.status) {
            case 401:
                showAlert('Invalid username or password');
                break;
            case 429:
                showAlert('Too many requests. Please try again later.');
            default:
                showAlert('An error occurred. Please try again later.');
        }

    },
    logout(xhr) {
        Swal.fire({
            icon: "error",
            title: "Error occurred during logout",
            text: "Please try again later.",
            confirmButtonText: "OK",
            buttonsStyling: false,
            customClass: {
                confirmButton: "btn btn-dark"
            }
        });
    },
    auth(xhr) {
        if (xhr.status === 401) {
            location.hash = '';
            location.reload();
        } else {
            Swal.fire({
                icon: "error",
                title: "Some error occurred...",
                text: "Please try again later.",
                confirmButtonText: "OK",
                buttonsStyling: false,
                customClass: {
                    confirmButton: "btn btn-dark"
                }
            });
        }
    },
}

export default AuthFailure;
