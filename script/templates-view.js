$(document).ready(function (){
    
    const userEncoded = $('#user-encoded').val();
    const modal = $('#form-modal');
    const iframe = $('#form-iframe');
    const closeBtn = $('.close-modal');

    function init(){
        handleSetData();
        loadData();
        handleUI();
        handleDropdownChanges();
        handleModalEvents();
        handleDeleteModal();
    }

    function openFormModal(formId) {
        if (formId && formId !== "empty" && formId !== "0") {
            const templateId = $('#template-id').val();
            // Pass templateId so FormsViewUI can verify access through the template
            const url = `index.php?view=viewForms&user=${userEncoded}&id=${formId}&mode=view&templateId=${templateId}`;

            // Use location.replace to avoid adding to the browser history stack
            if (iframe[0].contentWindow) {
                iframe[0].contentWindow.location.replace(url);
            } else {
                iframe.attr('src', url);
            }

            modal.show();
            $('body').css('overflow', 'hidden');
        }
    }

    function closeFormModal() {
        modal.hide();
        // Clear iframe using replace to avoid history entry
        if (iframe[0].contentWindow) {
            iframe[0].contentWindow.location.replace('about:blank');
        } else {
            iframe.attr('src', '');
        }
        $('body').css('overflow', 'auto');
    }

    function handleModalEvents() {
        closeBtn.on('click', function() {
            closeFormModal();
        });

        $(window).on('click', function(event) {
            if ($(event.target).is(modal)) {
                closeFormModal();
            }
        });

        // Click event for view form links
        $('.view-form-link a').on('click', function(e) {
            e.preventDefault();
            const type = $(this).closest('.view-form-link').attr('id').split('-')[0];
            const formId = $(`#${type}-select`).val();
            openFormModal(formId);
        });
    }

    function updateViewLinkVisibility(type, formId) {
        const linkContainer = $(`#${type}-view-link`);
        if (formId && formId !== "empty" && formId !== "0") {
            linkContainer.show();
        } else {
            linkContainer.hide();
        }
    }

    function handleDropdownChanges() {
        $('#assessment-select').on('change', function() {
            updateViewLinkVisibility('assessment', $(this).val());
        });
        $('#pre-select').on('change', function() {
            updateViewLinkVisibility('pre', $(this).val());
        });
        $('#post-select').on('change', function() {
            updateViewLinkVisibility('post', $(this).val());
        });
    }

    function loadData(){
        let $templateId = $('#template-id')[0].value;
        if($templateId != 0){
            let $jsonStr = $('#json')[0].value;
            if ($jsonStr) {
                let $json = JSON.parse($jsonStr);
                let templateName = $json.templateName;
                let assessmentId = $json.assessmentId != "0" ? $json.assessmentId : "empty";
                let preId = $json.preId  != "0" ? $json.preId : "empty";
                let postId = $json.postId  != "0" ? $json.postId : "empty";

                $('#template-name')[0].value = templateName;
                $('#assessment-select')[0].value = assessmentId;
                $('#pre-select')[0].value = preId;
                $('#post-select')[0].value = postId;

                updateViewLinkVisibility('assessment', assessmentId);
                updateViewLinkVisibility('pre', preId);
                updateViewLinkVisibility('post', postId);
            }
        }
    }

    function handleSetData(){
        let $saveButton = $('#save-button');
        let $save = $('#save');

        $saveButton.on('click', function (){
            let isEmpty = false;
            let templateName = $('#template-name');
            let alertMsg = "";

            let $assessmentDropdown = $('#assessment-select :selected')[0].value;
            let $preDropdown = $('#pre-select :selected')[0].value;
            let $postDropdown = $('#post-select :selected')[0].value;
            
            if(templateName[0].value == ""){
                isEmpty = true;
                alertMsg = "Please make sure all component are filled!";
            }else{
                if($assessmentDropdown == "empty" && $preDropdown == "empty" && $postDropdown == "empty"){
                    isEmpty = true;
                    alertMsg = "Please choose at least one form!";
                }
            }

            if(isEmpty){
                alert(alertMsg);
            }else{
                // Convert "empty" to "0" for database storage
                $('#assessment-id')[0].value = $assessmentDropdown === "empty" ? "0" : $assessmentDropdown;
                $('#pre-id')[0].value = $preDropdown === "empty" ? "0" : $preDropdown;
                $('#post-id')[0].value = $postDropdown === "empty" ? "0" : $postDropdown;
    
                $save.click();
            }
        })
    }

    function handleUI(){
        let $container = $('#container');
        let topBarHeight = $('#top-bar').height();
        let winHeight = $(window).height();
        let height = winHeight - topBarHeight;
        $container.css('min-height', height + 'px');
    }

    function handleDeleteModal() {
        $('#open-delete-modal').on('click', function() {
            $('#delete-modal').show();
        });

        $('#close-delete-modal').on('click', function() {
            $('#delete-modal').hide();
        });

        // Close modal when clicking outside of the confirmation container
        $('#delete-modal').on('click', function(e) {
            if (e.target.id === 'delete-modal') {
                $(this).hide();
            }
        });
    }

    init();

});