<?php
ob_start();

class TemplatesViewUI{

    private $db = null;
    private $view = "";

    public function __construct($db, $id){
        $this->db = $db;
        $user = $_GET['user'];
        $templateId = $id;
        $mode = $_GET['mode'] ?? 'view'; // Default to 'view' mode

        // Check if user has access to this template
        if ($templateId != 0) {
            $json = $this->db->fetchDetailTemplate($templateId);
            if ($json) {
                $templateData = json_decode($json, true);
                $currentUser = base64_decode($user);
                $isOwner = isset($templateData['owner']) && $templateData['owner'] === $currentUser;
                $isPublic = isset($templateData['is_public']) && $templateData['is_public'] == 1;
                
                // If not owner and template is not public, redirect back
                if (!$isOwner && !$isPublic) {
                    header("Location: index.php?view=templates&user=$user&query=&delete=");
                    exit();
                }
                
                // Read-only if user is not owner OR if mode is explicitly 'view'
                $readOnly = (!$isOwner || $mode === 'view');
            }
        } else {
            // Creating new template is always in edit mode
            $readOnly = false;
            $mode = 'edit';
        }

        if ($templateId == 0) {
            $headerAction = "Create New";
        } else {
            $headerAction = $readOnly ? "View" : "Edit";
        }

        // Determine back button URL
        if ($mode === 'edit' && $templateId != 0) {
            $backUrl = "index.php?view=viewTemplates&user=$user&id=$templateId&mode=view";
        } else {
            $backUrl = "index.php?view=templates&user=$user&query=&delete=";
        }
        
        $this->view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link rel="stylesheet" href="styles/list-style.css">
                <link rel="stylesheet" href="styles/template-view-style.css">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="script/templates-view.js"></script>
                <title></title>
            </head>
            <body>
                <div id="form-modal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 id="modal-title">View Form</h2>
                            <span class="close-modal">&times;</span>
                        </div>
                        <div class="modal-body">
                            <iframe id="form-iframe" src="" frameborder="0"></iframe>
                        </div>
                    </div>
                </div>

                <div id="delete-modal" style="display: none;">
                    <div class="delete-container">
                        <div class="delete-confirmation">
                            <div class="delete-title">
                                <h2>Confirmation</h2>
                                <i class='fa-solid fa-circle-exclamation fa-5x' style='color:#ffc107; margin: 20px 0 10px 0;'></i>
                            </div>
                            <div class="delete-content">
                                Are you sure want to delete this template?
                                <form method="POST" id="delete-template-action">
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
                        <div class='back-button'><a href='$backUrl'><i class='fa-solid fa-arrow-left-long fa-2x' style='color:#ffffff; margin-right:30px;'></i></a></div>
                        <div class='header-title'><h1>{$headerAction} Template</h1></div>
                    </nav>
                    <div id="container">
                        <div class="content-box">
HTML;

        if ($readOnly && $templateId != 0) {
            $this->view .= '<div class="readonly-notice">You are viewing this template in read-only mode.</div>';
        }

        $this->view .= <<<HTML
                            <form method="POST" id="main-form">
                                <div class="label-row">
                                    <div class="label-title">Template Name</div>
HTML;

        // Show edit icon next to the title if user is owner and currently in view mode
        if (isset($isOwner) && $isOwner && $mode === 'view') {
            $this->view .= <<<HTML
                                    <div class="edit-icon-btn" title="Edit Template">
                                        <a href="index.php?view=viewTemplates&user=$user&id=$templateId&mode=edit">
                                            <i class="fa-solid fa-pen-to-square fa-lg"></i>
                                        </a>
                                    </div>
HTML;
        }

        $this->view .= <<<HTML
                                </div>
                                <input type="text" id="template-name" name="template-name" required
HTML;
        
        if ($readOnly) {
            $this->view .= ' readonly';
        }
        if (isset($templateData)) {
            $this->view .= ' value="' . htmlspecialchars($templateData['templateName']) . '"';
        }
        
        $this->view .= ">";
        
        $this->view .= <<<HTML
                                <div class="select-container">
                                    <div class="assessment-container">
                                        <div class="assessment-label">Assessment Form</div>
                                        <select id="assessment-select" name="assessment-form"
HTML;
        
        if ($readOnly) {
            $this->view .= ' disabled';
        }
        
        $this->view .= ">";
        $this->view .= '<option disabled selected value="empty"> -- select an option -- </option>';

        $currentUser = base64_decode($user);
        $includeIds = [];
        if (isset($templateData)) {
            if ($templateData['assessmentId'] != 0) $includeIds[] = $templateData['assessmentId'];
            if ($templateData['preId'] != 0) $includeIds[] = $templateData['preId'];
            if ($templateData['postId'] != 0) $includeIds[] = $templateData['postId'];
        }

        $jsonForms = $this->db->getAllForm($currentUser, $includeIds);
        $forms = json_decode($jsonForms);
        
        if($forms) {
            foreach ($forms as $opt){
                if($opt->formType === "assessment"){
                    $value = $opt->id;
                    $formName = $opt->formName;
                    $selected = '';
                    if (isset($templateData) && $templateData['assessmentId'] == $value) {
                        $selected = ' selected';
                    }
                    $this->view .= "<option value='$value'$selected>$formName</option>";
                }
            }
        }

        $this->view .= <<<HTML
                                        </select>
                                        <div class="view-form-link" id="assessment-view-link" style="display: none; margin-top: 8px;">
                                            <a href="javascript:void(0)" style="color: #0097da; text-decoration: none; font-size: 14px; font-weight: bold;">
                                                <i class="fa-solid fa-eye" style="margin-right: 5px;"></i>View Form
                                            </a>
                                        </div>
                                    </div>

                                    <div class="pre-container">
                                        <div class="pre-label">Pre-Flight Form</div>
                                        <select id="pre-select" name="pre-form"
HTML;
        
        if ($readOnly) {
            $this->view .= ' disabled';
        }
        
        $this->view .= ">";
        $this->view .= '<option disabled selected value="empty"> -- select an option -- </option>';

        if($forms) {
            foreach ($forms as $opt){
                if($opt->formType === "pre"){
                    $value = $opt->id;
                    $formName = $opt->formName;
                    $selected = '';
                    if (isset($templateData) && $templateData['preId'] == $value) {
                        $selected = ' selected';
                    }
                    $this->view .= "<option value='$value'$selected>$formName</option>";
                }
            }
        }

        $this->view .= <<<HTML
                                        </select>
                                        <div class="view-form-link" id="pre-view-link" style="display: none; margin-top: 8px;">
                                            <a href="javascript:void(0)" style="color: #0097da; text-decoration: none; font-size: 14px; font-weight: bold;">
                                                <i class="fa-solid fa-eye" style="margin-right: 5px;"></i>View Form
                                            </a>
                                        </div>
                                    </div>

                                    <div class="post-container">
                                        <div class="post-label">Post-Flight Form</div>
                                        <select id="post-select" name="post-form"
HTML;
        
        if ($readOnly) {
            $this->view .= ' disabled';
        }
        
        $this->view .= ">";
        $this->view .= '<option disabled selected value="empty"> -- select an option -- </option>';

        if($forms) {
            foreach ($forms as $opt){
                if($opt->formType === "post"){
                    $value = $opt->id;
                    $formName = $opt->formName;
                    $selected = '';
                    if (isset($templateData) && $templateData['postId'] == $value) {
                        $selected = ' selected';
                    }
                    $this->view .= "<option value='$value'$selected>$formName</option>";
                }
            }
        }

        $this->view .= <<<HTML
                                        </select>
                                        <div class="view-form-link" id="post-view-link" style="display: none; margin-top: 8px;">
                                            <a href="javascript:void(0)" style="color: #0097da; text-decoration: none; font-size: 14px; font-weight: bold;">
                                                <i class="fa-solid fa-eye" style="margin-right: 5px;"></i>View Form
                                            </a>
                                        </div>
                                    </div>
                                </div>
HTML;

        $json = "";
        if($templateId != 0){
            //edit template
            $json = $this->db->fetchDetailTemplate($templateId);
        }
        $this->view .= "<input type='text' id='json' name='json' value='$json' style='display: none'>";
        $this->view .= "<input type='text' id='template-id' name='template-id' value='$templateId' style='display: none'>";
        $this->view .= "<input type='hidden' id='assessment-id' name='assessment-id' value=''>";
        $this->view .= "<input type='hidden' id='pre-id' name='pre-id' value=''>";
        $this->view .= "<input type='hidden' id='post-id' name='post-id' value=''>";
        $this->view .= "<input type='hidden' id='user-encoded' value='$user'>";

        // Visibility checkbox (always shown, but disabled in read-only mode)
        $isPublicChecked = (isset($templateData) && isset($templateData['is_public']) && $templateData['is_public']) ? " checked" : "";
        $disabledAttr = $readOnly ? " disabled" : "";

        $this->view .= <<<HTML
                                <div class="footer-actions">
                                    <div class="template-visibility">
                                        <label for="is_public">Make template public:</label>
                                        <input type="checkbox" id="is_public" name="is_public" value="on" $isPublicChecked $disabledAttr>
                                    </div>
HTML;

        if (!$readOnly) {
            $this->view .= <<<HTML
                                    <div id="save-container">
                                        <button id="save-button" type="button">Save Template</button>
                                    </div>
HTML;
        }

        $this->view .= <<<HTML
                                    <div class="delete-template-container">
HTML;

        if (!$readOnly && $templateId != 0) {
            $this->view .= <<<HTML
                                        <button id="open-delete-modal" type="button" class="delete-template-btn">
                                            <i class="fa-solid fa-trash-can" style="margin-right: 10px;"></i>Delete Template
                                        </button>
HTML;
        }

        $this->view .= <<<HTML
                                    </div>
                                </div>
HTML;

        if (!$readOnly) {
            $this->view .= '<button id="save" type="submit" style="display: none">Save Template</button>';
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
                $this->deleteTemplate();
            } else {
                $this->saveTemplate();
            }
        }
    }

    private function deleteTemplate(){
        $id = $_GET['id'];
        $user = $_GET['user'];
        if ($id != 0) {
            $isDeleted = $this->db->deleteTemplate($id);
            if ($isDeleted) {
                header("Location: index.php?view=templates&user=$user&query=&delete=1");
                exit();
            } else {
                echo "<script>alert('Failed to delete template');</script>";
            }
        }
    }

    private function saveTemplate(){
        $id = $_GET['id'];
        $templateName = $_POST['template-name'] ?? '';
        $assessmentId = $_POST['assessment-id'] ?? '0';
        $preId = $_POST['pre-id'] ?? '0';
        $postId = $_POST['post-id'] ?? '0';
        $user = $_GET['user'];
        $is_public = isset($_POST['is_public']) ? 1 : 0;

        // Debug logging
        error_log("Template Save Debug:");
        error_log("ID: " . $id);
        error_log("Template Name: " . $templateName);
        error_log("Assessment ID: " . $assessmentId);
        error_log("Pre ID: " . $preId);
        error_log("Post ID: " . $postId);
        error_log("User: " . $user);
        error_log("Is Public: " . $is_public);

        // Check if user has permission to save
        if ($id != 0) {
            $existingJson = $this->db->fetchDetailTemplate($id);
            if ($existingJson) {
                $templateData = json_decode($existingJson, true);
                $currentUser = base64_decode($user);
                if (!isset($templateData['owner']) || $templateData['owner'] !== $currentUser) {
                    error_log("User does not have permission to save this template");
                    return;
                }
            }
        }

        $isSaved = $this->db->saveTemplate($id, $templateName, $assessmentId, $preId, $postId, $user, $is_public);

        error_log("Save Result: " . ($isSaved ? "true" : "false"));

        if($isSaved){
            header("Location: index.php?view=templates&user=$user&query=&delete=");
            exit();
        } else {
            error_log("Failed to save template");
        }
    }
}

ob_end_flush();
