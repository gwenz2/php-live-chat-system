<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <title>Gwez - Login you account</title>
    <style>
        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background: linear-gradient(135deg, #e3f0ff 0%, #f9f9f9 100%);
        }
        .card {
            border-radius: 1.2rem;
            box-shadow: 0 6px 24px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.06);
            border: none;
        }
        .card-title {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 0.3rem;
        }
        .form-control {
            border-radius: 0.7rem;
            padding: 0.7rem 1rem;
            border: 1px solid #cfd8dc;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.15);
        }
        .btn-primary {
            border-radius: 0.7rem;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.08);
        }
        .btn-primary:hover {
            background: #1565c0;
        }
        .card-body {
            padding: 2.5rem 2rem;
        }
        .mt-3 {
            margin-top: 1.2rem !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center" style="height:100vh">
            <div class="col-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="card-title ">Create your account</h1>
                        <form action="" autocomplete="off">
                            <div class="form-group">
                                <label for="display_name">Display Name</label>
                                <input id="display_name" class="form-control" name="display_name">
                            </div>
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input id="username" class="form-control" name="username">
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" name="password">
                            </div>
                            <button type="button" id="sendcreate" class="btn btn-primary mt-3">Create Account</button>
                            <p class="mt-3">Already have an account? <a href="index.php">Login</a></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>