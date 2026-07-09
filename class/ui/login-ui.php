<?php

date_default_timezone_set('Asia/Jakarta');
class LoginUI{

    private $db = null;
    private $error = '';
    private $view = '';

    public function __construct($db){
        $this->db = $db;
    }

    private function generateView(){
        $messageHtml = "";
        $submittedUsername = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : "";

        if ($this->error) {
            $style = "display: block; color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px;";
            $messageHtml = "<div class='error-message' style='$style'>$this->error</div>";
        }

        $this->view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="styles/login-style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script src="script/login.js"></script>
            <title>Login Page</title>
        </head>
        <body>
            <div class="container">
                <div class="login-box-left">
                    <div class="login-title">Drone Flight<br>Checklist</div>
                    <form id="loginForm" class="login-container" method="POST" novalidate>
                        <div class="login-statement">Login to Your Account</div>
                        $messageHtml
                        <div class="label-input-container">
                            <label class="label-input">Username or Email</label>
                            <div class="container-input">
                                <i class='fa-solid fa-user fa-lg' style='color:#000000; margin-right:15px;'></i>
                                <input class="input-style" type="text" id="username" name="username" placeholder="example123 or name@email.com" value="$submittedUsername">
                            </div>
                        </div>
                        <div class="label-input-container">
                            <label class="label-input">Password</label>
                            <div class="container-input">
                                <i class='fa-solid fa-lock fa-lg' style='color:#000000; margin-right:15px;'></i>
                                <input class="input-style" type="password" id="password" name="password" placeholder="**********">
                                <i class="fa-solid fa-eye-slash fa-lg" id="togglePassword" style="cursor: pointer; margin-left: 10px; color: #000000;"></i>
                            </div>
                            <div class="forgot-password-link">
                                <a href="index.php?view=forgotPassword">Forgot Password?</a>
                            </div>
                        </div>
                        <input class="login-button" type="submit" value="Log In">
                    </form>
                    <div class="register-link">
                        Don't have account? <span><a href="index.php?view=register">Register here</a></span> 
                    </div>
                    <div class="register-link" style="margin-top: 10px;">
                        Need help? <span><a href="asset/UserManual_DroneFlightChecklist_(Website).pdf" download>User Manual</a></span>
                    </div>
                </div>
                <div class="login-box-right">
                    <img class="login-image" src="asset/main-image.jpg" alt="">
                    <div class="global-footer-note">Developed By BINUS University</div>
                </div>
            </div>
        </body>
        </html>
HTML;
    }

    public function getView(){
        $this->login();
        $this->generateView();
        echo $this->view;
    }

    private function validateForm($username, $password){
        if ($username == '' || $password == '') {
            return 'Please fill all data';
        }
        return 'success';
    }
    private function login(){
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $input = $_POST['username'];
            $password = $_POST['password'];
            $verified = $this->validateForm($input, $password);

            if($verified == 'success'){
                $validated = $this->db->validateLogin($input, $password);

                // If login is successful, $validated contains the actual username
                if($validated !== "incorrect password" && $validated !== "username or email does not exist"){
                    $userEncode = base64_encode($validated);
                    header("Location: index.php?view=dashboard&user=$userEncode&query=&delete=");
                    exit;
                }else{
                    $this->error = ucfirst($validated);
                }
            }else{
                $this->error = $verified;
            }
        }
    }

}
