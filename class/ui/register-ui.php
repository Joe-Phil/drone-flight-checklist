<?php
class RegisterUI{

    private $db = null;
    private $error = '';
    private $view = '';

    public function __construct($db){
        $this->db = $db;
    }

    private function generateView(){
        $globalError = $this->error;

        $submittedEmail = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : "";
        $submittedUsername = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : "";

        $display = $globalError ? "block" : "none";
        $style = "display: $display; color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px;";
        $messageHtml = "<div id='global-error' class='error-message' style='$style'>$globalError</div>";

        $this->view = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/register-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="script/register.js"></script>
    <title>Register Page</title>
</head>
<body>
    <div class="container">
        <div class="register-box-left">
            <div class="register-title">Drone Flight<br>Checklist</div>
            <form id="registerForm" class="register-container" method="POST" novalidate>
                <div class="register-statement ">Register Your Account</div>
                $messageHtml
                <div class="label-register-container">
                    <label class="label-register">Email</label>
                    <div class="container-register">
                        <i class='fa-solid fa-envelope fa-lg' style='color:#000000; margin-right:15px;'></i>
                        <input class="register-style" type="email" id="email" name="email" placeholder="example123@gmail.com" value="$submittedEmail">
                    </div>
                </div>
                <div class="label-register-container">
                    <label class="label-register">Username</label>
                    <div class="container-register">
                        <i class='fa-solid fa-user fa-lg' style='color:#000000; margin-right:15px;'></i>
                        <input class="register-style" type="text" id="username" name="username" placeholder="example123" value="$submittedUsername">
                    </div>
                </div>
                <div class="label-register-container">
                    <label for="" class="label-register">Password</label>
                    <div class="container-register">
                        <i class='fa-solid fa-lock fa-lg' style='color:#000000; margin-right:15px;'></i>
                        <input class="register-style" type="password" id="password" name="password" placeholder="**********">
                        <i class="fa-solid fa-eye-slash fa-lg togglePassword" data-target="password" style="cursor: pointer; margin-left: 10px; color: #000000;"></i>
                    </div>
                </div>
                <div class="label-register-container">
                    <label for="" class="label-register">Confirm Password</label>
                    <div class="container-register">
                        <i class='fa-solid fa-lock fa-lg' style='color:#000000; margin-right:15px;'></i>
                        <input class="register-style" type="password" id="confirmPassword" name="confirmPassword" placeholder="**********">
                        <i class="fa-solid fa-eye-slash fa-lg togglePassword" data-target="confirmPassword" style="cursor: pointer; margin-left: 10px; color: #000000;"></i>
                    </div>
                </div>
                <input class="register-button" type="submit" value="Register">
                <div class="login-link">
                    Already Have An Account? <span><a href="index.php?view=login">Login here</a></span>
                </div>
                <div class="login-link" style="margin-top: 10px;">
                    Need help? <span><a href="asset/UserManual_DroneFlightChecklist_(Website).pdf" download>User Manual</a></span>
                </div>
            </form>
        </div>

        <div class="register-box-right">
            <img class="register-image" src="asset/main-image.jpg" alt="">
            <div class="global-footer-note">Developed By BINUS University</div>
        </div>

    </div>
</body>
</html>
HTML;
    }

    public function getView(){
        $this->register();
        $this->generateView();
        echo $this->view;
    }

    private function validateForm($email, $username, $password, $confirmPassword){
        if ($email == '' || $username == '' || $password == '' || $confirmPassword == '') {
            return "Please fill all data";
        }
        else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format. Please use a valid email (e.g., name@example.com)";
        }
        else if ($password !== $confirmPassword){
            return "Password and confirm password is not match";
        }
        return 'success';
    }
    private function register(){
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = $_POST['email'];
            $username = $_POST['username'];
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirmPassword'];
            $verified = $this->validateForm($email, $username, $password, $confirmPassword);
            if($verified == 'success'){
                $passwordHash = hash("sha512", $password);  

                // Email Duplicate Check
                $emailUnique = $this->db->validateUniqueEmail($email);
                if($emailUnique !== 'unique'){
                    $this->error = $emailUnique;
                    return;
                }

                // Username Duplicate Check
                $isUnique = $this->db->validateUnique($username);
                if($isUnique == 'unique'){
                    $successRegist = $this->db->registUser($email, $username, $passwordHash);
                    if ($successRegist) {        
                        header("Location: index.php?view=login");
                        exit;
                    }
                }else{
                    $this->error = $isUnique;
                }
            }else{
                $this->error = $verified;
            }
        } 
    }
}
