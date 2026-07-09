$(document).ready(function() {
    // Hide the error/success box when the user starts typing
    $('input').on('input', function() {
        $('.error-message, .success-message').fadeOut(300);
    });
});
