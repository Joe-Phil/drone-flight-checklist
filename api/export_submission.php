<?php
include_once __DIR__ . '/../class/database/master-database.php';

// Initialize database
$db = new MasterDatabase();

// Get parameters
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userEncoded = isset($_GET['user']) ? $_GET['user'] : '';

if ($id === 0 || empty($userEncoded)) {
    die("Invalid request parameters.");
}

// Fetch submission details
$json = $db->fetchDetailSubmission($id);
if (empty($json)) {
    die("Submission not found or access denied.");
}

$submission = json_decode($json, true);
$submissionName = $submission['submissionName'] ?? 'submission';
$formDataRaw = $submission['formData'] ?? [];
$templateId = $submission['templateId'] ?? null;

$formData = is_string($formDataRaw) ? json_decode($formDataRaw, true) : $formDataRaw;
if (!is_array($formData)) $formData = [];

// Clean filename and use .xls for Excel compatibility
$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $submissionName) . '.xls';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=' . $filename);
header('Cache-Control: max-age=0');

// Fetch Template
$templateObj = null;
if ($templateId) {
    $templateJson = $db->fetchDetailTemplate($templateId);
    $templateObj = json_decode($templateJson, true);
}

function getFormQuestions($db, $formId) {
    if (!$formId || $formId == "0") return [];
    $formDetail = $db->fetchDetailForm($formId);
    if ($formDetail) {
        $data = json_decode($formDetail, true);
        if (isset($data['formData'])) {
            $questionsData = is_array($data['formData']) ? $data['formData'] : json_decode($data['formData'], true);
            if (is_array($questionsData)) {
                $questions = [];
                foreach ($questionsData as $q) {
                    if (isset($q['question'])) {
                        $questions[] = $q['question'];
                    }
                }
                return $questions;
            }
        }
    }
    return [];
}
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        .title { font-size: 18pt; font-weight: bold; text-align: center; }
        .section-header { font-weight: bold; background-color: #f2f2f2; border: 1px solid #000; text-align: center; }
        .table-header { font-weight: bold; background-color: #d9d9d9; border: 1px solid #000; text-align: center; }
        td { border: 0.5pt solid #ccc; padding: 8px; text-align: center; vertical-align: middle; }
        .empty-col { border: none; width: 60px; }
    </style>
</head>
<body>
    <table>

        <!-- Gap rows -->
        <tr><td class="empty-col"></td><td class="empty-col"></td><td colspan="4"></td></tr>
        <tr><td class="empty-col"></td><td class="empty-col"></td><td colspan="4"></td></tr>

        <!-- Submission Title -->
        <tr>
            <td class="empty-col"></td>
            <td class="empty-col"></td>
            <td colspan="4" class="title"><?php echo strtoupper(htmlspecialchars($submissionName)); ?></td>
        </tr>

        <!-- Gap rows -->
        <tr><td class="empty-col"></td><td class="empty-col"></td><td colspan="4"></td></tr>
        <tr><td class="empty-col"></td><td class="empty-col"></td><td colspan="4"></td></tr>

        <?php
        $sections = [
            'assessment' => ['title' => 'Assessment', 'idKey' => 'assessmentId'],
            'pre' => ['title' => 'Pre-Flight', 'idKey' => 'preId'],
            'post' => ['title' => 'Post-Flight', 'idKey' => 'postId']
        ];

        $isFirstTable = true;

        foreach ($sections as $type => $info) {
            $title = $info['title'];
            $formId = $templateObj ? ($templateObj[$info['idKey']] ?? null) : null;
            $questions = getFormQuestions($db, $formId);

            $answerSets = [];

            if (isset($formData[$type])) {
                $val = $formData[$type];
                if (!empty($val)) {
                    if (($type === 'pre' || $type === 'post') && isset($val[0]) && is_array($val[0]) && isset($val[0]['flightNum'])) {
                        foreach ($val as $flight) {
                            $answerSets[] = [
                                'label' => "Flight " . ($flight['flightNum'] ?? ''),
                                'answers' => $flight['data'] ?? []
                            ];
                        }
                    } else {
                        $answerSets[] = ['label' => 'N/A', 'answers' => $val];
                    }
                }
            }

            if (empty($answerSets) && is_array($formData)) {
                foreach ($formData as $section) {
                    $secType = isset($section['type']) ? strtolower($section['type']) : '';
                    if ($secType === $type) {
                        if ($type === 'assessment' && isset($section['answer']) && is_array($section['answer'])) {
                            $flatAnswers = [];
                            foreach ($section['answer'] as $q) {
                                $flatAnswers[$q['questionName'] ?? ''] = $q['answer'] ?? '';
                            }
                            $answerSets[] = ['label' => 'N/A', 'answers' => $flatAnswers];
                        } elseif (($type === 'pre' || $type === 'post') && isset($section['answer']) && is_array($section['answer'])) {
                            foreach ($section['answer'] as $flight) {
                                $fData = [];
                                if (isset($flight['data']) && is_array($flight['data'])) {
                                    foreach ($flight['data'] as $q) {
                                        $fData[$q['questionName'] ?? ''] = $q['answer'] ?? '';
                                    }
                                }
                                $flightNum = isset($flight['flightNum']) ? "Flight " . $flight['flightNum'] : "";
                                $answerSets[] = ['label' => $flightNum, 'answers' => $fData];
                            }
                        }
                        break;
                    }
                }
            }

            if (!empty($answerSets)) {
                if (!$isFirstTable) {
                    echo '<tr><td class="empty-col"></td><td class="empty-col"></td><td colspan="4"></td></tr>';
                    echo '<tr><td class="empty-col"></td><td class="empty-col"></td><td colspan="4"></td></tr>';
                }
                ?>
                <!-- Section Header -->
                <tr>
                    <td class="empty-col"></td>
                    <td class="empty-col"></td>
                    <td colspan="4" class="section-header"><?php echo strtoupper(htmlspecialchars($title)); ?> TABLE</td>
                </tr>

                <!-- Gap row -->
                <tr><td class="empty-col"></td><td class="empty-col"></td><td colspan="4"></td></tr>

                <!-- Table Columns -->
                <tr>
                    <td class="empty-col"></td>
                    <td class="empty-col"></td>
                    <td class="table-header">Section</td>
                    <td class="table-header">Flight Label</td>
                    <td class="table-header">Question / Statement</td>
                    <td class="table-header">Answer</td>
                </tr>

                <?php
                foreach ($answerSets as $set) {
                    $answers = $set['answers'];
                    $flightLabel = $set['label'];

                    if (!empty($questions)) {
                        $questionKeys = array_keys($answers);
                        for ($i = 0; $i < count($questions); $i++) {
                            $qText = $questions[$i];
                            $answerKey = isset($questionKeys[$i]) ? $questionKeys[$i] : 'question'.($i+1);
                            $ans = isset($answers[$answerKey]) ? $answers[$answerKey] : '';
                            $ansText = is_array($ans) ? implode(', ', $ans) : (string)$ans;
                            ?>
                            <tr>
                                <td class="empty-col"></td>
                                <td class="empty-col"></td>
                                <td><?php echo htmlspecialchars($title); ?></td>
                                <td><?php echo htmlspecialchars($flightLabel); ?></td>
                                <td><?php echo htmlspecialchars($qText); ?></td>
                                <td><?php echo htmlspecialchars($ansText); ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        foreach ($answers as $qKey => $ans) {
                            $ansText = is_array($ans) ? implode(', ', $ans) : (string)$ans;
                            ?>
                            <tr>
                                <td class="empty-col"></td>
                                <td class="empty-col"></td>
                                <td><?php echo htmlspecialchars($title); ?></td>
                                <td><?php echo htmlspecialchars($flightLabel); ?></td>
                                <td><?php echo htmlspecialchars($qKey); ?></td>
                                <td><?php echo htmlspecialchars($ansText); ?></td>
                            </tr>
                            <?php
                        }
                    }
                }
                $isFirstTable = false;
            }
        }
        ?>
    </table>
</body>
</html>
<?php
exit();
