// Firebase Authentication and Database Configuration
// This file contains the Firebase config and common authentication functions

// Firebase Configuration - same as used in chat system
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

// Authentication helper functions
class FirebaseAuth {
    constructor() {
        this.app = null;
        this.auth = null;
        this.db = null;
        this.initialized = false;
    }

    async init() {
        if (this.initialized) return;
        
        try {
            const { initializeApp } = await import('https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js');
            const { getAuth, onAuthStateChanged } = await import('https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js');
            const { getDatabase } = await import('https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js');

            this.app = initializeApp(firebaseConfig);
            this.auth = getAuth(this.app);
            this.db = getDatabase(this.app);
            this.initialized = true;

            // Set up authentication state listener
            onAuthStateChanged(this.auth, (user) => {
                if (user) {
                    // User is signed in
                    localStorage.setItem('firebaseUser', JSON.stringify({
                        uid: user.uid,
                        email: user.email,
                        displayName: user.displayName,
                        photoURL: user.photoURL
                    }));
                } else {
                    // User is signed out
                    localStorage.removeItem('firebaseUser');
                }
            });
        } catch (error) {
            console.error('Firebase initialization failed:', error);
        }
    }

    // Get current user from Firebase Auth
    getCurrentUser() {
        return this.auth?.currentUser || null;
    }

    // Get user data from localStorage (for immediate access)
    getCurrentUserData() {
        const userData = localStorage.getItem('firebaseUser');
        return userData ? JSON.parse(userData) : null;
    }

    // Check if user is authenticated
    isAuthenticated() {
        return this.getCurrentUser() !== null || this.getCurrentUserData() !== null;
    }

    // Redirect to login if not authenticated
    requireAuth() {
        if (!this.isAuthenticated()) {
            window.location.href = '/index.php?msg=' + encodeURIComponent('Please log in to access this page.');
            return false;
        }
        return true;
    }

    // Sign out user
    async signOut() {
        try {
            const { signOut } = await import('https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js');
            await signOut(this.auth);
            localStorage.removeItem('firebaseUser');
            window.location.href = '/index.php?msg=' + encodeURIComponent('You have been logged out.');
        } catch (error) {
            console.error('Sign out error:', error);
        }
    }
}

// Global Firebase Auth instance
window.firebaseAuth = new FirebaseAuth();

// Initialize Firebase when DOM is ready
document.addEventListener('DOMContentLoaded', async () => {
    await window.firebaseAuth.init();
});

// Export for use in modules
export { firebaseConfig, FirebaseAuth };