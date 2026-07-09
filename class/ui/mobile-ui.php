<?php

class MobileUI{

    private $db = null;
    private $view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="styles/dashboard-style.css">
            <link rel="stylesheet" href="styles/mobile-style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script src="script/mobile.js"></script>
            <title>Download Center</title>
        </head>
        <body>

    HTML;

    public function __construct($db){
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
        $this->view .= "<div class='menu active-menu'><a href='index.php?view=mobile&user=$user'>Download Center</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=feedback&user=$user'>Feedback</a></div>";
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
                        <div class="dashboard-title">Download Center</div>
                    </div>
                    <div class="content-container">
                        <div class="qr-section">
                            <img src="asset/QR_Mobile_APK.png" alt="Scan to Download APK" class="qr-image">
                            <div class="qr-text">Scan to Download Mobile App</div>
                        </div>

                        <div class="download-cards-container">
                            <!-- APK Download Card -->
                            <div class="download-card">
                                <div class="download-card-info">
                                    <i class="fa-brands fa-android"></i>
                                    <div class="download-card-text">Download Mobile APK</div>
                                </div>
                                <a href="asset/DroneFlightChecklist.apk" class="download-link" download>Download</a>
                            </div>

                            <!-- Mobile User Manual Card -->
                            <div class="download-card">
                                <div class="download-card-info">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    <div class="download-card-text">Mobile User Manual</div>
                                </div>
                                <a href="asset/UserManual_DroneFlightChecklist_(Mobile).pdf" class="download-link" download>Download</a>
                            </div>

                            <!-- Website User Manual Card -->
                            <div class="download-card">
                                <div class="download-card-info">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    <div class="download-card-text">Website User Manual</div>
                                </div>
                                <a href="asset/UserManual_DroneFlightChecklist_(Website).pdf" class="download-link" download>Download</a>
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

}
