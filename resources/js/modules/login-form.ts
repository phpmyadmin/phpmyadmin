import $ from 'jquery';

/**
 * Login form: shows or hides the password, and locks the form while the login request is sent.
 */
export function onloadLoginForm (): void {
    $(document).on('click', '#login_form .login-password-toggle', function () {
        const button = this as HTMLButtonElement;
        const input = document.getElementById(button.getAttribute('aria-controls')) as HTMLInputElement | null;
        if (! input) {
            return;
        }

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        const label = show ? button.dataset.labelHide : button.dataset.labelShow;
        button.setAttribute('aria-pressed', show ? 'true' : 'false');
        button.setAttribute('aria-label', label);
        button.title = label;
        input.focus();
    });

    // Only the full page login: the session expired login is sent with AJAX and stays open on errors
    $(document).on('submit', '#login_form.disableAjax', function () {
        const form = this as HTMLFormElement;
        const password = form.querySelector<HTMLInputElement>('#input_password');
        if (password) {
            password.type = 'password';
        }

        // Read-only and not disabled: the browser does not send the values of disabled fields
        form.querySelectorAll<HTMLInputElement>('input.form-control').forEach(input => {
            input.readOnly = true;
        });

        form.querySelectorAll<HTMLButtonElement | HTMLInputElement>(
            '.login-password-toggle, input[type="submit"]'
        ).forEach(element => {
            // Deferred, so the submit button's own value is still sent with this submission
            setTimeout(() => {
                element.disabled = true;
            });
        });

        form.classList.add('is-submitting');
    });

    // Coming back with the browser's back button can restore the locked form from the cache
    window.addEventListener('pageshow', function (event) {
        if (! event.persisted) {
            return;
        }

        const form = document.querySelector<HTMLFormElement>('#login_form.is-submitting');
        if (! form) {
            return;
        }

        form.classList.remove('is-submitting');
        form.querySelectorAll<HTMLInputElement>('input.form-control').forEach(input => {
            input.readOnly = false;
        });

        form.querySelectorAll<HTMLButtonElement | HTMLInputElement>(
            '.login-password-toggle, input[type="submit"]'
        ).forEach(element => {
            element.disabled = false;
        });
    });
}
