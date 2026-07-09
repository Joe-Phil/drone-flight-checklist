<?php
ob_start();

class DashboardUI{

    private $db = null;
    private $view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="styles/dashboard-style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script src="script/dashboard.js"></script>
            <title>Dashboard</title>
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
        $this->view .= "<div class='menu active-menu'><a href='index.php?view=dashboard&user=$user'>Dashboard</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=forms&user=$user&query=&delete='>Form Builder</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=templates&user=$user&query=&delete='>Checklist Template</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=submissions&user=$user&query='>Checklist Submission</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=mobile&user=$user'>Download Center</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=feedback&user=$user'>Feedback</a></div>";
        $this->view .= "</div>"; // tutup .absolute
        $this->view .= "<div class='sidebar-footer'>";
        $this->view .= "<div class='menu'><a href='index.php?view=profile&user=$user'>Profile</a></div>";
        $this->view .= "<div class='menu'><a href='index.php'>Logout</a></div>";
        $this->view .= "<div class='global-footer-note'>Developed By BINUS University</div>";
        $this->view .= "</div>";
        $this->view .= <<<HTML
                </div> <!-- end #sidebar-menu -->
                <div class="dashboard-container">
                    <div class="top-bar">
                        <i class="fa-solid fa-bars fa-2x" id="hamburger-menu" style="display: none; cursor: pointer; margin-right: 20px;"></i>
                        <div class="dashboard-title">Dashboard</div>
        HTML;
        $countRecent = $this->db->getCountRecentData();
        $countRecent = json_decode($countRecent);
        $forms = $countRecent->forms->count;
        $templates = $countRecent->templates->count;
        $submissions = $countRecent->submissions->count;

        $this->view .= <<<HTML
                    </div>
                    <div class="all-card-container">
                        <div class="card">
                            <div class="card-data">
                                $forms
                            </div>
                            <div class="card-title">
                                <div>FORM<br>DATA</div>
                            </div>
                            <div class="card-icon"><i class='fa-solid fa-chart-simple fa-3x' style='color:#0097da'></i></div>   
                        </div>
                        <div class="card mid">
                            <div class="card-data">
                                $templates
                            </div>
                            <div class="card-title">
                                <div>TEMPLATE<br>DATA</div>
                            </div>
                            <div class="card-icon"><i class='fa-solid fa-chart-simple fa-3x' style='color:#0097da'></i></div> 
                        </div>
                        <div class="card">
                            <div class="card-data">
                                $submissions
                            </div>
                            <div class="card-title">
                                <div>SUBMISSION<br>DATA</div>
                            </div>
                            <div class="card-icon"><i class='fa-solid fa-chart-simple fa-3x' style='color:#0097da'></i></div> 
                        </div>
                    </div>
                    <div class="recent-container">
                        <div class="recent">
                            <div class="recent-title"><i class='fa-solid fa-arrows-rotate' style='color:#000000;'></i>Recent Form (by updated date)</div>
                            <div class="table-container">
        HTML;

        $formRecent = $countRecent->forms->recent;
        $templateRecent = $countRecent->templates->recent;
        $submissionRecent = $countRecent->submissions->recent;

        $formLength = count($formRecent);
        for($i = 0; $i < $formLength; $i++){
            $no = $i + 1;
            $currRecent = $formRecent[$i];
            $oddEven = $no % 2 == 0 ? "even" : "odd";

            $this->view .= "<div class='table-data $oddEven'>";
            $this->view .= <<<HTML
                                    <div class="col-1">$no</div>
                                    <div class="col-2">$currRecent->formName</div>
                                    <div class="col-3">$currRecent->formType</div>
                                    <div class="col-4">$currRecent->updatedBy</div>
                                    <div class="col-5">$currRecent->updatedDate</div>
                                </div>
            HTML;
        }

        $this->view .= <<<HTML
                            </div>
                        </div>
                        <div class="recent">
                            <div class="recent-title"><i class='fa-solid fa-arrows-rotate' style='color:#000000;'></i>Recent Template (by updated date)</div>
                            <div class="table-container">
        HTML;

        $templateLength = count($templateRecent);
        for($i = 0; $i < $templateLength; $i++){
            $no = $i + 1;
            $currRecent = $templateRecent[$i];
            $oddEven = $no % 2 == 0 ? "even" : "odd";
            $this->view .= "<div class='table-data $oddEven'>";
            $this->view .= <<<HTML
                                    <div class="col-1">$no</div>
                                    <div class="col-2">$currRecent->templateName</div>
                                    <div class="col-4">$currRecent->updatedBy</div>
                                    <div class="col-5">$currRecent->updatedDate</div>
                                </div>
            HTML;
        }

        $this->view .= <<<HTML
                            </div>
                        </div>
                        <div class="recent">
                            <div class="recent-title"><i class='fa-solid fa-arrows-rotate' style='color:#000000;'></i>Recent Submission (by updated date)</div>
                            <div class="table-container">
        HTML;

        $submissionLength = count($submissionRecent);
        for($i = 0; $i < $submissionLength; $i++){
            $no = $i + 1;
            $currRecent = $submissionRecent[$i];
            $oddEven = $no % 2 == 0 ? "even" : "odd";
            $this->view .= "<div class='table-data $oddEven'>";
            $this->view .= <<<HTML
                                    <div class="col-1">$no</div>
                                    <div class="col-2">$currRecent->submissionName</div>
                                    <div class="col-4">$currRecent->submittedBy</div>
                                    <div class="col-5">$currRecent->submittedDate</div>
                                </div>
            HTML;
        }

        $this->view .= <<<HTML
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