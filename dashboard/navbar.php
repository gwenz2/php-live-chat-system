<style>
    .navbar {
        border-radius: 1rem;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08), 0 1.5px 4px rgba(0,0,0,0.04);
        font-family: 'Segoe UI', 'Arial', sans-serif;
        padding: 0.5rem 1rem;
    }
    .navbar-brand {
        font-weight: 700;
        letter-spacing: 1px;
        font-size: 1.4rem;
    }
    .navbar-nav .nav-link {
        margin-left: 0.7rem;
        margin-right: 0.7rem;
        border-radius: 0.5rem;
        transition: background 0.2s, color 0.2s;
    }
    .navbar-nav .nav-link.active, .navbar-nav .nav-link:hover {
        background: rgba(255,255,255,0.15);
        color: #fff !important;
    }
    .navbar-toggler {
        border-radius: 0.5rem;
        border: none;
    }
</style>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary border-radius mb-3">
        <div class="container">
            <a class="navbar-brand" href="#">Gwez - Live Chat</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Home</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="#">Manage Contacts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Setting</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>