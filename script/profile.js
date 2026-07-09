$(document).ready(function(){

    function init() {
        handleSidebar();
        handleEditUsername();
        handlePasswordChange();
    }

    function handleSidebar() {
        $("#hamburger-menu").click(function(){
            $("#sidebar-menu").toggleClass("active");
        });

        $(document).click(function(event) {
            if(!$(event.target).closest('#sidebar-menu, #hamburger-menu').length) {
                if($('#sidebar-menu').hasClass('active')) {
                    $('#sidebar-menu').removeClass('active');
                }
            }
        });
    }

    function handleEditUsername() {
        const $editBtn = $('#edit-username-btn');
        const $cancelBtn = $('#cancel-edit-btn');
        const $display = $('#username-display');
        const $editContainer = $('#username-edit-container');
        const $usernameInput = $('#new-username-input');
        const $errorMsg = $('#username-error');
        const $saveBtn = $('#save-username-btn');

        if ($editBtn.length) {
            $editBtn.on('click', function() {
                $display.hide();
                $editBtn.hide();
                $editContainer.show();
                $usernameInput.focus();

                // check initial state (case where it might be opened empty)
                validateInput();
            });
        }

        if ($cancelBtn.length) {
            $cancelBtn.on('click', function() {
                $editContainer.hide();
                $display.show();
                $editBtn.show();
                $errorMsg.hide();
                // Reset value to current displayed username
                $usernameInput.val($display.text().trim());
            });
        }

        function validateInput() {
            if ($usernameInput.val().trim() === "") {
                $errorMsg.show();
                $saveBtn.css('opacity', '0.6').css('cursor', 'not-allowed');
            } else {
                $errorMsg.hide();
                $saveBtn.css('opacity', '1').css('cursor', 'pointer');
            }
        }

        if ($usernameInput.length) {
            $usernameInput.on('input', function() {
                validateInput();
            });
        }

        $('#edit-username-form').on('submit', function(e) {
            if ($usernameInput.val().trim() === "") {
                e.preventDefault();
                $errorMsg.show();
                return false;
            }
        });
    }

    function handlePasswordChange() {
        const $changeBtn = $('#change-password-btn');
        const $cancelBtn = $('#cancel-password-btn');
        const $display = $('#password-display');
        const $container = $('#password-change-container');
        const $form = $('#change-password-form');
        const $newPass = $('#new-password');
        const $confirmPass = $('#confirm-password');
        const $errorMsg = $('#password-error');

        if ($changeBtn.length) {
            $changeBtn.on('click', function() {
                $display.hide();
                $changeBtn.hide();
                $container.show();
            });
        }

        if ($cancelBtn.length) {
            $cancelBtn.on('click', function() {
                $container.hide();
                // $display.show();
                $changeBtn.show();
                $errorMsg.hide();
                $form[0].reset();
            });
        }

        $form.on('submit', function(e) {
            if ($newPass.val() !== $confirmPass.val()) {
                e.preventDefault();
                $errorMsg.show();
                return false;
            }
        });

        $newPass.add($confirmPass).on('input', function() {
            $errorMsg.hide();
        });
    }

    init();
});