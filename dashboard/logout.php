<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OneTalk - Signing Out</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #e3f0ff 0%, #f9f9f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .logout-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border: 0.3rem solid #f3f3f3;
            border-top: 0.3rem solid #1976d2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="spinner-border" role="status"></div>
        <h5 class="mt-3">Signing out...</h5>
        <p class="text-muted">Please wait while we sign you out.</p>
    </div>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js";
        import { getAuth, signOut } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js";
        import { getDatabase, ref, set, serverTimestamp } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js";

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

        async function signOutUser() {
            try {
                // Get current user data
                const firebaseUserData = localStorage.getItem('firebaseUser');
                if (firebaseUserData) {
                    const userData = JSON.parse(firebaseUserData);
                    
                    // Set user status to offline before signing out
                    const userStatusRef = ref(database, `users/${userData.uid}/status`);
                    await set(userStatusRef, {
                        online: false,
                        status: 'offline',
                        lastSeen: serverTimestamp(),
                        inChat: null,
                        name: userData.displayName,
                        avatar: userData.avatar,
                        timestamp: Date.now()
                    });
                }
                
                // Clear localStorage
                localStorage.removeItem('firebaseUser');
                
                // Sign out from Firebase Auth
                await signOut(auth);
                
                // Redirect to login page
                window.location.href = '../index.php?msg=' + encodeURIComponent('You have been logged out successfully.');
                
            } catch (error) {
                console.error('Sign out error:', error);
                // Even if there's an error, clear localStorage and redirect
                localStorage.removeItem('firebaseUser');
                window.location.href = '../index.php?msg=' + encodeURIComponent('You have been logged out.');
            }
        }

        // Start sign out process
        signOutUser();
    </script>
</body>
</html>
