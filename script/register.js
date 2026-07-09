$(document).ready(function() {
    // Toggle password visibility
    $('.togglePassword').click(function() {
        const targetId = $(this).data('target');
        const passwordField = $('#' + targetId);
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);

        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // Form validation
    $('#registerForm').on('submit', function(e) {
        let isValid = true;
        let errorMessage = "";

        // Helper function to set error
        function setError(message) {
            if (!errorMessage) { // Only set the first error encountered
                errorMessage = message;
            }
            isValid = false;
        }

        const email = $('#email').val().trim();
        const username = $('#username').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();

        // 1. Check for empty fields (matches Login page logic)
        if (email === '' || username === '' || password === '' || confirmPassword === '') {
            setError('Please fill all data');
        }
        // 2. Email format validation
        else {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                setError('Invalid email format. Please use a valid email (e.g., name@example.com)');
            }
            // 3. Password match validation
            else if (password !== confirmPassword) {
                setError('Password and confirm password is not match');
            }
        }

        if (!isValid) {
            e.preventDefault();
            const $globalError = $('#global-error');
            $globalError.text(errorMessage).show();
        }
    });

    // Hide error messages as user types
    $('input').on('input', function() {
        // Hide global error box when user starts correcting
        $('#global-error').fadeOut(300);
    });
});
