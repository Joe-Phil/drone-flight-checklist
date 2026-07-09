<?php

class SubmissionsViewUI{

    private $db = null;
    private $view = <<<HTML
    <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link rel="stylesheet" href="styles/list-style.css">
                <link rel="stylesheet" href="styles/submission-view-style.css">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                <script src="script/submissions-view.js"></script>
                <title></title>
            </head>
            <body>
                <div>
                    <nav class="top-bar" id="top-bar">
    HTML;

    public function __construct($db, $id){
        $this->db = $db;
        $user = $_GET['user'];
        $this->view .= "<a class='back-button' href='index.php?view=submissions&user=$user&query='><i class='fa-solid fa-arrow-left-long fa-2x' style='color:#ffffff; margin-right:30px;'></i></a>";
        $this->view .= "<div class='header-title'><h1>View Submission</h1></div>";
        $this->view .= <<<HTML
                    </nav>
                    <div id="container">
                        <div class="submission-content">

        HTML;
        $subId = 0;
        $json = "";
        if(isset($_GET['id'])){
            $subId = $_GET['id'];
            $json = $this->db->fetchDetailSubmission($subId);
        }
        // Ensure valid JSON string for embedding in value attribute
        if (!$json || json_decode($json) === null) {
            $json = '{}';
        }
        $jsonEscaped = htmlspecialchars($json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $jsonObj = json_decode($json);
        $submissionName = isset($jsonObj->submissionName) ? htmlspecialchars($jsonObj->submissionName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '(No data)';
        $submittedBy = isset($jsonObj->submittedBy) ? htmlspecialchars($jsonObj->submittedBy, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
        $submittedDate = isset($jsonObj->submittedDate) ? htmlspecialchars($jsonObj->submittedDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
        
        $this->view .= "<input type='text' id='json' style='display: none;' value='$jsonEscaped'/>";

        // Header Row for Name and Download Button
        $this->view .= "<div class='submission-header-row'>";
        $this->view .= "<div class='submission-name'>$submissionName</div>";
        if ($subId != 0) {
            $this->view .= "<div class='download-container'>";
            $this->view .= "<a href='api/export_submission.php?id=$subId&user=$user' class='download-btn'>";
            $this->view .= "<i class='fa-solid fa-file-excel' style='margin-right: 10px;'></i>Download Excel</a>";
            $this->view .= "</div>";
        }
        $this->view .= "</div>";

        // Display submitter information
        if ($submittedBy || $submittedDate) {
            $this->view .= "<div class='submission-info'>";
            if ($submittedBy) {
                $this->view .= "<div class='info-item'><strong>Submitted By:</strong> $submittedBy</div>";
            }
            if ($submittedDate) {
                $this->view .= "<div class='info-item'><strong>Submitted Date:</strong> $submittedDate</div>";
            }
            $this->view .= "</div>";
        }

        // Server-side render of forms and answers to avoid JS structure mismatch
        $templateObj = null;
        if ($jsonObj && isset($jsonObj->templateId)) {
            $templateJson = $this->db->fetchDetailTemplate($jsonObj->templateId);
            $templateObj = json_decode($templateJson);

            // Debug: Log template structure
            error_log("Template data: " . print_r($templateObj, true));
        }
        $formDataObj = isset($jsonObj->formData) ? $jsonObj->formData : (object)[];
        
        // Debug: Log submission data structure
        error_log("Submission formData: " . print_r($formDataObj, true));

        // Render all three sections on a single page - Assessment, Pre-Flight, Post-Flight
        $this->view .= "<div class='answer-container'>";
        $this->renderFormAnswers('Assessment', 'assessment', $templateObj, $formDataObj);
        $this->renderFormAnswers('Pre-Flight', 'pre', $templateObj, $formDataObj);
        $this->renderFormAnswers('Post-Flight', 'post', $templateObj, $formDataObj);
        $this->view .= "</div>";

        $this->view .= <<<HTML
                        </div>
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
    }

    private function formatDateInAnswer($text) {
        // Convert yyyy-mm-dd format to dd-mm-yyyy
        // Date formatting removed to keep original yyyy-mm-dd format in answers
        }

    private function renderFormAnswers($title, $type, $templateObj, $formDataObj){
        // Get form questions from template and answers from submission
        $questions = [];

        // Get questions from template using form IDs
        if ($templateObj) {
            $formId = null;
            if ($type === 'assessment' && isset($templateObj->assessmentId) && $templateObj->assessmentId > 0) {
                $formId = $templateObj->assessmentId;
            } elseif ($type === 'pre' && isset($templateObj->preId) && $templateObj->preId > 0) {
                $formId = $templateObj->preId;
            } elseif ($type === 'post' && isset($templateObj->postId) && $templateObj->postId > 0) {
                $formId = $templateObj->postId;
            }
            
            if ($formId) {
                error_log("Found form ID $formId for type $type");
                // Get form detail to get questions
                $formDetail = $this->db->fetchDetailForm($formId);
                if ($formDetail) {
                    $formData = json_decode($formDetail, true);
                    error_log("Form data: " . print_r($formData, true));
                    if (isset($formData['formData']) && is_string($formData['formData'])) {
                        $questionsData = json_decode($formData['formData'], true);
                        error_log("Questions data: " . print_r($questionsData, true));
                        if (is_array($questionsData)) {
                            foreach ($questionsData as $question) {
                                if (isset($question['question'])) {
                                    $questions[] = $question['question'];
                                }
                            }
                        }
                    }
                }
            } else {
                error_log("No form ID found for type $type in template");
            }
        } else {
            error_log("Template object is null");
        }
        
        // Collect all answer sets
        $answerSets = [];
        
        // Try to handle new format first
        if (is_object($formDataObj) && isset($formDataObj->$type)) {
            $val = $formDataObj->$type;
            error_log("Found answers for type $type (new format): " . print_r($val, true));
            $answers = is_object($val) ? (array)$val : (is_array($val) ? $val : []);
            if (!empty($answers)) {
                $answerSets[] = ['label' => '', 'answers' => $answers, 'questionNames' => []];
            }
        } elseif (is_array($formDataObj) && isset($formDataObj[$type])) {
            $val = $formDataObj[$type];
            error_log("Found answers for type $type (new format array): " . print_r($val, true));
            $answers = is_object($val) ? (array)$val : (is_array($val) ? $val : []);
            if (!empty($answers)) {
                $answerSets[] = ['label' => '', 'answers' => $answers, 'questionNames' => []];
            }
        }

        if (empty($answerSets) && is_array($formDataObj)) {
            foreach ($formDataObj as $section) {
                if (isset($section->type) && strtolower($section->type) === $type) {
                    if ($type === 'assessment' && isset($section->answer) && is_array($section->answer)) {
                        $answers = [];
                        $questionNames = [];
                        foreach ($section->answer as $q) {
                            $answers[] = isset($q->answer) ? $q->answer : '';
                            if (isset($q->questionName)) {
                                $questionNames[] = $q->questionName;
                            }
                        }
                        $answerSets[] = ['label' => '', 'answers' => $answers, 'questionNames' => $questionNames];
                    } else if (($type === 'pre' || $type === 'post') && isset($section->answer) && is_array($section->answer)) {
                        foreach ($section->answer as $flight) {
                            if (isset($flight->data) && is_array($flight->data)) {
                                $answers = [];
                                $questionNames = [];
                                foreach ($flight->data as $q) {
                                    $answers[] = isset($q->answer) ? $q->answer : '';
                                    if (isset($q->questionName)) {
                                        $questionNames[] = $q->questionName;
                                    }
                                }
                                $flightNum = isset($flight->flightNum) ? "Flight " . $flight->flightNum : "";
                                $answerSets[] = ['label' => $flightNum, 'answers' => $answers, 'questionNames' => $questionNames];
                            }
                        }
                    }
                    break;
                }
            }
        }

        if (empty($answerSets)) { return; }

        // Start rendering the group
        $this->view .= "<div class='group'>";
        $this->view .= "<div class='group-title'>";
        $this->view .= "<div class='" . strtolower($title) . "-group-title'>$title</div>";
        $this->view .= "</div>";

        // Loop through each answer set
        foreach ($answerSets as $set) {
            $answers = $set['answers'];
            $questionNames = $set['questionNames'];
            $flightLabel = $set['label'];

            // Map questions to answers
            $displayData = [];
            if (!empty($questions)) {
                $questionKeys = array_keys($answers);
                for ($i = 0; $i < count($questions); $i++) {
                    $question = $questions[$i];
                    $answerKey = isset($questionKeys[$i]) ? $questionKeys[$i] : 'question'.($i+1);
                    $answer = isset($answers[$answerKey]) ? $answers[$answerKey] : '';

                    if (is_array($answer)) { $answerText = implode(', ', $answer); }
                    elseif (is_object($answer)) { $answerText = json_encode($answer); }
                    else { $answerText = (string)$answer; }

                    $displayData[] = ['question' => $question, 'answer' => $answerText];
                }
            } else if (!empty($questionNames)) {
                for ($i = 0; $i < count($questionNames); $i++) {
                    $question = $questionNames[$i];
                    $answer = isset($answers[$i]) ? $answers[$i] : '';

                    if (is_array($answer)) { $answerText = implode(', ', $answer); }
                    elseif (is_object($answer)) { $answerText = json_encode($answer); }
                    else { $answerText = (string)$answer; }
                    
                    $displayData[] = ['question' => $question, 'answer' => $answerText];
                }
            } else {
                foreach ($answers as $qId => $ans) {
                    $questionText = (string)$qId;
                    if (is_array($ans)) { $answerText = implode(', ', $ans); }
                    elseif (is_object($ans)) { $answerText = json_encode($ans); }
                    else { $answerText = (string)$ans; }
                    
                    $displayData[] = ['question' => $questionText, 'answer' => $answerText];
                }
            }

            // Render table for this set
            if (!empty($displayData)) {
                if ($flightLabel) {
                    $this->view .= "<div class='flight-header' style='background: #f4f4f4; padding: 10px 15px 10px 40px; font-weight: bold; border-left: 5px solid #0097da; margin-top: 30px; font-size: 1.15rem; text-transform: uppercase;'>$flightLabel</div>";
                }
                $this->view .= "<div class='all-question' style='display: block;'>";
                $this->view .= "<table>";
                $this->view .= "<thead><tr><th class='col-1'>Question / Statement</th><th class='col-2'>Answer</th></tr></thead>";
                $this->view .= "<tbody>";
                foreach ($displayData as $item) {
                    $qLabel = htmlspecialchars($item['question'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $answerRaw = $item['answer'];
                    $norm = str_replace('\\','/',$answerRaw);
                    $htmlAnswer = htmlspecialchars($answerRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                    if (preg_match('/\.(jpg|jpeg|png|gif|bmp)$/i', $norm) || stripos($norm, '/uploads/') !== false) {
                        $parts = preg_split('/\s*,\s*/', $answerRaw);
                        $htmlAnswer = '';
                        foreach ($parts as $p) {
                            $p = trim($p);
                            if ($p === '') continue;
                            $pNorm = str_replace('\\','/',$p);
                            if (preg_match('/^[A-Za-z]:\//', $pNorm)) { $p = 'uploads/' . basename($pNorm); }
                            elseif (strpos($pNorm, '/uploads/') !== false) { $p = ltrim(strstr($pNorm, '/uploads/'), '/'); }
                            $safe = htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $filename = basename($p);
                            $htmlAnswer .= "<div class='response-link-wrapper'><a href='".$safe."' target='_blank' class='response-link'>" . htmlspecialchars($filename, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</a></div>";
                        }
                    }
                    $this->view .= "<tr><td class='col-1'>$qLabel</td><td class='col-2'>$htmlAnswer</td></tr>";
                }
                $this->view .= "</tbody></table></div>";
            }
        }
        $this->view .= "</div>";
    }
}
