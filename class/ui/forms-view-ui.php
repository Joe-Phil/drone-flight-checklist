<?php
ob_start();

class FormsViewUI{

    private $db = null;
    private $view = "";

    public function __construct($db, $id){
        $this->db = $db;
        $user = $_GET['user'];
        $formId = $id;
        $mode = $_GET['mode'] ?? '';
        $templateId = $_GET['templateId'] ?? null;

        // Check if user has access to this form
        if ($formId != 0) {
            $json = $this->db->fetchDetailForm($formId);
            if ($json) {
                $formData = json_decode($json, true);
                $currentUser = base64_decode($user);
                $isOwner = isset($formData['owner']) && $formData['owner'] === $currentUser;
                $isPublic = isset($formData['is_public']) && $formData['is_public'] == 1;
                
                // Allow access if the form is part of a template the user can access
                $hasTemplateAccess = false;
                if ($templateId) {
                    $hasTemplateAccess = $this->db->canViewFormInTemplate($formId, $templateId, $currentUser);
                }

                // If not owner, not public, AND doesn't have template access, redirect back
                if (!$isOwner && !$isPublic && !$hasTemplateAccess) {
                    header("Location: index.php?view=forms&user=$user&query=&delete=");
                    exit();
                }
                
                // If not owner but form is public, OR if mode is 'view'
                $readOnly = (!$isOwner || $mode === 'view');
            }
        } else {
            $readOnly = false;
        }
        
        // Determine title label based on context
        if ($formId == 0) {
            $isCreate = "Create New";
        } else {
            // If user is not owner and form is public, show view mode
            if (isset($readOnly) && $readOnly) {
                $isCreate = "View";
            } else {
                $isCreate = "Edit";
            }
        }

        // Determine back button URL
        // If we are in edit mode for an existing form, go back to view mode
        if ($mode === 'edit' && $formId != 0) {
            $backUrl = "index.php?view=viewForms&user=$user&id=$formId&mode=view";
        } else {
            $backUrl = "index.php?view=forms&user=$user&query=&delete=";
        }

        $this->view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link rel="stylesheet" href="styles/list-style.css">
                <link rel="stylesheet" href="styles/form-view-style.css">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="script/forms-view.js"></script>
                <title></title>
            </head>
            <body>
                <div id="delete-modal" style="display: none;">
                    <div class="delete-container">
                        <div class="delete-confirmation">
                            <div class="delete-title">
                                <h2>Confirmation</h2>
                                <i class='fa-solid fa-circle-exclamation fa-5x' style='color:#ffc107; margin: 20px 0 10px 0;'></i>
                            </div>
                            <div class="delete-content">
                                Are you sure want to delete this form?
                                <form method="POST" id="delete-form-action">
                                    <input type="hidden" name="action" value="delete">
                                    <div class='confirm-button'>
                                        <div class='no-button' id="close-delete-modal">No</div>
                                        <button type="submit" class="yes-button">Yes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <nav class="top-bar" id="top-bar">
HTML;

        // Show back button unless we are inside a template modal
        if ($templateId === null) {
            $this->view .= "<div class='back-button'><a href='$backUrl'><i class='fa-solid fa-arrow-left-long fa-2x' style='color:#ffffff; margin-right:30px;'></i></a></div>";
        }

        $this->view .= <<<HTML
                        <div class='header-title'><h1>$isCreate Form</h1></div>
                    </nav>
                    <div id="container">
                        <div class="content-box">
HTML;

        if ($readOnly && $formId != 0) {
            $notice = ($mode === 'view') ? "You are viewing this form in read-only mode." : "You are viewing this form in read-only mode because you are not the owner.";
            $this->view .= '<div class="readonly-notice">'.$notice.'</div>';
        }

        $this->view .= <<<HTML
                            <form method="POST" id="main-form">
                                <div class="header-info-row">
                                    <div class="header-info-item">
                                        <div class="header-label-row">
                                            <div class="header-input">Form Name</div>
HTML;

        if (isset($isOwner) && $isOwner && ($mode === 'view' || $mode === '') && $templateId === null) {
            $this->view .= <<<HTML
                                            <div class="edit-icon-btn mobile-only-icon" title="Edit Form">
                                                <a href="index.php?view=viewForms&user=$user&id=$formId&mode=edit">
                                                    <i class="fa-solid fa-pen-to-square fa-lg"></i>
                                                </a>
                                            </div>
HTML;
        }

        $this->view .= <<<HTML
                                        </div>
                                        <input type="text" name="form-name" id="form-name" placeholder="Enter form name"
HTML;
        
        if ($readOnly) {
            $this->view .= ' readonly';
            if (isset($formData)) {
                $this->view .= ' value="' . htmlspecialchars($formData['formName']) . '"';
            }
        }
        
        $this->view .= <<<HTML
                                            >
                                    </div>
                                    <div class="header-info-item">
                                        <div class="header-label-row">
                                            <div class="header-input">Form Type</div>
HTML;

        // Show edit icon next to the "Form Type" label if user is owner and currently in view mode
        if (isset($isOwner) && $isOwner && ($mode === 'view' || $mode === '') && $templateId === null) {
            $this->view .= <<<HTML
                                            <div class="edit-icon-btn desktop-only-icon" title="Edit Form">
                                                <a href="index.php?view=viewForms&user=$user&id=$formId&mode=edit">
                                                    <i class="fa-solid fa-pen-to-square fa-lg"></i>
                                                </a>
                                            </div>
HTML;
        }

        $this->view .= <<<HTML
                                        </div>
                                        <select id="form-type-dropdown"
HTML;
        
        if ($readOnly) {
            $this->view .= ' disabled';
        }
        
        $this->view .= <<<HTML
                                        >
HTML;
        $selectedType = isset($formData) ? $formData['formType'] : '';
        $this->view .= '<option value="assessment"' . ($selectedType === 'assessment' ? ' selected' : '') . '>Assessment</option>';
        $this->view .= '<option value="pre"' . ($selectedType === 'pre' ? ' selected' : '') . '>Pre-Flight</option>';
        $this->view .= '<option value="post"' . ($selectedType === 'post' ? ' selected' : '') . '>Post-Flight</option>';
        
        $this->view .= <<<HTML
                                        </select>
                                        <input type="text" name="form-type" id="form-type" style="display: none" value="$selectedType">
                                    </div>
                                </div>
                                
                                <div id="container-field">
                                <!-- for show the data -->
                                </div>
HTML;

        if (!$readOnly) {
            $this->view .= <<<HTML
                                <button id="add-new-field" type="button">
                                    <i class='fa-solid fa-plus fa-lg' style='color:#FFFFFF;margin-right:15px'></i>Add New Field
                                </button>
HTML;
        }

        $json = "";
        if($formId != 0){
            //edit form
            $json = $this->db->fetchDetailForm($formId);
        }
        $this->view .= "<input type='text' id='json' name='json' value='$json' style='display: none'>";
        $this->view .= "<input type='text' id='form-id' name='form-id' value='$formId' style='display: none'>";
        $readOnlyVal = $readOnly ? '1' : '0';
        $this->view .= "<input type='text' id='is-readonly' value='$readOnlyVal' style='display: none'>";

        // Visibility checkbox (always shown, but disabled in read-only mode)
        $isPublicChecked = (isset($formData) && isset($formData['is_public']) && $formData['is_public']) ? " checked" : "";
        $disabledAttr = $readOnly ? " disabled" : "";

        $this->view .= <<<HTML
                                <div class="footer-actions">
                                    <div class="form-visibility">
                                        <label for="is_public">Make form public:</label>
                                        <input type="checkbox" id="is_public" name="is_public" value="on" $isPublicChecked $disabledAttr>
                                    </div>
HTML;

        if (!$readOnly) {
            $this->view .= <<<HTML
                                    <div id="save-container">
                                        <button id="save-button" type="button">Save Form</button>
                                    </div>
HTML;
        }

        $this->view .= <<<HTML
                                    <div class="delete-form-container">
HTML;

        if (!$readOnly && $formId != 0) {
            $this->view .= <<<HTML
                                        <button id="open-delete-modal" type="button" class="delete-form-btn">
                                            <i class="fa-solid fa-trash-can" style="margin-right: 10px;"></i>Delete Form
                                        </button>
HTML;
        }

        $this->view .= <<<HTML
                                    </div>
                                </div>
HTML;

        if (!$readOnly) {
            $this->view .= '<button id="save" type="submit" style="display: none">Save Form</button>';
        }
        
        $this->view .= <<<HTML
                            </form>
                        </div>
                    </div>
                    </div>
                </div>  
            </body>
        </html>
HTML;
    }

    public function getView(){
        echo $this->view;
        $this->handlePost();
    }

    private function handlePost(){
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['action']) && $_POST['action'] === 'delete') {
                $this->deleteForm();
            } else {
                $this->saveForm();
            }
        }
    }

    private function deleteForm(){
        $id = $_GET['id'];
        $user = $_GET['user'];
        if ($id != 0) {
            $isDeleted = $this->db->deleteForm($id);
            if ($isDeleted) {
                header("Location: index.php?view=forms&user=$user&query=&delete=1");
                exit();
            } else {
                echo "<script>alert('Failed to delete form');</script>";
            }
        }
    }

    private function saveForm(){
        $id = $_GET['id'];
        $formName = $_POST['form-name'] ?? '';
        $formType = $_POST['form-type'] ?? '';
        $user = $_GET['user'];
        $json = $_POST['json'] ?? '';
        $is_public = isset($_POST['is_public']) ? 1 : 0;

        // Debug logging
        error_log("Form Save Debug:");
        error_log("ID: " . $id);
        error_log("Form Name: " . $formName);
        error_log("Form Type: " . $formType);
        error_log("User: " . $user);
        error_log("Is Public: " . $is_public);

        // Check if user has permission to save
        if ($id != 0) {
            $existingJson = $this->db->fetchDetailForm($id);
            if ($existingJson) {
                $formData = json_decode($existingJson, true);
                $currentUser = base64_decode($user);
                if (!isset($formData['owner']) || $formData['owner'] !== $currentUser) {
                    error_log("User does not have permission to save this form");
                    return;
                }
            }
        }

        $isSaved = $this->db->saveForm($id, $formName, $formType, $user, $json, $is_public);

        error_log("Save Result: " . ($isSaved ? "true" : "false"));

        if($isSaved){
            header("Location: index.php?view=forms&user=$user&query=&delete=");
            exit();
        } else {
            error_log("Failed to save form");
        }
    }
}

ob_end_flush();
