<?php

class SubmissionsUI{

    private $db = null;
    private $view = "";

    public function __construct($db){
        $this->db = $db;
        $user = $_GET['user'];
        $query = $_GET['query'] ?? "";
        $sort = $_GET['sort'] ?? 'submittedDate';
        $order = $_GET['order'] ?? 'DESC';

        $this->view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="styles/list-style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script src="script/submissions.js"></script>
            <title>Checklist Submission</title>
            <style>
                th.sortable { cursor: pointer; }
                th.sortable:hover { background-color: #727272; color: #fff; }
                .sort-icon { margin-left: 5px; font-size: 0.8em; }
            </style>
        </head>
        <body>
            <div id="modal" style="display: none;">
                <div class="delete-container">
                    <div class="delete-confirmation">
                        <div class="delete-title">
                            <h2>Confirmation</h2>
                            <i class='fa-solid fa-circle-exclamation fa-5x' style='color:#ffc107; margin: 20px 0 10px 0;'></i>
                        </div>
                        <div class="delete-content">
                            Are you sure want to delete this submission?
                            <form method="POST" name="delete-submission-form">
                                <input type="hidden" name="submissionId" id="submission-id">
HTML;

        $this->view .= "<input type='text' id='user' name='user' value='$user' style='display: none;'/>";
        $this->view .= "<div class='confirm-button'><div class='no-button' id='cancel-delete'>No</div>";
        $this->view .= <<<HTML
                                    <button class="yes-button" type="submit">Yes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div id="sidebar-menu">
                    <div class="absolute">
HTML;
        $this->view .= "<div class='menu'><a href='index.php?view=dashboard&user=$user'>Dashboard</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=forms&user=$user&query=&delete='>Form Builder</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=templates&user=$user&query=&delete='>Checklist Template</a></div>";
        $this->view .= "<div class='menu active-menu'><a href='index.php?view=submissions&user=$user&query='>Checklist Submission</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=mobile&user=$user'>Download Center</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=feedback&user=$user'>Feedback</a></div>";
        $this->view .= "</div>";
        $this->view .= "<div class='sidebar-footer'>";
        $this->view .= "<div class='menu'><a href='index.php?view=profile&user=$user'>Profile</a></div>";
        $this->view .= "<div class='menu'><a href='index.php'>Logout</a></div>";
        $this->view .= "<div class='global-footer-note'>Developed By BINUS University</div>";
        $this->view .= "</div>";
        $this->view .= <<<HTML
            </div>
            <div class="submission-container">
                <div>
                    <div class="top-bar">
                        <i class="fa-solid fa-bars fa-2x" id="hamburger-menu" style="display: none; cursor: pointer; margin-right: 20px;"></i>
                        <div class="submission-title">Checklist Submission</div>
                    </div>
                    <div style="display: flex">
                        <form method="POST" class="search-container">
                            <input class="search-input" type="text" placeholder="Search..." name="query" value="$query">
                            <button id="search" type="submit"><i class='fa-solid fa-magnifying-glass fa-sm' style='color:#ffffff;margin-right:15px;'></i>Search</button>
                        </form>
                    </div>

                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th class="col-1">No</th>
HTML;

        // Helper to generate sortable headers
        $columns = [
            'submissionName' => 'Name',
            'submittedBy' => 'Submitted By',
            'submittedDate' => 'Submitted Date'
        ];

        $colMappings = [
            'submissionName' => 'col-2',
            'submittedBy' => 'col-4',
            'submittedDate' => 'col-5'
        ];

        foreach ($columns as $key => $label) {
            $nextOrder = ($sort === $key && $order === 'ASC') ? 'DESC' : 'ASC';
            $icon = "";
            if ($sort === $key) {
                $icon = $order === 'ASC' ? " <i class='fa-solid fa-sort-up sort-icon'></i>" : " <i class='fa-solid fa-sort-down sort-icon'></i>";
            } else {
                $icon = " <i class='fa-solid fa-sort sort-icon' style='color: #444;'></i>";
            }

            $colClass = $colMappings[$key];
            $url = "index.php?view=submissions&user=$user&query=$query&sort=$key&order=$nextOrder";
            $this->view .= "<th class='$colClass sortable' onclick=\"window.location.href='$url'\">$label$icon</th>";
        }

        $this->view .= <<<HTML
                                    <th class="col-6">Action</th>
                                </tr>
                            </thead>
                            <tbody>
HTML;
        $this->getAllData();
    }

    private function getAllData(){
        $query = $_GET['query'] ?? "";
        $sort = $_GET['sort'] ?? 'submittedDate';
        $order = $_GET['order'] ?? 'DESC';

        $submissionList = $this->db->fetchAllSubmission($query, $sort, $order);
        $this->view .= $submissionList;
    }

    public function getView(){
        $this->view .= <<<HTML
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </body>
        </html>
HTML;
        echo $this->view;
        $this->handleSubmit();
    }

    private function handleSubmit(){
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if(isset($_POST['submissionId'])){
                $this->deleteConfirm();
            } else if(isset($_POST['query'])) {
                $user = $_GET['user'];
                $query = $_POST['query'];
                // Preserve sort/order when searching
                $sort = $_GET['sort'] ?? 'submittedDate';
                $order = $_GET['order'] ?? 'DESC';
                header("Location: index.php?view=submissions&user=$user&query=$query&sort=$sort&order=$order");
            }
        }
    }

    private function deleteConfirm(){
        $submissionId = $_POST['submissionId'];
        $user = $_GET['user'];
        if ($submissionId != "") {
            $sql = "DELETE FROM submission WHERE id = $submissionId";
            if ($this->db->getConnection()->query($sql) === TRUE) {
                header("Location: index.php?view=submissions&user=$user&query=");
            } else {
                echo "<script>alert('Error deleting record: " . $this->db->getConnection()->error . "');</script>";
            }
        }
    }

}
