<?php

include_once __DIR__ . '/connect.php';

date_default_timezone_set('Asia/Jakarta');

class MasterDatabase {

    private $conn = null;

    function __construct(){
        $this->conn = (new Connect())->getConnection();
    }

    function getConnection(){
        return $this->conn;
    }

    function getDbError(){
        return $this->conn ? $this->conn->error : 'No DB connection';
    }

    private function getDisplayFormType($type) {
        $type = strtolower($type);
        if ($type === 'pre') return 'Pre-flight';
        if ($type === 'post') return 'Post-flight';
        if ($type === 'assessment') return 'Assessment';
        return strtoupper($type);
    }

    function validateLogin($input, $password){
        // Support login by username or email
        $sql = "SELECT * FROM user WHERE username=? OR email=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $input, $input);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = mysqli_fetch_assoc($result);
            // Hash password menggunakan SHA-512 untuk membandingkan
            $hashedPassword = hash('sha512', $password);
            if($row['password'] === $hashedPassword){
                // Use actual username for session even if email was used for login
                $username = $row['username'];
                $_SESSION['username'] = $username;
                $_SESSION['password'] = $hashedPassword;
                return $username; // Return the username to the UI
            }else{
                return "incorrect password";
            }
        }
        return "username or email does not exist";
    }

    function validateUnique($username){
        $username = $this->conn->real_escape_string($username);
        $sql = "SELECT * FROM user WHERE username='$username'";
        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            // not unique
            return 'Username already exists';
        }else{
            // unique
            return 'unique';
        }
    }

    function validateUniqueEmail($email){
        $email = $this->conn->real_escape_string($email);
        $sql = "SELECT * FROM user WHERE email='$email'";
        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            // not unique
            return 'Email already exists';
        }else{
            // unique
            return 'unique';
        }
    }

    function registUser($email, $username, $password){;
        $sql = "INSERT INTO `user`(`username`, `email`, `password`) VALUES ('$username','$email','$password')";
        if ($this->conn->query($sql) === TRUE){
            return true;
        }
        return false;
    }

    function updateUsername($oldUsername, $newUsername) {
        $oldUsername = $this->conn->real_escape_string($oldUsername);
        $newUsername = $this->conn->real_escape_string($newUsername);

        if ($this->validateUnique($newUsername) !== 'unique') {
            return "Username already exists";
        }

        $this->conn->begin_transaction();
        try {
            $this->conn->query("UPDATE user SET username = '$newUsername' WHERE username = '$oldUsername'");
            $this->conn->query("UPDATE form SET owner = '$newUsername' WHERE owner = '$oldUsername'");
            $this->conn->query("UPDATE form SET updatedBy = '$newUsername' WHERE updatedBy = '$oldUsername'");
            $this->conn->query("UPDATE template SET owner = '$newUsername' WHERE owner = '$oldUsername'");
            $this->conn->query("UPDATE template SET updatedBy = '$newUsername' WHERE updatedBy = '$oldUsername'");
            $this->conn->query("UPDATE submission SET submittedBy = '$newUsername' WHERE submittedBy = '$oldUsername'");
            $this->conn->commit();
            return "success";
        } catch (Exception $e) {
            $this->conn->rollback();
            return "Error: " . $e->getMessage();
        }
    }

    function updatePassword($username, $oldPassword, $newPassword) {
        $username = $this->conn->real_escape_string($username);

        // 1. Verify old password
        $sql = "SELECT password FROM user WHERE username=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if ($row['password'] !== hash('sha512', $oldPassword)) {
                return "Incorrect current password";
            }
        } else {
            return "User not found";
        }

        // 2. Update to new password
        $hashedNewPassword = hash('sha512', $newPassword);
        $updateSql = "UPDATE user SET password=? WHERE username=?";
        $updateStmt = $this->conn->prepare($updateSql);
        $updateStmt->bind_param("ss", $hashedNewPassword, $username);

        if ($updateStmt->execute()) {
            return "success";
        } else {
            return "Error updating password: " . $this->conn->error;
        }
    }

    function getUser($username){
        $sql = "SELECT * FROM user WHERE username=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function createPasswordResetToken($email) {
        // First check if email exists
        if ($this->validateUniqueEmail($email) === 'unique') {
            return "Email not found";
        }

        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $sql = "INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $email, $token, $expiry);

        if ($stmt->execute()) {
            return $token;
        }
        return false;
    }

    function verifyResetToken($token) {
        $sql = "SELECT email FROM password_resets WHERE token = ? AND expiry > NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row['email'];
        }
        return false;
    }

    function resetPasswordWithToken($token, $newPassword) {
        $email = $this->verifyResetToken($token);
        if (!$email) return "Invalid or expired token";

        $hashedNewPassword = hash('sha512', $newPassword);

        $this->conn->begin_transaction();
        try {
            // Update user password
            $stmt = $this->conn->prepare("UPDATE user SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashedNewPassword, $email);
            $stmt->execute();

            // Delete used token
            $stmt = $this->conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            $this->conn->commit();
            return "success";
        } catch (Exception $e) {
            $this->conn->rollback();
            return "Error: " . $e->getMessage();
        }
    }

    function getCountRecentData(){
        $sql = "SELECT COUNT(*) AS submission FROM `form`";
        $result = mysqli_query($this->conn, $sql);
        $resString = "{";

        if ($result->num_rows === 1) {
            $row = mysqli_fetch_assoc($result);
            $sub = $row['submission'];
            $resString .= "\"forms\": {\"count\": $sub,";
        }

        $sql = "SELECT * FROM form ORDER BY updatedDate DESC LIMIT 3;";
        $result = mysqli_query($this->conn, $sql);

        $resString .= "\"recent\": [";
        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $id = $row['id'];
                $formName = $row['formName'];
                $formType = $this->getDisplayFormType($row['formType']);
                $updatedBy = $row['updatedBy'];
                $updatedDate = $row['updatedDate'];
                $resString .= "{\"id\": $id, \"formName\": \"$formName\", \"formType\": \"$formType\", \"updatedBy\": \"$updatedBy\", \"updatedDate\": \"$updatedDate\"},";
            }
            $resString = substr($resString, 0, -1);
        }
        $resString .= "]},";

        $sql = "SELECT COUNT(*) AS submission FROM `template`";
        $result = mysqli_query($this->conn, $sql);

        if ($result->num_rows === 1) {
            $row = mysqli_fetch_assoc($result);
            $sub = $row['submission'];
            $resString .= "\"templates\": {\"count\": $sub,";
        }

        $sql = "SELECT * FROM template ORDER BY updatedDate DESC LIMIT 3;";
        $result = mysqli_query($this->conn, $sql);

        $resString .= "\"recent\": [";
        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $id = $row['id'];
                $templateName = $row['templateName'];
                $updatedBy = $row['updatedBy'];
                $updatedDate = $row['updatedDate'];
                $resString .= "{\"id\": $id, \"templateName\": \"$templateName\", \"updatedBy\": \"$updatedBy\", \"updatedDate\": \"$updatedDate\"},";
            }
            $resString = substr($resString, 0, -1);
        }
        $resString .= "]},";

        $sql = "SELECT COUNT(*) AS submission FROM `submission`";
        $result = mysqli_query($this->conn, $sql);

        if ($result->num_rows === 1) {
            $row = mysqli_fetch_assoc($result);
            $sub = $row['submission'];
            $resString .= "\"submissions\": {\"count\": $sub,";
        }

        $sql = "SELECT * FROM submission ORDER BY submittedDate DESC LIMIT 3;";
        $result = mysqli_query($this->conn, $sql);

        $resString .= "\"recent\": [";
        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $id = $row['id'];
                $submissionName = $row['submissionName'];
                $submittedBy = $row['submittedBy'];
                $submittedDate = $row['submittedDate'];
                $resString .= "{\"id\": $id, \"submissionName\": \"$submissionName\", \"submittedBy\": \"$submittedBy\", \"submittedDate\": \"$submittedDate\"},";
            }
            $resString = substr($resString, 0, -1);
        }
        $resString .= "]}}";

        return $resString;
    }

    function fetchAllForms($query, $sort = 'updatedDate', $order = 'DESC'){
        $user = base64_decode($_GET['user']);

        // Whitelist for sort and order to prevent SQL Injection
        $allowedSort = ['formName', 'formType', 'owner', 'updatedDate'];
        $allowedOrder = ['ASC', 'DESC'];

        if (!in_array($sort, $allowedSort)) $sort = 'updatedDate';
        if (!in_array($order, $allowedOrder)) $order = 'DESC';

        // Special case for owner since it might be updatedBy in old data
        $orderBy = $sort;
        if ($sort === 'owner') {
            $orderBy = "COALESCE(owner, updatedBy)";
        }

        $sql = "SELECT * FROM `form` WHERE (`is_public` = 1 OR `owner` = '$user') AND (`formName` LIKE '%$query%' OR `formType` LIKE '%$query%' OR `updatedBy` LIKE '%$query%') ORDER BY $orderBy $order";
        $result = mysqli_query($this->conn, $sql);
        $resString = "";
        $userEncoded = $_GET['user'];
        $userDecoded = $user;
        $hasOwnedItems = false;
        $rows = array();
        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
                $owner = isset($row["owner"]) ? $row["owner"] : $row["updatedBy"];
                if ($owner == $userDecoded) {
                    $hasOwnedItems = true;
                }
            }
            $no = 1;
            foreach($rows as $row) {
                $num = $no % 2 == 0 ? 'even' : 'odd';
                $owner = isset($row["owner"]) ? $row["owner"] : $row["updatedBy"];

                $resString .= "<tr class='table-data ". $num . "' id='data-" . $row['id'] . "'>" .
                    "<td class='col-1'>" . $no . "</td>" .
                    "<td class='col-2'>" . $row["formName"] . "</td>" .
                    "<td class='col-3'>" . $this->getDisplayFormType($row["formType"]) . "</td>" .
                    "<td class='col-4'>" . $owner . "</td>" .
                    "<td class='col-5'>" . $row["updatedDate"] . "</td>";
                /*
                if ($owner == $userDecoded) {
                    $resString .= "<td class='col-6'>" .
                        "<div style='display: flex; justify-content: center; gap: 20px;'>" .
                        "<a href='index.php?view=viewForms&user=$userEncoded&id=". $row['id'] . "'>" .
                        "<i id='edit-".$row['id'] . "' class='fa-solid fa-pen-to-square fa-lg action-icon edit-icon'></i></a>" .
                        "<i id='delete-".$row['id'] . "' class='fa-solid fa-trash fa-lg action-icon delete-data delete-icon'></i>" .
                        "</div>" . "</td>";
                } else {
                    $resString .= "<td class='col-6'></td>";
                }
                */
                $resString .= "</tr>";
                $no++;
            }
        } else {
            // return '<tr class="table-data"><td colspan="5">No Data Available</td></tr>';
            return '<tr class="table-data"><td colspan="5">No Data Available</td></tr>';
        }
        return $resString;
    }

    function fetchDetailForm($id){
        $sql = "SELECT * FROM form WHERE id='$id'";
        $result = $this->conn->query($sql);
        $json = "";

        if ($result->num_rows === 1) {
            // output data of each row
            $row = mysqli_fetch_assoc($result);
            // Use non-strict comparison to avoid type mismatch (string vs int)
            if($row['id'] == $id){
                $formName = $row['formName'];
                $formType = $row['formType'];
                $updatedBy = $row['updatedBy'];
                $updatedDate = $row['updatedDate'];
                $formData = $row['formData'];
                $isPublic = isset($row['is_public']) ? $row['is_public'] : 0;
                $owner = isset($row['owner']) ? $row['owner'] : $updatedBy;
                // Embed formData as raw JSON (already a JSON string in DB)
                $json = "{\"formName\": \"$formName\", \"formType\": \"$formType\", \"updatedBy\": \"$updatedBy\", \"updatedDate\": \"$updatedDate\", \"formData\": $formData, \"is_public\": $isPublic, \"owner\": \"$owner\"}";
            }
        }
        return $json;
    }

    function saveForm($id, $formName, $formType, $user, $json, $is_public){
        $user = base64_decode($user);
        $currentDateTime = new DateTime();
        $date = $currentDateTime->format("Y-m-d H:i:s");

        $sql = "";
        if($id == 0){
            $sql = "INSERT INTO `form`(`id`, `formName`, `formType`, `updatedBy`, `updatedDate`, `formData`, `owner`, `is_public`) VALUES (NULL,'$formName','$formType','$user','$date','$json','$user','$is_public')";
        }else{
            $sql = "UPDATE `form` SET `formName` = '$formName', `formType` = '$formType', `updatedBy` = '$user', `updatedDate` = '$date', `formData` = '$json', `is_public` = '$is_public' WHERE `id` = $id"; 
        }

        if ($this->conn->query($sql) === TRUE){
            return true;
        }
        return false;
    }

    function deleteForm($id){
        $sql = "DELETE FROM `form` WHERE id = $id";
        if ($this->conn->query($sql) === true) {
            return true;
        }
        return false;
    }

    // Lightweight fetch for form name/type without JSON composition pitfalls
    function fetchFormBasic($id){
        $id = intval($id);
        $sql = "SELECT formName, formType FROM form WHERE id=$id";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows === 1) {
            $row = mysqli_fetch_assoc($result);
            return [
                'formName' => $row['formName'],
                'formType' => $row['formType'],
            ];
        }
        return null;
    }

    function fetchAllTemplate($query, $sort = 'updatedDate', $order = 'DESC'){
        $user = base64_decode($_GET['user']);

        // Whitelist for sort and order to prevent SQL Injection
        $allowedSort = ['templateName', 'owner', 'updatedDate'];
        $allowedOrder = ['ASC', 'DESC'];

        if (!in_array($sort, $allowedSort)) $sort = 'updatedDate';
        if (!in_array($order, $allowedOrder)) $order = 'DESC';

        // Special case for owner since it might be updatedBy in old data
        $orderBy = $sort;
        if ($sort === 'owner') {
            $orderBy = "COALESCE(owner, updatedBy)";
        }

        $sql = "SELECT * FROM `template` WHERE (`is_public` = 1 OR `owner` = '$user') AND (`templateName` LIKE '%$query%' OR `updatedBy` LIKE '%$query%') ORDER BY $orderBy $order";
        $result = mysqli_query($this->conn, $sql);
        $resString = "";
        $userEncoded = $_GET['user'];
        $userDecoded = $user;
        $hasOwnedItems = false;
        $rows = array();
        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
                $owner = isset($row["owner"]) ? $row["owner"] : $row["updatedBy"];
                 if ($owner == $userDecoded) {
                    $hasOwnedItems = true;
                }
            }
            $no = 1;
            foreach($rows as $row) {
                $num = $no % 2 == 0 ? 'even' : 'odd';
                $owner = isset($row["owner"]) ? $row["owner"] : $row["updatedBy"];
                $resString .= "<tr class='table-data ". $num . "' id='data-" . $row['id'] . "'>" .
                    "<td class='col-1'>" . $no . "</td>" .
                    "<td class='col-2'>" . $row["templateName"] . "</td>" .
                    "<td class='col-3'>" . $owner . "</td>" .
                    "<td class='col-4'>" . $row["updatedDate"] . "</td>";
                /*
                if ($owner == $userDecoded) {
                    $resString .= "<td class='col-5'>" .
                        "<div style='display: flex; justify-content: center; gap: 20px;'>" .
                        "<a href='index.php?view=viewTemplates&user=$userEncoded&id=". $row['id'] . "'>" .
                        "<i id='edit-".$row['id'] . "' class='fa-solid fa-pen-to-square fa-lg action-icon edit-icon'></i></a>" .
                        "<a href='index.php?view=fillTemplate&user=$userEncoded&templateId=". $row['id'] . "'>" .
                        "<i class='fa-solid fa-clipboard-list fa-lg action-icon fill-icon'></i></a>" .
                        "<i id='delete-".$row['id'] . "' class='fa-solid fa-trash fa-lg action-icon delete-data delete-icon'></i>" .
                        "</div>" . "</td>";
                } else {
                    $resString .= "<td class='col-5'>" .
                        "<div style='display: flex; justify-content: center; gap: 20px;'>" .
                        "<a href='index.php?view=fillTemplate&user=$userEncoded&templateId=". $row['id'] . "'>" .
                        "<i class='fa-solid fa-clipboard-list fa-lg action-icon fill-icon'></i></a>" .
                        "</div>" . "</td>";
                }
                */
                $resString .= "</tr>";
                $no++;
            }
        } else {
            // return '<tr class="table-data"><td colspan="5">No Data Available</td></tr>';
            return '<tr class="table-data"><td colspan="4">No Data Available</td></tr>';
        }
        return $resString;
    }

    function getAllForm($user = null, $includeIds = []){
        $whereClauses = [];
        if ($user) {
            $user = $this->conn->real_escape_string($user);
            $whereClauses[] = "(`is_public` = 1 OR `owner` = '$user')";
        }

        if (!empty($includeIds)) {
            $ids = implode(',', array_map('intval', $includeIds));
            $whereClauses[] = "(id IN ($ids))";
        }

        $sql = "SELECT id, formName, formType FROM `form` ";
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' OR ', $whereClauses);
        }
        $sql .= " ORDER BY updatedDate DESC";

        $result = mysqli_query($this->conn, $sql);
        $json = "";

        if ($result && mysqli_num_rows($result) > 0) {
            $json = "[";
            while($row = mysqli_fetch_assoc($result)) {
                $id = $row['id'];
                $formName = $row['formName'];
                $formType = $row['formType'];
                $json .= "{\"id\": \"$id\", \"formName\" : \"$formName\", \"formType\" : \"$formType\"},";
            }
            $json = substr($json, 0, -1);
            $json .= "]";
        }

        return $json;
    }

    function fetchDetailTemplate($id){
        $sql = "SELECT * FROM template WHERE id='$id'";
        $result = $this->conn->query($sql);
        $json = "";

        if ($result->num_rows === 1) {
            // output data of each row
            $row = mysqli_fetch_assoc($result);
            // Use non-strict comparison to avoid type mismatch (string vs int)
            if($row['id'] == $id){
                $templateName = $row['templateName'];
                $assessmentId = $row['assessmentId'];
                $preId = $row['preId'];
                $postId = $row['postId'];
                $updatedBy = $row['updatedBy'];
                $updatedDate = $row['updatedDate'];
                $isPublic = isset($row['is_public']) ? $row['is_public'] : 0;
                $owner = isset($row['owner']) ? $row['owner'] : $updatedBy;
                $json = "{\"templateName\": \"$templateName\", \"assessmentId\": \"$assessmentId\", \"preId\": \"$preId\", \"postId\": \"$postId\", \"updatedBy\": \"$updatedBy\", \"updatedDate\": \"$updatedDate\", \"is_public\": $isPublic, \"owner\": \"$owner\"}";
            }
        }
        return $json;
    }

    function saveTemplate($id, $templateName, $assessmentId, $preId, $postId, $user, $is_public){
        try {
            $user = base64_decode($user);
            $currentDateTime = new DateTime();
            $date = $currentDateTime->format("Y-m-d H:i:s");

            // Ensure numeric values
            $id = intval($id);
            $assessmentId = $assessmentId === "0" || empty($assessmentId) ? 0 : intval($assessmentId);
            $preId = $preId === "0" || empty($preId) ? 0 : intval($preId);
            $postId = $postId === "0" || empty($postId) ? 0 : intval($postId);
            $is_public = intval($is_public);

            // Escape strings to prevent SQL injection
            $templateName = $this->conn->real_escape_string($templateName);
            $user = $this->conn->real_escape_string($user);

            // Debug logging
            error_log("Database saveTemplate Debug:");
            error_log("ID: " . $id);
            error_log("Template Name: " . $templateName);
            error_log("Assessment ID: " . $assessmentId);
            error_log("Pre ID: " . $preId);
            error_log("Post ID: " . $postId);
            error_log("Decoded User: " . $user);
            error_log("Is Public: " . $is_public);
            error_log("Date: " . $date);

            $sql = "";
            if($id == 0){
                $sql = "INSERT INTO `template` (`templateName`, `assessmentId`, `preId`, `postId`, `updatedBy`, `updatedDate`, `owner`, `is_public`) 
                        VALUES ('$templateName', $assessmentId, $preId, $postId, '$user', '$date', '$user', $is_public)";
            } else {
                $sql = "UPDATE `template` 
                        SET `templateName` = '$templateName',
                            `assessmentId` = $assessmentId,
                            `preId` = $preId,
                            `postId` = $postId,
                            `updatedBy` = '$user',
                            `updatedDate` = '$date',
                            `is_public` = $is_public 
                        WHERE `id` = $id";
            }

            error_log("SQL Query: " . $sql);

            if ($this->conn->query($sql) === TRUE){
                error_log("Database save successful");
                return true;
            }

            error_log("Database Error: " . $this->conn->error);
            return false;
        } catch (Exception $e) {
            error_log("Exception in saveTemplate: " . $e->getMessage());
            return false;
        }
    }

    function deleteTemplate($id){
        $sql = "DELETE FROM `template` WHERE id = $id";
        if ($this->conn->query($sql) === true) {
            return true;
        }
        return false;
    }

    function fetchAllSubmission($query, $sort = 'submittedDate', $order = 'DESC'){
        $user = base64_decode($_GET['user']);

        // Whitelist for sort and order to prevent SQL Injection
        $allowedSort = ['submissionName', 'submittedBy', 'submittedDate'];
        $allowedOrder = ['ASC', 'DESC'];

        if (!in_array($sort, $allowedSort)) $sort = 'submittedDate';
        if (!in_array($order, $allowedOrder)) $order = 'DESC';

        $sql = "SELECT * FROM `submission` WHERE (`submittedBy` = '$user') AND (`submissionName` LIKE '%$query%' OR `submittedBy` LIKE '%$query%') ORDER BY $sort $order";
        $result = mysqli_query($this->conn, $sql);
        $resString = "";
        $user = $_GET['user'];

        if (mysqli_num_rows($result) > 0) {
            // output data of each row
            $no = 1;
            while($row = mysqli_fetch_assoc($result)) {
                $num = $no % 2 == 0 ? 'even' : 'odd';
                $resString .= "<tr class='table-data ". $num . "' id='data-" . $row['id'] . "'>" .
                               "<td class='col-1'>" . $no . "</td>" .
                               "<td class='col-2'>" . $row["submissionName"] . "</td>" .
                               "<td class='col-3'>" . $row["submittedBy"] . "</td>" .
                               "<td class='col-4'>" . $row["submittedDate"] . "</td>" .
                               "<td class='col-5'>" .
                               "<div style='display:flex;gap:16px;justify-content:center'>" .
                               "<i class='fa-solid fa-trash fa-lg action-icon delete-submission' data-id='" . $row['id'] . "'></i>" .
                               "</div></td></tr>";
                $no = $no + 1;
            }
        } else {
            return '<tr class="table-data"><td colspan="5">No Data Available</td></tr>';
        }

        return $resString;
    }

    function fetchDetailSubmission($id){
        $user = base64_decode($_GET['user']);
        $sql = "SELECT * FROM submission WHERE id='$id' AND submittedBy='$user'";
        $result = $this->conn->query($sql);
        $json = "";

        if ($result->num_rows === 1) {
            // output data of each row
            $row = mysqli_fetch_assoc($result);
            if($row['id'] == $id){
                $submissionName = $row['submissionName'];
                $templateId = $row['templateId'];
                $submittedBy = $row['submittedBy'];
                $submittedDate = $row['submittedDate'];
                $formData = $row['formData'];
                $json = "{\"submissionName\": \"$submissionName\", \"templateId\": \"$templateId\", \"submittedBy\": \"$submittedBy\", \"submittedDate\": \"$submittedDate\", \"formData\": $formData}";
            }
        }
        return $json;       
    }

    function saveSubmission($templateId, $submissionName, $formData, $user){
        try {
            $user = base64_decode($user);
            $currentDateTime = new DateTime();
            $date = $currentDateTime->format("Y-m-d H:i:s");

            // Escape strings to prevent SQL injection
            $templateId = intval($templateId);
            $submissionName = $this->conn->real_escape_string($submissionName);
            $formData = $this->conn->real_escape_string($formData);
            $user = $this->conn->real_escape_string($user);

            $sql = "INSERT INTO `submission` (`submissionName`, `templateId`, `submittedBy`, `submittedDate`, `formData`) 
                    VALUES ('$submissionName', $templateId, '$user', '$date', '$formData')";

            if ($this->conn->query($sql) === TRUE){
                return $this->conn->insert_id;
            }

            error_log('saveSubmission SQL error: ' . $this->conn->error);
            return false;
        } catch (Exception $e) {
            error_log("Exception in saveSubmission: " . $e->getMessage());
            return false;
        }
    }

    function updateSubmission($submissionId, $formData, $user){
        try {
            $user = base64_decode($user);
            $currentDateTime = new DateTime();
            $date = $currentDateTime->format("Y-m-d H:i:s");

            // Escape strings to prevent SQL injection
            $submissionId = intval($submissionId);
            $formData = $this->conn->real_escape_string($formData);
            $user = $this->conn->real_escape_string($user);

            $sql = "UPDATE `submission` 
                    SET `formData` = '$formData', 
                        `submittedDate` = '$date' 
                    WHERE `id` = $submissionId AND `submittedBy` = '$user'";

            if ($this->conn->query($sql) === TRUE){
                return true;
            }

            error_log('updateSubmission SQL error: ' . $this->conn->error);
            return false;
        } catch (Exception $e) {
            error_log("Exception in updateSubmission: " . $e->getMessage());
            return false;
        }
    }

    // Store an uploaded file metadata for a submission
    function saveSubmissionFile($submissionId, $formType, $questionKey, $filePath){
        $submissionId = intval($submissionId);
        $formType = $this->conn->real_escape_string($formType);
        $questionKey = $this->conn->real_escape_string($questionKey);
        $filePath = $this->conn->real_escape_string($filePath);
        $sql = "INSERT INTO `submission_file` (`submission_id`,`form_type`,`question_key`,`file_path`) 
                VALUES ($submissionId,'$formType','$questionKey','$filePath')";
        return $this->conn->query($sql);
    }

    function getFilesForSubmission($submissionId){
        $submissionId = intval($submissionId);
        $sql = "SELECT * FROM submission_file WHERE submission_id=$submissionId";
        $result = $this->conn->query($sql);
        $files = [];
        if($result){
            while($row = mysqli_fetch_assoc($result)){
                $files[] = $row;
            }
        }
        return $files;
    }

    function getSubmissionByTemplate($templateId, $user){
        $user = base64_decode($user);
        $sql = "SELECT * FROM submission WHERE templateId='$templateId' AND submittedBy='$user' ORDER BY submittedDate DESC LIMIT 1";
        $result = $this->conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row;
        }
        return null;
    }

    function canViewFormInTemplate($formId, $templateId, $user){
        $formId = intval($formId);
        $templateId = intval($templateId);
        $user = $this->conn->real_escape_string($user);

        // Check if template is public or owned by user
        $sql = "SELECT assessmentId, preId, postId FROM template WHERE id = $templateId AND (is_public = 1 OR owner = '$user')";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = mysqli_fetch_assoc($result);
            if ($row['assessmentId'] == $formId || $row['preId'] == $formId || $row['postId'] == $formId) {
                return true;
            }
        }
        return false;
    }
}
