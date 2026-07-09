$(document).ready(function() {
    // Toggle password visibility
    $('.togglePassword').click(function() {
        const input = $(this).siblings('input');
        const type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // Hide the error box
    $('input').on('input', function() {
        $('.error-message, .success-message').fadeOut(300);
    });
});
