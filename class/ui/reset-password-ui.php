<?php

class ResetPasswordUI {
    private $db = null;
    private $token = '';
    private $message = '';
    private $isError = false;
    private $success = false;

    public function __construct($db, $token) {
        $this->db = $db;
        $this->token = $token;
    }

    public function getView() {
        if (!$this->token) {
            header("Location: index.php?view=login");
            exit;
        }

        $this->handlePost();
        $this->generateView();
    }

    private function handlePost() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirmPassword'] ?? '';

            if (empty($password) || empty($confirmPassword)) {
                $this->message = "Please fill in all fields.";
                $this->isError = true;
                return;
            }

            if ($password !== $confirmPassword) {
                $this->message = "Passwords do not match.";
                $this->isError = true;
                return;
            }

            $result = $this->db->resetPasswordWithToken($this->token, $password);
            if ($result === "success") {
                $this->message = "Your password has been reset successfully. You can now login with your new password.";
                $this->isError = false;
                $this->success = true;
            } else {
                $this->message = $result;
                $this->isError = true;
            }
        }
    }

    private function generateView() {
        $messageHtml = "";
        if ($this->message) {
            $class = $this->isError ? "error-message" : "success-message";
            $style = $this->isError ? "display: block; color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px;" : "display: block; color: #27ae60; background: #d4efdf; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px;";
            $messageHtml = "<div class='$class' style='$style'>$this->message</div>";
        }

        $formHtml = "";
        if (!$this->success) {
            $formHtml = <<<HTML
            <form class="login-container" method="POST" id="resetForm" novalidate>
                <div class="login-statement">Reset Your Password</div>
                $messageHtml
                <div class="label-input-container">
                    <label class="label-input">New Password</label>
                    <div class="container-input">
                        <i class='fa-solid fa-lock fa-lg' style='color:#000000; margin-right:15px;'></i>
                        <input class="input-style" type="password" name="password" id="password" placeholder="**********">
                        <i class="fa-solid fa-eye-slash fa-lg togglePassword" style="cursor: pointer; margin-left: 10px; color: #000000;"></i>
                    </div>
                </div>
                <div class="label-input-container">
                    <label class="label-input">Confirm New Password</label>
                    <div class="container-input">
                        <i class='fa-solid fa-lock fa-lg' style='color:#000000; margin-right:15px;'></i>
                        <input class="input-style" type="password" name="confirmPassword" id="confirmPassword" placeholder="**********">
                        <i class="fa-solid fa-eye-slash fa-lg togglePassword" style="cursor: pointer; margin-left: 10px; color: #000000;"></i>
                    </div>
                </div>
                <div><input class="login-button" type="submit" value="Reset Password"></div>
            </form>
HTML;
        } else {
            $formHtml = <<<HTML
            <div class="login-container">
                <div class="login-statement">Success!</div>
                $messageHtml
                <div style="margin-top: 20px;">
                    <a href="index.php?view=login" class="login-button" style="display: block; text-align: center; text-decoration: none;">Go to Login</a>
                </div>
            </div>
HTML;
        }

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/reset-password-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="script/reset-password.js"></script>
    <title>Reset Password</title>
</head>
<body>
    <div class="container">
        <div class="login-box-left">
            <div class="login-title">Drone Flight<br>Checklist</div>
            $formHtml
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
}
