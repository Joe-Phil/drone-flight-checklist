<?php

class ForgotPasswordUI {
    private $db = null;
    private $message = '';
    private $isError = false;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getView() {
        $this->handlePost();
        $this->generateView();
    }

    private function handlePost() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                $this->message = "Please enter your email address.";
                $this->isError = true;
                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->message = "Invalid email format. Please use a valid email (e.g., name@example.com)";
                $this->isError = true;
                return;
            }

            $token = $this->db->createPasswordResetToken($email);
            if ($token === "Email not found") {
                $this->message = "No account found with that email address.";
                $this->isError = true;
            } else if ($token) {
                $resetLink = "index.php?view=resetPassword&token=" . $token;
                $this->message = "A reset link has been generated. <br><br><a href='$resetLink'>Click here to reset your password</a>";
                $this->isError = false;
            } else {
                $this->message = "An error occurred. Please try again later.";
                $this->isError = true;
            }
        }
    }

    private function generateView() {
        $messageHtml = "";
        if ($this->message) {
            $class = $this->isError ? "error-message" : "success-message";
            $style = $this->isError ? "display: block; color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin-bottom: 15px;" : "display: block; color: #27ae60; background: #d4efdf; padding: 10px; border-radius: 5px; margin-bottom: 15px;";
            $messageHtml = "<div class='$class' style='$style'>$this->message</div>";
        }

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/forgot-password-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="script/forgot-password.js"></script>
    <title>Forgot Password</title>
</head>
<body>
    <div class="container">
        <div class="login-box-left">
            <div class="login-title">Drone Flight<br>Checklist</div>
            <form class="login-container" method="POST" novalidate>
                <div class="login-statement">Forgot Your Password?</div>
                <p style="margin-bottom: 20px; color: #666;">Enter your email address and we'll provide a link to reset your password.</p>
                $messageHtml
                <div class="label-input-container">
                    <label class="label-input">Email Address</label>
                    <div class="container-input">
                        <i class='fa-solid fa-envelope fa-lg' style='color:#000000; margin-right:15px;'></i>
                        <input class="input-style" type="email" name="email" placeholder="name@email.com">
                    </div>
                </div>
                <div><input class="login-button" type="submit" value="Generate Reset Link"></div>
            </form>
            <div class="register-link">
                Remembered your password? <span><a href="index.php?view=login">Back to Login</a></span>
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
}
