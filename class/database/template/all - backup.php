<?php
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
    echo $json;
    