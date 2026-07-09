<?php
/*
class TemplateFillUI{

    private $db = null;
    private $view = "";
    private $templateId = null;

    public function __construct($db, $templateId){
        $this->db = $db;
        $this->templateId = $templateId;
        $user = $_GET['user'];
        $currentUser = base64_decode($user);
        
        $this->view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="styles/list-style.css">
            <link rel="stylesheet" href="styles/template-fill-style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script src="script/template-fill.js"></script>
            <title>Fill Template</title>
        </head>
        <body>
            <div class="container">
                <div id="sidebar-menu">
                    <div class="absolute">
HTML;
        $this->view .= "<div class='menu'><a href='index.php?view=dashboard&user=$user'>Dashboard</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=forms&user=$user&query=&delete='>Form Builder</a></div>";
        $this->view .= "<div class='menu active-menu'><a href='index.php?view=templates&user=$user&query=&delete='>Checklist Template</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=submissions&user=$user&query='>Checklist Submission</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=mobile&user=$user'>Download Center</a></div>";
        $this->view .= "</div>";
        $this->view .= "<div class='sidebar-footer'>";
        $this->view .= "<div class='menu'><a href='index.php'>Logout</a></div>";
        $this->view .= "<div class='global-footer-note'>Developed By BINUS University</div>";
        $this->view .= "</div>";
        $this->view .= <<<HTML
                </div>
                <div class="template-container">
                    <div class="top-bar">
                        <i class="fa-solid fa-bars fa-2x" id="hamburger-menu" style="display: none; cursor: pointer; margin-right: 20px;"></i>
                        <div class='template-title'>Fill Template</div>
                        <div class='button-container'>
                            <a href='index.php?view=templates&user=$user&query=&delete='>
                                <div class='create-template-button'>
                                    <i class='fa-solid fa-arrow-left fa-lg' style='color:#ffffff;margin-right:15px'></i>Back to Templates
                                </div>
                            </a>
                        </div>
                    </div>
HTML;
        
        $this->getTemplateInfo();
        $this->getFormsList();
    }

    private function getTemplateInfo(){
        $templateDetail = $this->db->fetchDetailTemplate($this->templateId);
        $templateData = json_decode($templateDetail, true);
        
        if($templateData){
            $templateName = $templateData['templateName'];
            $this->view .= "<div class='template-info'>";
            $this->view .= "<h2>$templateName</h2>";
            $this->view .= "<p>Please fill out all the forms in this template to complete your submission.</p>";
            $this->view .= "</div>";

            // Submission title input (card UI)
            $this->view .= "<div class='submission-title-card'>";
            $this->view .= "  <div class='submission-title-header'>";
            $this->view .= "    <i class='fa-regular fa-file-lines'></i> Submission Title";
            $this->view .= "  </div>";
            $this->view .= "  <div class='submission-title-subtext'>Give this submission a clear, descriptive name.</div>";
            $this->view .= "  <input type='text' id='submission-title' class='submission-title-input' placeholder='e.g. Flight 23 - Site A - 2025-08-10' />";
            $this->view .= "</div>";
        }
    }

    private function getFormsList(){
        $templateDetail = $this->db->fetchDetailTemplate($this->templateId);
        error_log("Template detail: " . $templateDetail);
        $templateData = json_decode($templateDetail, true);
        error_log("Template data: " . print_r($templateData, true));
        
        if($templateData){
            error_log("Template has data, checking form IDs:");
            error_log("Assessment ID: " . ($templateData['assessmentId'] ?? 'not set'));
            error_log("Pre ID: " . ($templateData['preId'] ?? 'not set'));
            error_log("Post ID: " . ($templateData['postId'] ?? 'not set'));
            
            $this->view .= "<div class='forms-list-container'>";
            $this->view .= "<h3>Forms to Complete:</h3>";
            $this->view .= "<div class='forms-grid'>";
            
            // Get assessment form
            if(!empty($templateData['assessmentId']) && $templateData['assessmentId'] != '0'){
                error_log("Fetching assessment form ID: " . $templateData['assessmentId']);
                $basic = $this->db->fetchFormBasic($templateData['assessmentId']);
                if ($basic && !empty($basic['formName'])) {
                    $this->view .= $this->createFormCard('Assessment Form', htmlspecialchars($basic['formName']), $templateData['assessmentId'], 'assessment');
                } else {
                    error_log("Assessment form basic not found for ID: " . $templateData['assessmentId']);
                }
            }
            
            // Get pre-flight form
            if(!empty($templateData['preId']) && $templateData['preId'] != '0'){
                error_log("Fetching pre-flight form ID: " . $templateData['preId']);
                $basic = $this->db->fetchFormBasic($templateData['preId']);
                if ($basic && !empty($basic['formName'])) {
                    $this->view .= $this->createFormCard('Pre-Flight Form', htmlspecialchars($basic['formName']), $templateData['preId'], 'pre');
                } else {
                    error_log("Pre-flight form basic not found for ID: " . $templateData['preId']);
                }
            }
            
            // Get post-flight form
            if(!empty($templateData['postId']) && $templateData['postId'] != '0'){
                error_log("Fetching post-flight form ID: " . $templateData['postId']);
                $basic = $this->db->fetchFormBasic($templateData['postId']);
                if ($basic && !empty($basic['formName'])) {
                    $this->view .= $this->createFormCard('Post-Flight Form', htmlspecialchars($basic['formName']), $templateData['postId'], 'post');
                } else {
                    error_log("Post-flight form basic not found for ID: " . $templateData['postId']);
                }
            }
            
            $this->view .= "</div>";
            $this->view .= "</div>";
            
            // Check if any forms were added
            $formCardsCount = substr_count($this->view, 'form-card');
            error_log("Number of form cards created: " . $formCardsCount);
            
            if($formCardsCount == 0) {
                $this->view .= "<div class='no-forms-message'>";
                $this->view .= "<p>No forms found in this template.</p>";
                $this->view .= "<p>Please make sure the template has forms assigned to it.</p>";
                $this->view .= "</div>";
            } else {
                // Add submit all button only if there are forms
                $this->view .= "<div class='submit-all-container'>";
                $this->view .= "<button id='submit-all-forms' class='submit-all-btn' disabled>";
                $this->view .= "<i class='fa-solid fa-paper-plane fa-lg' style='color:#d4e9ea;margin-right:15px'></i>Submit All Forms";
                $this->view .= "</button>";
                $this->view .= "</div>";
            }
        }
    }

    private function createFormCard($type, $formName, $formId, $formType){
        $user = $_GET['user'];
        $card = "<div class='form-card' data-form-id='$formId' data-form-type='$formType'>";
        $card .= "<div class='form-card-header'>";
        $card .= "<h4>$type</h4>";
        $card .= "<span class='form-status' id='status-$formId'>Not Started</span>";
        $card .= "</div>";
        $card .= "<div class='form-card-body'>";
        $card .= "<p>$formName</p>";
        $card .= "</div>";
        $card .= "<div class='form-card-footer'>";
        $card .= "<a href='index.php?view=fillForm&user=$user&templateId=$this->templateId&formId=$formId&formType=$formType'>";
        $card .= "<button class='fill-form-btn'><i class='fa-solid fa-edit fa-sm' style='margin-right:8px;'></i>Fill Form</button>";
        $card .= "</a>";
        $card .= "</div>";
        $card .= "</div>";
        return $card;
    }

    public function getView(){
        $this->view .= <<<HTML
                </div>
            </div>
            <script>
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
            </script>
        </body>
        </html>
HTML;
        echo $this->view;
    }
}
*/
?>