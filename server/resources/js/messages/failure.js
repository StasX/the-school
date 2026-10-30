export default function failure(){
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
