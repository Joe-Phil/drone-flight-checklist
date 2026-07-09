<?php
ob_start();

class FormsUI{

    private $db = null;
    private $view = "";

    public function __construct($db){
        $this->db = $db;
        $user = $_GET['user'];
        $currentUser = base64_decode($user);

        // Check if user owns any forms
        $hasOwnedItems = false;
        $query = $_GET['query'] ?? "";
        $sort = $_GET['sort'] ?? 'updatedDate';
        $order = $_GET['order'] ?? 'DESC';

        $formList = $this->db->fetchAllForms($query, $sort, $order);
        if ($formList && strpos($formList, 'edit-') !== false) {  // Check if any edit buttons exist
            $hasOwnedItems = true;
        }
        
        $this->view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="styles/list-style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script src="script/forms.js"></script>
            <title>Form Builder</title>
            <style>
                th.sortable { cursor: pointer; }
                th.sortable:hover { background-color: #727272; color: #fff; }
                .sort-icon { margin-left: 5px; font-size: 0.8em; }
            </style>
        </head>
        <body>
            <!-- Delete Modal Blocked
            <div id="modal" style="display: none;">
                <div class="delete-container">
                    <div class="delete-confirmation">
                        <div class="delete-title">
                            <h2>Confirmation</h2>
                            <i class='fa-solid fa-circle-exclamation fa-5x' style='color:#ffc107; margin: 20px 0 10px 0;'></i>
                        </div>
                        <div class="delete-content">
                            Are you sure want to delete this form?
                            <form method="POST" name="delete-form">
                                <input type="text" name="formId" id="form-id" style="display: none;">
            -->
HTML;

        $this->view .= "<input type='text' id='user' name='user' style='display: none;' value='$user'/>";

        /*
        $this->view .= "<div class='confirm-button'><a href='index.php?view=forms&user=$user&query=&delete='><div class='no-button'>No</div></a>";
        $this->view .= <<<HTML
                                    <button class="yes-button">Yes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
HTML;
        */

        $this->view .= <<<HTML
            <div class="container">
                <div id="sidebar-menu">
                    <div class="absolute">
HTML;
        $this->view .= "<div class='menu'><a href='index.php?view=dashboard&user=$user'>Dashboard</a></div>";
        $this->view .= "<div class='menu active-menu'><a href='index.php?view=forms&user=$user&query=&delete='>Form Builder</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=templates&user=$user&query=&delete='>Checklist Template</a></div>";
        $this->view .= "<div class='menu'><a href='index.php?view=submissions&user=$user&query='>Checklist Submission</a></div>";
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
            <div class="form-container">
HTML;
        $isDelete = $_GET['delete'] ?? null;
        if($isDelete){
            $this->view .= "<div id='delete-alert'>Delete Successful!</div>";
        }
        $this->view .= <<<HTML
                <div class="top-bar">
                    <i class="fa-solid fa-bars fa-2x" id="hamburger-menu" style="display: none; cursor: pointer; margin-right: 20px;"></i>
                    <div class="form-title">Form Builder</div>
HTML;
        $this->view .= "<div class='button-container'><a href='index.php?view=viewForms&user=$user&id=0'><div class='create-form-button'><i class='fa-solid fa-plus fa-lg' style='color:#ffffff;margin-right:15px'></i>Create New Form</div></a></div></div>";

        $currentQuery = $_GET['query'] ?? '';
        $this->view .= <<<HTML
            <div style="display: flex">
                <form method="POST" name="search-form" class="search-container">
                    <input class="search-input" type="text" placeholder="Search..." name="query" value="$currentQuery">
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
            'formName' => 'Name',
            'formType' => 'Type',
            'owner' => 'Owner',
            'updatedDate' => 'Updated Date'
        ];

        $colNo = 2;
        foreach ($columns as $key => $label) {
            $nextOrder = ($sort === $key && $order === 'ASC') ? 'DESC' : 'ASC';
            $icon = "";
            if ($sort === $key) {
                $icon = $order === 'ASC' ? " <i class='fa-solid fa-sort-up sort-icon'></i>" : " <i class='fa-solid fa-sort-down sort-icon'></i>";
            } else {
                $icon = " <i class='fa-solid fa-sort sort-icon' style='color: #444;'></i>";
            }

            $url = "index.php?view=forms&user=$user&query=$currentQuery&sort=$key&order=$nextOrder";
            $this->view .= "<th class='col-$colNo sortable' onclick=\"window.location.href='$url'\">$label$icon</th>";
            $colNo++;
        }

        $this->view .= <<<HTML
                            <!-- <th class="col-6">Action</th> -->
                        </tr>
                    </thead>
                    <tbody>
HTML;
                
        $this->getAllData();
    }

    private function getAllData(){
        $query = $_GET['query'] ?? "";
        $sort = $_GET['sort'] ?? 'updatedDate';
        $order = $_GET['order'] ?? 'DESC';

        $formList = $this->db->fetchAllForms($query, $sort, $order);
        $this->view .= $formList;
    }

    public function getView(){
        $this->view .= <<<HTML
                        </tbody>
                    </table>
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
           /* Blocked formId check for delete popup
           if(isset($_POST['formId'])) {
              $formId = $_POST['formId'];
              if($formId != ""){
                  $this->deleteConfirm();
              }
           } else */
           if(isset($_POST['query'])) {
              $query = $_POST['query'];
              $this->reconstructData($query);
           }
        }
    }

    private function deleteConfirm(){
        $formId = $_POST['formId'];
        $user = $_GET['user'];
        if ($formId != 0) {
            $deleteSuccess = $this->db->deleteForm($formId);
            if ($deleteSuccess) {
                header("Location: index.php?view=forms&user=$user&query=&delete=1");
            }
            else {
                echo "<script>alert('Something Wrong');</script>";
            }
        }
    }

    private function reconstructData($query){
        $user = $_GET['user'];
        // Preserve sort/order when searching
        $sort = $_GET['sort'] ?? 'updatedDate';
        $order = $_GET['order'] ?? 'DESC';
        header("Location: index.php?view=forms&user=$user&query=$query&sort=$sort&order=$order&delete=");
    }

}
