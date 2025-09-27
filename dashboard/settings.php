<?php
// Firebase Authentication - no more MySQL dependency for auth
require_once '../firebase-auth.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="icon" href="../iconMO.svg" type="image/svg+xml">
    <title>OneTalk - Setting</title>
</head>
<style>
    body {
        font-family: 'Segoe UI', 'Arial', sans-serif;
        background: linear-gradient(135deg, #e3f0ff 0%, #f9f9f9 100%);
    }
</style>

<body class="p-3">
    <?php include_once 'navbar.php'; ?>
    <div class="container mt-4" style="max-width: 90vw;">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 w-100" style="max-width: 90vw;">
                <div class="card shadow-sm rounded-4">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-3">Profile Settings</h5>
                        
                        <!-- Toast Notifications -->
                        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
                            <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <span id="successMessage">Profile updated successfully!</span>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                                </div>
                            </div>
                            <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <span id="errorMessage">Error updating profile</span>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                                </div>
                            </div>
                        </div>
                        <!-- Firebase-based Profile Settings Form -->
                        <div id="loadingSpinner" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading profile settings...</p>
                        </div>

                        <form id="profileForm" style="display: none;" class="mb-2">
                            <div class="mb-2 text-center">
                                <img id="currentAvatar" src="" class="rounded-circle border border-primary" width="80" height="80" alt="Avatar">
                            </div>
                            <div class="mb-2">
                                <label for="display_name" class="form-label">Display Name</label>
                                <input type="text" class="form-control" id="display_name" name="display_name" required>
                            </div>
                            <div class="mb-2">
                                <label for="avatar_url" class="form-label">Avatar URL (Optional)</label>
                                <input type="url" class="form-control" id="avatar_url" name="avatar_url" placeholder="Enter image URL or leave blank to keep current">
                                <div class="form-text">Enter a direct link to an image, or leave blank to keep your current Google profile picture.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="updateBtn">
                                <span id="btnText">Update Profile</span>
                                <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </form>
                        <div class="text-center text-muted small"><strong>OneTalk - by Gwen Balajediong</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js";
        import { getAuth, onAuthStateChanged, updateProfile } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js";
        import { getDatabase, ref, get, set } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js";

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

        let currentUser = null;
        let currentUserData = null;

        // Firebase Data Cache System
        const CACHE_EXPIRY_TIME = 5 * 60 * 1000; // 5 minutes
        
        function setCachedData(key, data) {
            const cacheData = {
                data: data,
                timestamp: Date.now(),
                expiresAt: Date.now() + CACHE_EXPIRY_TIME
            };
            localStorage.setItem(`firebase_cache_${key}`, JSON.stringify(cacheData));
        }
        
        function getCachedData(key) {
            const cached = localStorage.getItem(`firebase_cache_${key}`);
            if (!cached) return null;
            
            const cacheData = JSON.parse(cached);
            if (Date.now() > cacheData.expiresAt) {
                localStorage.removeItem(`firebase_cache_${key}`);
                return null;
            }
            
            return cacheData.data;
        }

        // Set initial Google profile picture from localStorage if available
        const firebaseUserData = localStorage.getItem('firebaseUser');
        if (firebaseUserData) {
            const userData = JSON.parse(firebaseUserData);
            if (userData.photoURL) {
                document.getElementById('currentAvatar').src = userData.photoURL;
            } else {
                document.getElementById('currentAvatar').src = '../assets/user_male_80px.png';
            }
        } else {
            document.getElementById('currentAvatar').src = '../assets/user_male_80px.png';
        }

        // Show notification function
        function showNotification(message, type = 'success') {
            const toastId = type === 'success' ? 'successToast' : 'errorToast';
            const messageId = type === 'success' ? 'successMessage' : 'errorMessage';
            
            document.getElementById(messageId).textContent = message;
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
        }

        // Firebase Authentication Check
        onAuthStateChanged(auth, (user) => {
            if (user) {
                currentUser = user;
                console.log('Settings page - User authenticated:', user.uid);
                loadUserProfile(user);
            } else {
                console.log('Settings page - No user found, redirecting to login');
                localStorage.removeItem('firebaseUser');
                window.location.href = '../index.php';
            }
        });

        async function loadUserProfile(user) {
            try {
                // Check cache first
                currentUserData = getCachedData(`user_${user.uid}`);
                
                if (currentUserData) {
                    console.log('Using cached user profile data');
                } else {
                    // Get fresh data from Firebase Database
                    console.log('Loading fresh user profile from Firebase');
                    const userRef = ref(database, `users/${user.uid}`);
                    const snapshot = await get(userRef);
                    
                    if (snapshot.exists()) {
                        currentUserData = snapshot.val();
                        // Cache the user data
                        setCachedData(`user_${user.uid}`, currentUserData);
                        console.log('Loaded and cached user profile:', currentUserData);
                    }
                }
                
                if (currentUserData) {
                    
                    // Populate form with current data
                    document.getElementById('display_name').value = currentUserData.displayName || user.displayName || '';
                    document.getElementById('avatar_url').value = currentUserData.customAvatarUrl || '';
                    
                    // Set current avatar - show Google pic if no custom URL
                    const avatarImg = document.getElementById('currentAvatar');
                    if (currentUserData.customAvatarUrl && currentUserData.customAvatarUrl.trim()) {
                        avatarImg.src = currentUserData.customAvatarUrl;
                    } else if (currentUserData.photoURL) {
                        avatarImg.src = currentUserData.photoURL;
                    } else {
                        avatarImg.src = '../assets/user_male_80px.png';
                    }
                    
                    // Show form, hide loading
                    document.getElementById('loadingSpinner').style.display = 'none';
                    document.getElementById('profileForm').style.display = 'block';
                } else {
                    console.error('No user data found in database');
                    showNotification('Error loading profile data', 'error');
                }
            } catch (error) {
                console.error('Error loading user profile:', error);
                showNotification('Error loading profile: ' + error.message, 'error');
            }
        }

        // Handle profile update
        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const updateBtn = document.getElementById('updateBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            
            // Show loading state
            updateBtn.disabled = true;
            btnText.textContent = 'Updating...';
            btnSpinner.classList.remove('d-none');
            
            try {
                const newDisplayName = document.getElementById('display_name').value.trim();
                const newAvatarUrl = document.getElementById('avatar_url').value.trim();
                
                // Validate inputs
                if (!newDisplayName) {
                    throw new Error('Display name is required');
                }
                
                // Update user profile in Firebase Database
                const updates = {
                    displayName: newDisplayName,
                    customAvatarUrl: newAvatarUrl || null,
                    updatedAt: new Date().toISOString()
                };
                
                await set(ref(database, `users/${currentUser.uid}`), {
                    ...currentUserData,
                    ...updates
                });
                
                // Update Firebase Auth profile
                await updateProfile(currentUser, {
                    displayName: newDisplayName,
                    photoURL: newAvatarUrl || currentUserData.photoURL
                });
                
                // Update localStorage
                const firebaseUserData = JSON.parse(localStorage.getItem('firebaseUser') || '{}');
                firebaseUserData.displayName = newDisplayName;
                firebaseUserData.photoURL = newAvatarUrl || currentUserData.photoURL;
                localStorage.setItem('firebaseUser', JSON.stringify(firebaseUserData));
                
                // Update current data
                currentUserData = { ...currentUserData, ...updates };
                
                // Update avatar display
                const avatarImg = document.getElementById('currentAvatar');
                if (newAvatarUrl && newAvatarUrl.trim()) {
                    avatarImg.src = newAvatarUrl;
                } else if (currentUserData.photoURL) {
                    avatarImg.src = currentUserData.photoURL;
                } else {
                    avatarImg.src = '../assets/user_male_80px.png';
                }
                
                showNotification('Profile updated successfully!', 'success');
                
            } catch (error) {
                console.error('Error updating profile:', error);
                showNotification(error.message, 'error');
            } finally {
                // Reset button state
                updateBtn.disabled = false;
                btnText.textContent = 'Update Profile';
                btnSpinner.classList.add('d-none');
            }
        });
        
        // Preview avatar URL
        document.getElementById('avatar_url').addEventListener('input', (e) => {
            const url = e.target.value.trim();
            const avatarImg = document.getElementById('currentAvatar');
            
            if (url) {
                // Test if the URL is a valid image
                const testImg = new Image();
                testImg.onload = () => {
                    avatarImg.src = url;
                };
                testImg.onerror = () => {
                    // Keep current avatar if URL is invalid - prioritize Google profile pic
                    if (currentUserData.photoURL) {
                        avatarImg.src = currentUserData.photoURL;
                    } else {
                        avatarImg.src = '../assets/user_male_80px.png';
                    }
                };
                testImg.src = url;
            } else {
                // When URL is blank, show Google profile picture
                if (currentUserData.photoURL) {
                    avatarImg.src = currentUserData.photoURL;
                } else {
                    avatarImg.src = '../assets/user_male_80px.png';
                }
            }
        });
    </script>
</body>

</html>
