<?php

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
        $formDetail = $this->db->fetchDetailForm($this->formId);
        $formData = json_decode($formDetail, true);

        if($formData && isset($formData['formData'])){
            $rawFormData = $formData['formData'];
            if (is_array($rawFormData)) {
                $questionsData = $rawFormData;
            } else {
                $questionsData = json_decode((string)$rawFormData, true);
            }
            
            if($questionsData && is_array($questionsData)){
                $this->view .= "<form id='form-fill-form' class='form-fill-form' enctype='multipart/form-data'>";
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
            }
        }
    }

    private function createQuestionField($question, $questionId){
        $questionText = $question['question'] ?? '';
        $questionType = $question['type'] ?? 'text';
        $options = $question['option'] ?? [];
        $required = isset($question['required']) && $question['required'] ? 'required' : '';
        
        $questionKey = $questionText ?: "question$questionId";
        
        $field = "<div class='question-field' id='field-$questionId'>";
        $field .= "<label for='question-$questionId'>$questionText</label>";
        
        switch($questionType){
            case 'text':
                $field .= "<input type='text' id='question-$questionId' name='$questionKey' $required>";
                break;
            case 'textarea':
            case 'longtext':
                $field .= "<textarea id='question-$questionId' name='$questionKey' $required></textarea>";
                break;
            case 'number':
                $field .= "<input type='number' id='question-$questionId' name='$questionKey' $required>";
                break;
            case 'date':
                $field .= "<input type='date' id='question-$questionId' name='$questionKey' $required>";
                break;
            case 'time':
                $field .= "<input type='time' id='question-$questionId' name='$questionKey' $required>";
                break;
            case 'datetime':
                $field .= "<input type='datetime-local' id='question-$questionId' name='$questionKey' $required>";
                break;
            case 'duration':
                $field .= "<div class='duration-input-container' data-question-id='$questionId'>";
                $field .= "  <div style='display: flex; flex-direction: column; gap: 10px;'>";
                $field .= "    <div style='display: flex; gap: 10px;'>";
                $field .= "      <div style='flex: 1;'><small>Take Off</small><input type='time' class='take-off-time' required style='width: 100%;'></div>";
                $field .= "      <div style='flex: 1;'><small>Landing</small><input type='time' class='landing-time' required style='width: 100%;'></div>";
                $field .= "    </div>";
                $field .= "    <div style='width: 100%;'><small>Total Duration</small><input type='text' class='total-duration' name='$questionKey' placeholder='00:00' readonly $required style='width: 100%;'></div>";
                $field .= "  </div>";
                $field .= "</div>";
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
                $isMultiple = isset($question['multiple']) && $question['multiple'];
                $multipleAttr = $isMultiple ? 'multiple' : '';
                $nameAttr = $questionKey . ($isMultiple ? '[]' : '');
                $field .= "<input type='file' id='question-$questionId' name='$nameAttr' accept='image/*' $multipleAttr $required>";
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
                        $field .= "<input type='checkbox' id='$questionId-$option' name='{$questionKey}[]' value='$option'>";
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
