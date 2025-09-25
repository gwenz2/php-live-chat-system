<?php
// Firebase Authentication Helper for PHP pages
// This replaces the MySQL-based session system

function requireFirebaseAuth() {
    // Check if user data exists in the expected places
    // Since we're migrating from PHP sessions to Firebase,
    // we'll use a simple approach that works with the JavaScript
    
    // For now, we'll create a simple session bridge
    // The JavaScript will handle the real authentication
    ?>
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js";
        import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js";

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

        // Check authentication status
        onAuthStateChanged(auth, (user) => {
            if (!user) {
                // No user signed in, redirect to login
                window.location.href = '../index.php?msg=' + encodeURIComponent('Please log in to access this page.');
            } else {
                // User is authenticated, ensure localStorage is synced
                const userData = localStorage.getItem('firebaseUser');
                if (!userData) {
                    // If localStorage is missing, redirect to login to refresh
                    window.location.href = '../index.php?msg=' + encodeURIComponent('Please log in again.');
                }
            }
        });

        // Make user data available globally for PHP compatibility
        const userData = localStorage.getItem('firebaseUser');
        if (userData) {
            const user = JSON.parse(userData);
            window.currentUser = user;
            
            // Create a session-like object for backward compatibility
            window.session = {
                user_id: user.uid,
                username: user.username || user.email.split('@')[0],
                display_name: user.displayName,
                email: user.email,
                avatar: user.avatar
            };
        }
    </script>
    <?php
}

// Get user data from localStorage via JavaScript bridge
function getCurrentFirebaseUser() {
    // This will be populated by JavaScript
    return null; // PHP can't access localStorage directly
}

// Create a mock session for backward compatibility
function createFirebaseSession() {
    // This is handled by the JavaScript bridge above
    // PHP pages will rely on JavaScript for authentication
}
?>