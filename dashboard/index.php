<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <title>Gwez - Live-Chat</title>
</head>
<body class="p-3">
    <?php include_once 'navbar.php';?>
    <div class="container mt-5">
        <div class="row">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-center">CONTACTS</h5>
                        <div class="list-group">
                            <div class="col list-group-item">
                                <a href="chatroom.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <img src="../assets/user_male_96px.png" width="50" height="50"> 
                                    <div class="ms-2">
                                        <h6 class="d-inline-block ms-2">Ryan Christian Alimpuyo</h6>
                                        <span class="badge bg-success">O</span>
                                        <p>Last message</p>
                                    </div>
                                </a>
                            </div>

                        <div class="list-group">
                            <div class="col list-group-item">
                                <a href="chatroom.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <img src="../assets/user_male_80px.png" width="50" height="50"> 
                                    <div class="ms-2">
                                        <h6 class="d-inline-block ms-2">Jershon Alcarde</h6>
                                        <span class="badge bg-danger">O</span>
                                        <p>Last message</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>