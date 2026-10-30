import './bootstrap';
import school from './school';
import administration from './administration';
import notFound from './notFound';
import AuthApi from './api/authApi';
import { loginValidationConfig } from './validations/login';
import {showAlert} from './messages/invalidFields';
import AuthFailure from './messages/authFailure';


$(function () {
    let user = null;

    function render(data) {
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
        form.validate(loginValidationConfig);
        form.on('submit', function (e) {
            e.preventDefault();
            if (!form.valid()) {
                showAlert('Too many requests. Please try again later.');;
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
                location.hash = '#!school';
            }).fail(AuthFailure.login);
        });
    } else {
        AuthApi.auth().done(function (data) {
            render(data);
        })
            .fail(AuthFailure.auth);
    }
});
