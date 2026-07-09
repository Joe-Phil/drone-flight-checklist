<?php
    /*
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Credentials: true');

    include 'connect.php';
    
    $templateId = $_POST['templateId'];

    $conn = (new Connect())->getConnection();
    $sql = "SELECT * FROM `template` WHERE `id` = '$templateId'";

    $result = $conn->query($sql);
    if ($result->num_rows === 1) {
        // output data of each row
        $row = mysqli_fetch_assoc($result);
        
        $id = $row['id'];
        $templateName = $row['templateName'];
        $assessmentId = $row['assessmentId'];
        $preId = $row['preId'];
        $postId = $row['postId'];

        $json = "{\"id\": $id, \"templateName\": \"$templateName\",";
        
        $sql = "SELECT * FROM `form` WHERE `id` = '$assessmentId'";
        $result = $conn->query($sql);

        if ($result->num_rows === 1) {

            $row = mysqli_fetch_assoc($result);

            $assessmentData = $row['formData'];
            $json .= "\"assessment\": $assessmentData,";
        }

        $sql = "SELECT * FROM `form` WHERE `id` = '$preId'";
        $result = $conn->query($sql);

        if ($result->num_rows === 1) {

            $row = mysqli_fetch_assoc($result);

            $preData = $row['formData'];
            $json .= "\"pre\": $preData,";
        }

        $sql = "SELECT * FROM `form` WHERE `id` = '$postId'";
        $result = $conn->query($sql);

        if ($result->num_rows === 1) {

            $row = mysqli_fetch_assoc($result);

            $postData = $row['formData'];
            $json .= "\"post\": $postData}";
        }

        echo $json;

    }else{
        //incorrect
        echo 'Not Success';
        mysqli_close($conn);
        exit();
    }*/

    
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=UTF-8');
    include 'connect.php';
    
    $templateId = $_POST['templateId'] ?? null;
    if (!$templateId) { echo json_encode(["error" => "No ID"]); exit; }

    $conn = (new Connect())->getConnection();
    $sql = "SELECT * FROM template WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $templateId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $data = [
            "id" => (int)$row['id'],
            "templateName" => $row['templateName'],
            "assessment" => json_decode($row['assessmentId'] ? fetchFormData($conn, $row['assessmentId']) : "{}"),
            "pre" => json_decode($row['preId'] ? fetchFormData($conn, $row['preId']) : "{}"),
            "post" => json_decode($row['postId'] ? fetchFormData($conn, $row['postId']) : "{}")
        ];
        echo json_encode($data);
    } else {
        echo json_encode(["error" => "Not found"]);
    }

    function fetchFormData($conn, $id) {
        $stmt = $conn->prepare("SELECT formData FROM form WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return ($res->num_rows === 1) ? $res->fetch_assoc()['formData'] : "{}";
    }
?>
    