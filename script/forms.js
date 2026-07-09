$(document).ready(function () {

    function init(){
        handleUI();
        handleSidebar();
        // handleModal();
        // handleDelete();
        handleDeleteInfo();
        handleRowAction();
    }

    function handleUI(){
        // Sidebar height is set by CSS (100vh) so the footer with Logout stays visible in the viewport
    }

    function handleSidebar(){
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
    }

    /* function handleModal(){
        let $modal = $('#modal');
        $modal.on('click', function (){
            $modal.css("display", "none");
        })
    } */

    /* function handleDelete(){
        let $tableData = $('.table-data');
        for(let i = 0; i < $tableData.length; i++){
            let idString = $tableData[i].id;
            let id = idString.split("-")[1];
            $('#delete-' + id).on("click", function(e){
                e.stopPropagation();
                $('#form-id').val(id);
                $('#modal').css("display", "flex");
            })
        }
    } */

    function handleDeleteInfo(){
        let $deleteAlertEl = $('#delete-alert')[0];
        if($deleteAlertEl){
            $deleteAlertEl.classList.remove('show');
            void $deleteAlertEl.offsetWidth;
            $deleteAlertEl.classList.add('show');
        }
    }

    function handleRowAction(){
        let $tableData = $('.table-data');
        let length = $tableData.length;
        let user = $('#user')[0].value;
        for(let i = 0; i < length; i++){
            let rowId = $tableData[i].id;
            let $rowEl = $('#' + rowId);
            let id = rowId.split('-')[1];
            $rowEl.on('click', function (){
                // Redirect to view mode by default
                window.location.href = `index.php?view=viewForms&user=${user}&id=${id}&mode=view`;
            });
        }
    }

    init();

});