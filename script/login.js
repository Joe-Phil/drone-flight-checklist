$(document).ready(function() {
    // Toggle password visibility
    $('#togglePassword').click(function() {
        const passwordField = $('#password');
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);

        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // Hide the error box when the user starts typing
    $('input').on('input', function() {
        $('.login-container > .error-message').fadeOut(300);
    });
});