$(document).ready(function(){
    $("#hamburger-menu").click(function(){
        $("#sidebar-menu").toggleClass("active");
    });

    // Close sidebar when clicking outside
    $(document).click(function(event) {
        if(!$(event.target).closest('#sidebar-menu, #hamburger-menu').length) {
            if($('#sidebar-menu').hasClass('active')) {
                $('#sidebar-menu').removeClass('active');
            }
        }
    });
});