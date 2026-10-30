export default function noPermissions(){
    Swal.fire({
                    icon: "error",
                    title: "You do not have permission to perform this action",
                    text: "Please contact your administrator if you believe this is an error.",
                    confirmButtonText: "OK",
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: "btn btn-dark"
                    }
                });
}
