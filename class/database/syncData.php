<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Credentials: true');

    include 'connect.php';
    
    $submissionName = $_POST['submissionName'];
    $templateId = $_POST['templateId'];
    $submittedBy = $_POST['submittedBy'];
    $submittedDate = $_POST['submittedDate'];
    $formData = $_POST['formData'];

    $conn = (new Connect())->getConnection();

    $sql = "INSERT INTO `submission`(`id`, `submissionName`, `templateId`, `submittedBy`, `submittedDate`, `formData`) VALUES (NULL,'$submissionName','$templateId','$submittedBy','$submittedDate','$formData')";

    if ($conn->query($sql) === TRUE){
        mysqli_close($conn);
        echo "Success Insert $submissionName";
    }else{
        mysqli_close($conn);
        echo "Not Success Insert $submissionName";
    }