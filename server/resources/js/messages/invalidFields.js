export function showAlert(message){
$('#alerts').html(`<div class="alert alert-danger" role="alert">${message}</div>`);
}

export function invalidFields(validator) {
    const errors = validator.errorList.map(error => error.message).join('<br/>');
showAlert(errors);
}

