<?php

class FeedbackUI {

    private $db = null;
    private $view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="styles/dashboard-style.css">
            <link rel="stylesheet" href="styles/feedback-style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script src="script/feedback.js"></script>
            <title>Feedback</title>
        </head>
        <body>
    HTML;

    public function __construct($db) {
        $this->db = $db;
        $user = $_GET['user'];
        $this->view .= "<input type='text' id='user' name='user' style='display: none;' value='$user'/>";
        $this->view .= <<<HTML
            <div class="container">
                <div id="sidebar-menu">
                    <div class="absolute">
        HTML;
        $this->view .= "<div class='menu'><a href='index.php?view=dashboard&user=$user'>Dashboard</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=forms&user=$user&query=&delete='>Form Builder</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=templates&user=$user&query=&delete='>Checklist Template</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=submissions&user=$user&query='>Checklist Submission</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=mobile&user=$user'>Download Center</a></div>";
        $this->view .= "<div class='menu active-menu'><a href='index.php?view=feedback&user=$user'>Feedback</a></div>";
        $this->view .= "</div>";
        $this->view .= "<div class='sidebar-footer'>";
        $this->view .= "<div class='menu'><a href='index.php?view=profile&user=$user'>Profile</a></div>";
        $this->view .= "<div class='menu'><a href='index.php'>Logout</a></div>";
        $this->view .= "<div class='global-footer-note'>Developed By BINUS University</div>";
        $this->view .= "</div>";
        $this->view .= <<<HTML
                </div>
                <div class="dashboard-container">
                    <div class="top-bar">
                        <i class="fa-solid fa-bars fa-2x" id="hamburger-menu" style="display: none; cursor: pointer; margin-right: 20px;"></i>
                        <div class="dashboard-title">Feedback</div>
                    </div>
                    <div class="feedback-grid">
                        <div class="feedback-card">
                            <div class="card-header">
                                <i class="fa-solid fa-globe fa-4x"></i>
                                <h2>Website Feedback</h2>
                            </div>
                            <p class="card-description">Please share your thoughts and suggestions about your experience using our website.</p>
                            <a href="https://forms.gle/UWgY5kiWPCaVDUeeA" target="_blank" class="feedback-button">Give Feedback</a>
                        </div>
                        <div class="feedback-card">
                            <div class="card-header">
                                <i class="fa-solid fa-mobile-screen-button fa-4x"></i>
                                <h2>Mobile Feedback</h2>
                            </div>
                            <p class="card-description">Tell us how we can improve our mobile application to better serve your needs.</p>
                            <a href="https://forms.gle/RuWf8wEz4Vnt4dQU6" target="_blank" class="feedback-button">Give Feedback</a>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    public function getView() {
        echo $this->view;
    }
}
