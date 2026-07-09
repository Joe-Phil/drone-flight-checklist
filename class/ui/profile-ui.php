<?php
ob_start();

class ProfileUI {

    private $db = null;
    private $view = "";

    public function __construct($db){
        $this->db = $db;

        $this->handlePost();

        $user = $_GET['user'];
        $username = base64_decode($user);

        $userData = $this->db->getUser($username);
        $email = $userData['email'] ?? 'N/A';

        $this->view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="styles/list-style.css">
            <link rel="stylesheet" href="styles/profile-style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script src="script/profile.js"></script>
            <title>Profile</title>
        </head>
        <body>
            <div class="container">
                <div id="sidebar-menu">
                    <div class="absolute">
HTML;
        $this->view .= "<div class='menu'><a href='index.php?view=dashboard&user=$user'>Dashboard</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=forms&user=$user&query=&delete='>Form Builder</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=templates&user=$user&query=&delete='>Checklist Template</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=submissions&user=$user&query='>Checklist Submission</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=mobile&user=$user'>Download Center</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=feedback&user=$user'>Feedback</a></div>";
        $this->view .= "</div>";
        $this->view .= "<div class='sidebar-footer'>";
        $this->view .= "<div class='menu active-menu'><a href='index.php?view=profile&user=$user'>Profile</a></div>";
        $this->view .= "<div class='menu'><a href='index.php'>Logout</a></div>";
        $this->view .= "<div class='global-footer-note'>Developed By BINUS University</div>";
        $this->view .= "</div>";
        $this->view .= <<<HTML
                </div>
                <div class="form-container">
                    <div class="top-bar">
                        <i class="fa-solid fa-bars fa-2x" id="hamburger-menu" style="display: none; cursor: pointer; margin-right: 20px;"></i>
                        <div class="form-title">Profile</div>
                    </div>
                    <div class="profile-content">
                        <div class="big-card">
                            <div class="profile-info">
                                <div class="info-group">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <label>Username</label>
                                        <div id="edit-username-btn" style="cursor: pointer; color: #0097da; display: flex; align-items: center; gap: 8px;" title="Edit Username">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span style="font-weight: 600; font-size: 14px;">Change Username</span>
                                        </div>
                                    </div>

                                    <div id="username-display" class="value">$username</div>

                                    <div id="username-edit-container" style="display: none;">
                                        <form method="POST" id="edit-username-form">
                                            <input type="text" name="new-username" id="new-username-input" class="value" value="$username" style="width: 100%; box-sizing: border-box; outline: none; border: 1px solid #0097da;">
                                            <div id="username-error" style="color: #ff0000; font-size: 12px; margin-top: 5px; display: none;">Username cannot be empty</div>
                                            <div style="margin-top: 10px; display: flex; gap: 10px; justify-content: flex-end;">
                                                <button type="button" id="cancel-edit-btn" class="cancel-btn">Cancel</button>
                                                <button type="submit" id="save-username-btn" class="save-btn">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="info-group">
                                    <label>Email Address</label>
                                    <div class="value">$email</div>
                                </div>
                                <div class="info-group">
                                    <label>Password</label>
                                    <button type="button" id="change-password-btn" class="save-btn" style="width: fit-content; margin-top: 5px;">
                                        Change Password
                                    </button>

                                    <div id="password-change-container" style="display: none; margin-top: 10px;">
                                        <form method="POST" id="change-password-form">
                                            <div class="password-input-group">
                                                <label style="font-size: 12px; color: #666;">Current Password</label>
                                                <input type="password" name="current-password" class="value" style="width: 100%; box-sizing: border-box; outline: none; border: 1px solid #0097da; margin-bottom: 10px;" required>
                                            </div>
                                            <div class="password-input-group">
                                                <label style="font-size: 12px; color: #666;">New Password</label>
                                                <input type="password" name="new-password" id="new-password" class="value" style="width: 100%; box-sizing: border-box; outline: none; border: 1px solid #0097da; margin-bottom: 10px;" required>
                                            </div>
                                            <div class="password-input-group">
                                                <label style="font-size: 12px; color: #666;">Confirm New Password</label>
                                                <input type="password" name="confirm-password" id="confirm-password" class="value" style="width: 100%; box-sizing: border-box; outline: none; border: 1px solid #0097da;" required>
                                            </div>
                                            <div id="password-error" style="color: #ff0000; font-size: 12px; margin-top: 5px; display: none;">Passwords do not match</div>
                                            <div style="margin-top: 10px; display: flex; gap: 10px; justify-content: flex-end;">
                                                <button type="button" id="cancel-password-btn" class="cancel-btn">Cancel</button>
                                                <button type="submit" name="change-password" class="save-btn">Update Password</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
HTML;
    }

    public function getView(){
        echo $this->view;
    }

    private function handlePost(){
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['new-username'])) {
                $this->handleUsernameChange();
            } elseif (isset($_POST['change-password'])) {
                $this->handlePasswordChange();
            }
        }
    }

    private function handleUsernameChange() {
        $oldUsername = base64_decode($_GET['user']);
        $newUsername = trim($_POST['new-username']);

        if (empty($newUsername)) {
            return;
        }

        if ($oldUsername !== $newUsername) {
            $result = $this->db->updateUsername($oldUsername, $newUsername);
            if ($result === "success") {
                $newEncodedUser = base64_encode($newUsername);
                header("Location: index.php?view=profile&user=$newEncodedUser");
                exit();
            } else {
                echo "<script>alert('$result');</script>";
            }
        } else {
            header("Location: index.php?view=profile&user=" . $_GET['user']);
            exit();
        }
    }

    private function handlePasswordChange() {
        $username = base64_decode($_GET['user']);
        $currentPassword = $_POST['current-password'];
        $newPassword = $_POST['new-password'];
        $confirmPassword = $_POST['confirm-password'];

        if ($newPassword !== $confirmPassword) {
            echo "<script>alert('New passwords do not match');</script>";
            return;
        }

        $result = $this->db->updatePassword($username, $currentPassword, $newPassword);
        if ($result === "success") {
            echo "<script>alert('Password updated successfully');</script>";
            header("Location: index.php?view=profile&user=" . $_GET['user']);
            exit();
        } else {
            echo "<script>alert('$result');</script>";
        }
    }
}
