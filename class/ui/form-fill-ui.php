<?php
/*
class FormFillUI{

    private $db = null;
    private $view = "";
    private $formId = null;
    private $templateId = null;
    private $formType = null;

    public function __construct($db, $formId, $templateId, $formType){
        $this->db = $db;
        $this->formId = $formId;
        $this->templateId = $templateId;
        $this->formType = $formType;
        $user = $_GET['user'];
        $currentUser = base64_decode($user);
        
        $this->view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="styles/form-fill-style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script src="script/form-fill.js"></script>
            <title>Fill Form</title>
        </head>
        <body>
            <div class="container">
                <div class="form-container">
                    <div class="top-bar">
                        <div class='form-title'>Fill Form</div>
                        <div class='button-container'>
                            <a href='index.php?view=fillTemplate&user=$user&templateId=$templateId'>
                                <div class='back-button'>
                                    <i class='fa-solid fa-arrow-left fa-lg' style='color:#ffffff;margin-right:15px'></i>Back to Template
                                </div>
                            </a>
                        </div>
                    </div>
HTML;
        
        $this->getFormInfo();
        $this->getFormQuestions();
    }

    private function getFormInfo(){
        $formDetail = $this->db->fetchDetailForm($this->formId);
        $formData = json_decode($formDetail, true);
        
        if($formData){
            $formName = $formData['formName'];
            $formType = $formData['formType'];

            $this->view .= "<div class='form-info'>";
            $this->view .= "<h2>$formName</h2>";
            $this->view .= "<p>Form Type: $formType</p>";
            $this->view .= "</div>";
        }
    }

    private function getFormQuestions(){
        error_log("Form ID: " . $this->formId);
        $formDetail = $this->db->fetchDetailForm($this->formId);
        $formData = json_decode($formDetail, true);
        
        // Debug: Log the raw form detail and parsed data
        error_log("Raw form detail: " . $formDetail);
        error_log("Parsed form data: " . print_r($formData, true));
        
        if($formData && isset($formData['formData'])){
            // Handle both array or string JSON for formData
            $rawFormData = $formData['formData'];
            if (is_array($rawFormData)) {
                $questionsData = $rawFormData;
            } else {
                $questionsData = json_decode((string)$rawFormData, true);
            }
            
            error_log("Questions data: " . print_r($questionsData, true));
            error_log("Questions data type: " . gettype($questionsData));
            error_log("Questions data count: " . (is_array($questionsData) ? count($questionsData) : 'not array'));
            
            if($questionsData === null) {
                // JSON decode failed
                error_log("JSON decode failed for formData: " . $formData['formData']);
                $this->view .= "<div class='no-questions-message'>";
                $this->view .= "<p>Error: Invalid form data format.</p>";
                $this->view .= "</div>";
                return;
            }
            
            if($questionsData && is_array($questionsData) && count($questionsData) > 0){
                $this->view .= "<form id='form-fill-form' class='form-fill-form'>";
                $this->view .= "<input type='hidden' name='templateId' value='$this->templateId'>";
                $this->view .= "<input type='hidden' name='formId' value='$this->formId'>";
                $this->view .= "<input type='hidden' name='formType' value='$this->formType'>";
                
                foreach($questionsData as $questionId => $question){
                    $this->view .= $this->createQuestionField($question, $questionId);
                }
                
                $this->view .= "<div class='form-actions'>";
                $this->view .= "<button type='button' id='save-draft' class='save-draft-btn'>Save Draft</button>";
                $this->view .= "<button type='submit' class='submit-form-btn'>Submit Form</button>";
                $this->view .= "</div>";
                $this->view .= "</form>";
            } else {
                // If no questions found, show a message and create a test form
                $this->view .= "<div class='no-questions-message'>";
                $this->view .= "<p>No questions found in this form.</p>";
                $this->view .= "<p>Questions data type: " . gettype($questionsData) . "</p>";
                $this->view .= "<p>Questions data: " . print_r($questionsData, true) . "</p>";
                $this->view .= "</div>";
                
                // Create a test form for debugging
                $this->view .= "<form id='form-fill-form' class='form-fill-form'>";
                $this->view .= "<input type='hidden' name='templateId' value='$this->templateId'>";
                $this->view .= "<input type='hidden' name='formId' value='$this->formId'>";
                $this->view .= "<input type='hidden' name='formType' value='$this->formType'>";
                
                $this->view .= "<div class='question-field'>";
                $this->view .= "<label for='test-question'>Test Question (Debug)</label>";
                $this->view .= "<input type='text' id='test-question' name='test-question' placeholder='This is a test question'>";
                $this->view .= "</div>";
                
                $this->view .= "<div class='form-actions'>";
                $this->view .= "<button type='button' id='save-draft' class='save-draft-btn'>Save Draft</button>";
                $this->view .= "<button type='submit' class='submit-form-btn'>Submit Form</button>";
                $this->view .= "</div>";
                $this->view .= "</form>";
            }
        } else {
            // If form data is not available, show a message
            $this->view .= "<div class='no-questions-message'>";
            $this->view .= "<p>Form data not available or empty.</p>";
            $this->view .= "<p>Form data keys: " . (is_array($formData) ? implode(', ', array_keys($formData)) : 'not array') . "</p>";
            $this->view .= "<p>Form data: " . print_r($formData, true) . "</p>";
            $this->view .= "</div>";
        }
    }

    private function createQuestionField($question, $questionId){
        $questionText = $question['question'] ?? '';
        $questionType = $question['type'] ?? 'text';
        $options = $question['option'] ?? [];
        $required = isset($question['required']) && $question['required'] ? 'required' : '';
        
        // Use question text as the name/key instead of questionId
        $questionKey = $questionText ?: "question$questionId";
        
        $field = "<div class='question-field'>";
        $field .= "<label for='question-$questionId'>$questionText</label>";
        
        switch($questionType){
            case 'text':
                $field .= "<input type='text' id='question-$questionId' name='$questionKey' $required>";
                break;
            case 'textarea':
                $field .= "<textarea id='question-$questionId' name='$questionKey' $required></textarea>";
                break;
            case 'number':
                $field .= "<input type='number' id='question-$questionId' name='$questionKey' $required>";
                break;
            case 'date':
                $field .= "<input type='date' id='question-$questionId' name='$questionKey' $required>";
                break;
            case 'dropdown':
                $field .= "<select id='question-$questionId' name='$questionKey' $required>";
                $field .= "<option value=''>Select an option</option>";
                if (is_array($options)) {
                    foreach($options as $option){
                        $field .= "<option value='$option'>$option</option>";
                    }
                }
                $field .= "</select>";
                break;
            case 'photo':
                // allow multiple uploads if requested
                $isMultiple = isset($question['multiple']) && $question['multiple'];
                $multipleAttr = $isMultiple ? 'multiple' : '';
                $nameAttr = $questionKey . ($isMultiple ? '[]' : '');
                $field .= "<input type='file' id='question-$questionId' name='$nameAttr' accept='image/*' $multipleAttr $required>";
                // preview area for existing images (populated via JS)
                $field .= "<div class='file-preview' id='preview-$questionId'></div>";
                break;
            case 'multiple':
                if (is_array($options)) {
                    foreach($options as $option){
                        $field .= "<div class='radio-option'>";
                        $field .= "<input type='radio' id='$questionId-$option' name='$questionKey' value='$option' $required>";
                        $field .= "<label for='$questionId-$option'>$option</label>";
                        $field .= "</div>";
                    }
                }
                break;
            case 'checklist':
                if (is_array($options)) {
                    foreach($options as $option){
                        $field .= "<div class='checkbox-option'>";
                        $field .= "<input type='checkbox' id='$questionId-$option' name='$questionKey' value='$option'>";
                        $field .= "<label for='$questionId-$option'>$option</label>";
                        $field .= "</div>";
                    }
                }
                break;
            default:
                $field .= "<input type='text' id='question-$questionId' name='$questionKey' $required>";
                break;
        }
        
        $field .= "</div>";
        return $field;
    }

    public function getView(){
        $this->view .= <<<HTML
                </div>
            </div>
        </body>
        </html>
HTML;
        echo $this->view;
    }
}
*/
?>