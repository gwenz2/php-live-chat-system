<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="icon" href="iconMO.svg" type="image/svg+xml">
    <title>OneTalk - Create Your Account</title>
    <style>
        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background: linear-gradient(135deg, #e3f0ff 0%, #f9f9f9 100%);
        }

        .card {
            border-radius: 1.2rem;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.10), 0 2px 8px rgba(0, 0, 0, 0.06);
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

        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem 1rem;
            }

            .card-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height:100vh">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6">
                <div class="card shadow-sm mx-auto" style="max-width: 450px;">
                    <div class="card-body">
                        <h1 class="card-title">Welcome to OneTalk</h1>
                        <?php if (isset($_GET['msg'])): ?>
                            <div class="alert alert-info text-center mt-3"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($alert) && $alert): ?>
                            <div class="alert alert-danger text-center mt-3"><?php echo htmlspecialchars($alert); ?></div>
                        <?php endif; ?>
                        <form action="register.php" method="POST" autocomplete="off">
                            <div class="form-group">
                                <label for="display_name">Display Name</label>
                                <input id="display_name" class="form-control" name="display_name" required>
                            </div>
                            <div class="form-group mt-3">
                                <label for="username">Username</label>
                                <input id="username" class="form-control" name="username" required pattern="[a-z0-9]+" title="Lowercase letters and numbers only, no spaces or symbols">
                            </div>
                            <div class="form-group mt-3">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" name="password" required pattern=".{8,}" title="At least 8 characters">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-3">Create Account</button>
                            <p class="mt-3 text-center">Already have an account? <a href="index.php">Login</a></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>
