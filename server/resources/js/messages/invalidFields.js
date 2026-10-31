export function showAlert(message) {
    const alert = $('<div class="alert alert-danger" role="alert"></div>');
    const messages = Array.isArray(message) ? message : [message];
    messages.forEach((text, index) => {
        if (index > 0) alert.append('<br/>');
        alert.append(document.createTextNode(text));
    });
    $('#alerts').empty().append(alert);
}

export function invalidFields(validator) {
    showAlert(validator.errorList.map(error => error.message));
}

