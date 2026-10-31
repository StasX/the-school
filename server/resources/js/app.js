import './bootstrap';
import school from './school';
import administration from './administration';
import notFound from './notFound';
import AuthApi from './api/authApi';
import { loginValidationConfig } from './validations/login';
import { showAlert } from './messages/invalidFields';
import { invalidCredentialsMessage } from './messages/loginFailure';
import AuthFailure from './messages/authFailure';
import { showWarning } from './messages/warnings';


$(function () {
    let user = null;
    let renderedHash = location.hash;
    let navigationPending = false;

    function render(data) {
        if (!data) {
            return;
        }

        user = data;
        renderedHash = location.hash;
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

    $(window).on('hashchange', () => {
        const targetHash = location.hash;
        const hasUnsavedChanges = $('#students-container, #courses-container, #administrators-container')
            .toArray().some(element => $(element).data('unsaved'));

        if (navigationPending || hasUnsavedChanges) {
            // Restore the current route without triggering another hashchange.
            history.replaceState(null, '', location.pathname + location.search + renderedHash);
            if (navigationPending || targetHash === renderedHash) return;

            navigationPending = true;
            showWarning('navigate away').then(result => {
                navigationPending = false;
                if (result.isConfirmed) {
                    history.replaceState(null, '', location.pathname + location.search + targetHash);
                    render(user);
                }
            });
            return;
        }

        render(user);
    });

    if (!location.hash) {
        const form = $('#login');
        form.validate(loginValidationConfig);
        form.on('submit', function (e) {
            e.preventDefault();
            if (!form.valid()) {
                showAlert(invalidCredentialsMessage);
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
