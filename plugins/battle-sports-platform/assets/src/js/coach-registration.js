/**
 * Battle Sports Platform — Coach registration form.
 * Client-side validation and UX.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('bsp-coach-register-form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            const password = form.querySelector('#bsp_password');
            if (password && password.value.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters.');
                password.focus();
                return false;
            }
        });
    });
})();
