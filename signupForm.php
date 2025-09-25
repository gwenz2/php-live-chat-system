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
                        
                        <div id="signupContainer">
                            <div id="signupAlert" class="alert alert-danger text-center mt-3" style="display: none;"></div>
                            
                            <div class="text-center mb-4">
                                <p class="text-muted">Create your account with Google to get started</p>
                            </div>
                            
                            <!-- Google Sign-In Button -->
                            <button type="button" id="googleSignUpBtn" class="btn btn-primary w-100 mt-3 d-flex align-items-center justify-content-center" style="background: #4285f4; border-color: #4285f4;">
                                <svg class="me-2" width="20" height="20" viewBox="0 0 24 24">
                                    <path fill="white" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="white" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="white" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="white" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <span id="googleSignUpText">Sign up with Google</span>
                                <span id="googleSignUpSpinner" class="spinner-border spinner-border-sm ms-2" role="status" style="display: none;"></span>
                            </button>
                            
                            <p class="mt-3 text-center">Already have an account? <a href="index.php">Sign in</a></p>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    By signing up, you agree to our Terms of Service and Privacy Policy
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js";
        import { getAuth, signInWithPopup, GoogleAuthProvider, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js";
        import { getDatabase, ref, get, set, serverTimestamp } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js";

        const firebaseConfig = {
            apiKey: "AIzaSyDXixUNrcWNE1telIVZ_0L5KGQWLrElIEE",
            authDomain: "onetalk-116de.firebaseapp.com",
            databaseURL: "https://onetalk-116de-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "onetalk-116de",
            storageBucket: "onetalk-116de.firebasestorage.app",
            messagingSenderId: "175655177771",
            appId: "1:175655177771:web:a95b4032228b4209eca46e",
            measurementId: "G-B87YLF9WW4"
        };

        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const database = getDatabase(app);
        const provider = new GoogleAuthProvider();

        // Configure Google provider
        provider.addScope('profile');
        provider.addScope('email');

        // Check if user is already logged in
        onAuthStateChanged(auth, (user) => {
            if (user) {
                // User is signed in, redirect to dashboard
                window.location.href = 'dashboard/index.php';
            }
        });

        // Helper function to generate username from email
        function generateUsername(email, displayName) {
            // Try to use part of display name first, then email
            let username = '';
            if (displayName) {
                username = displayName.toLowerCase().replace(/[^a-z0-9]/g, '');
            }
            if (!username || username.length < 3) {
                username = email.split('@')[0].toLowerCase().replace(/[^a-z0-9]/g, '');
            }
            // Ensure minimum length and add random number if needed
            if (username.length < 3) {
                username += Math.floor(Math.random() * 1000);
            }
            return username.substring(0, 20); // Limit length
        }

        // Handle Google Sign-Up
        document.getElementById('googleSignUpBtn').addEventListener('click', async () => {
            const button = document.getElementById('googleSignUpBtn');
            const buttonText = document.getElementById('googleSignUpText');
            const spinner = document.getElementById('googleSignUpSpinner');
            const signupAlert = document.getElementById('signupAlert');

            // Show loading state
            button.disabled = true;
            buttonText.textContent = 'Creating account...';
            spinner.style.display = 'inline-block';
            signupAlert.style.display = 'none';

            try {
                // Sign in with Google
                const result = await signInWithPopup(auth, provider);
                const user = result.user;

                // Check if user profile exists in database
                const userRef = ref(database, `users/${user.uid}`);
                const snapshot = await get(userRef);

                let userData;
                let isNewUser = false;

                if (!snapshot.exists()) {
                    // First time user - create profile
                    isNewUser = true;
                    const username = generateUsername(user.email, user.displayName);
                    
                    userData = {
                        uid: user.uid,
                        email: user.email,
                        displayName: user.displayName || user.email.split('@')[0],
                        username: username,
                        avatar: user.photoURL || '../assets/user_male_80px.png',
                        createdAt: serverTimestamp(),
                        lastLogin: serverTimestamp(),
                        provider: 'google'
                    };

                    // Save user profile to database
                    await set(userRef, userData);
                    console.log('New user profile created');
                } else {
                    // Existing user - get data and update last login
                    userData = snapshot.val();
                    await set(ref(database, `users/${user.uid}/lastLogin`), serverTimestamp());
                    
                    // Update avatar if Google photo changed
                    if (user.photoURL && user.photoURL !== userData.avatar) {
                        await set(ref(database, `users/${user.uid}/avatar`), user.photoURL);
                        userData.avatar = user.photoURL;
                    }
                }

                // Store user data in localStorage for immediate access
                localStorage.setItem('firebaseUser', JSON.stringify({
                    uid: user.uid,
                    email: user.email,
                    displayName: userData.displayName,
                    username: userData.username,
                    avatar: userData.avatar,
                    createdAt: userData.createdAt,
                    provider: 'google'
                }));

                // Redirect to dashboard with welcome message for new users
                if (isNewUser) {
                    window.location.href = 'dashboard/index.php?msg=' + encodeURIComponent('Welcome to OneTalk! Your account has been created successfully.');
                } else {
                    window.location.href = 'dashboard/index.php';
                }

            } catch (error) {
                console.error('Google Sign-Up error:', error);
                
                // Show error message
                let errorMessage = 'Account creation failed. Please try again.';
                
                switch (error.code) {
                    case 'auth/popup-closed-by-user':
                        errorMessage = 'Sign-up was cancelled. Please try again.';
                        break;
                    case 'auth/popup-blocked':
                        errorMessage = 'Please allow popups for this site and try again.';
                        break;
                    case 'auth/cancelled-popup-request':
                        errorMessage = 'Sign-up was cancelled. Please try again.';
                        break;
                    case 'auth/account-exists-with-different-credential':
                        errorMessage = 'An account with this email already exists. Please sign in instead.';
                        break;
                    default:
                        errorMessage = error.message || errorMessage;
                }
                
                signupAlert.textContent = errorMessage;
                signupAlert.style.display = 'block';
                
                // Reset button state
                button.disabled = false;
                buttonText.textContent = 'Sign up with Google';
                spinner.style.display = 'none';
            }
        });
    </script>
</body>

</html>
