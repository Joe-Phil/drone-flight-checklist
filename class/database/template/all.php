<?php
    /*
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Credentials: true');
    
    include 'connect.php';

    $conn = (new Connect())->getConnection();
    $sql = "SELECT * FROM `template`";

    $result = $conn->query($sql);
    $json = "[";
    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        while($row = mysqli_fetch_assoc($result)) {
            $id = $row['id'];
            $templateName = $row['templateName'];
            $json .= "{\"id\": $id, \"templateName\": \"$templateName\"},";
        }
        $json = substr($json, 0, -1);
        $json .= "]";
    }
    echo $json;*/

    
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Credentials: true');
    header('Content-Type: application/json; charset=UTF-8');

    include '../connect.php';

    $conn = (new Connect())->getConnection();

    $username = isset($_GET['username']) ? $_GET['username'] : '';

    $stmt = $conn->prepare("SELECT id, templateName FROM template WHERE is_public = 1 OR owner = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $templates = [];
    while($row = $result->fetch_assoc()) {
        $templates[] = [
            "id" => (int)$row['id'],
            "templateName" => $row['templateName']
        ];
    }

    echo json_encode($templates);
?>
