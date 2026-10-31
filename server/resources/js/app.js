import './bootstrap';
import school from './school';
import administration from './administration';
import notFound from './notFound';
import AuthApi from './api/authApi';
import { loginValidationConfig } from './validations/login';
import { invalidFields } from './messages/invalidFields';
import AuthFailure from './messages/authFailure';


$(function () {
    let user = null;

    function render(data) {
        if (!data) {
            return;
        }

        user = data;
        switch (location.hash) {
            case '#!school':
                school(user);
                break;

            case '#!administration':
                administration(user);
                break;

            default:
                if (location.hash) {
                    notFound(user);
                }
        }
    }

    $(window).on('hashchange', () => render(user));

    if (!location.hash) {
        const form = $('#login');
        const validator = form.validate(loginValidationConfig);
        form.on('submit', function (e) {
            e.preventDefault();
            if (!form.valid()) {
                invalidFields(validator);
                return;
            }
            const data = {
                email: $('#user').val(),
                password: $('#password').val()
            };
            AuthApi.login(data).done(function (data) {
                user = data.administrator;
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': data.token
                    }
                });
                if (location.hash === '#!school') {
                    render(user);
                } else {
                    location.hash = '#!school';
                }
            }).fail(AuthFailure.login);
        });
    } else {
        AuthApi.auth().done(function (data) {
            render(data);
        })
            .fail(AuthFailure.auth);
    }
});
