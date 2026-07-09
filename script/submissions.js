$(document).ready(function () {

    function init(){
        handleUI();
        handleSidebar();
        addEventRow();
        handleDeleteModal();
    }

    function addEventRow(){
        let $tableData = $('.table-data');    
        let length = $tableData.length;
        let user = $('#user')[0] ? $('#user')[0].value : '';
        for(let i = 0; i < length; i++){
            let id = $tableData[i].id.split('-')[1];
            let rowId = $("#data-" + id);
            rowId.on('click', function (){
                window.location.href = `index.php?view=viewSubmissions&user=${user}&id=${id}`;
            })
        }

        // Show delete modal
        $('.delete-submission').on('click', function(e){
            e.stopPropagation();
            const subId = $(this).data('id');
            // Check if element exists before setting value
            if($('#submission-id').length) {
                $('#submission-id').val(subId);
            }
            // Check if element exists before showing
            if($('#modal').length) {
                $('#modal').show();
            }
        });
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

    function handleDeleteModal() {
        $('#cancel-delete').on('click', function() {
            $('#modal').hide();
        });

        // Close modal when clicking outside of the confirmation container
        $('#modal').on('click', function(e) {
            if (e.target.id === 'modal') {
                $(this).hide();
            }
        });
    }

    function handleUI(){
        // Sidebar height is set by CSS (100vh)
    }

    init();

});