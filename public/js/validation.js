// Lightweight client-side validation helpers
document.addEventListener('DOMContentLoaded', function () {
    function handleForm(id) {
        const form = document.getElementById(id);
        if (!form) return;
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                form.classList.add('was-validated');
                const first = form.querySelector(':invalid');
                if (first) first.focus();
            }
        });
    }

    handleForm('register-form');
    handleForm('login-form');
    handleForm('post-form');
    handleForm('post-form-edit');
});

