<?php
// config/firebase_config.php
class FirebaseConfig {
    public static function getConfig() {
        return [
            'apiKey' => "AIzaSyCK8hd_Lx2jg2yJ4BUCEZz5mlmuQv_fK6Y",
            'authDomain' => "kannangara-117b9.firebaseapp.com",
            'projectId' => "kannangara-117b9",
            'storageBucket' => "kannangara-117b9.firebasestorage.app",
            'messagingSenderId' => "697482259945",
            'appId' => "1:697482259945:web:788f0f4debe17682beaf78"
        ];
    }
}

// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'studysync_db';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// classes/User.php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $firebase_uid;
    public $username;
    public $email;
    public $display_name;
    public $profile_picture;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createFromFirebase($firebase_uid, $email) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET firebase_uid=:firebase_uid, email=:email, created_at=:created_at";

        $stmt = $this->conn->prepare($query);

        $this->firebase_uid = htmlspecialchars(strip_tags($firebase_uid));
        $this->email = htmlspecialchars(strip_tags($email));
        $this->created_at = date('Y-m-d H:i:s');

        $stmt->bindParam(":firebase_uid", $this->firebase_uid);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":created_at", $this->created_at);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function findByFirebaseUid($firebase_uid) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE firebase_uid = :firebase_uid LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':firebase_uid', $firebase_uid);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->firebase_uid = $row['firebase_uid'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->display_name = $row['display_name'];
            $this->profile_picture = $row['profile_picture'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username=:username, display_name=:display_name, 
                      profile_picture=:profile_picture, updated_at=:updated_at 
                  WHERE firebase_uid=:firebase_uid";

        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->display_name = htmlspecialchars(strip_tags($this->display_name));
        $this->updated_at = date('Y-m-d H:i:s');

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":display_name", $this->display_name);
        $stmt->bindParam(":profile_picture", $this->profile_picture);
        $stmt->bindParam(":updated_at", $this->updated_at);
        $stmt->bindParam(":firebase_uid", $this->firebase_uid);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function hasUsername() {
        return !empty($this->username);
    }
}

// classes/StudyPlan.php
class StudyPlan {
    private $conn;
    private $table_name = "study_plans";

    public $id;
    public $user_id;
    public $title;
    public $description;
    public $goals;
    public $progress;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getActiveByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND status = 'active' 
                  ORDER BY created_at DESC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->goals = $row['goals'];
            $this->progress = $row['progress'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }
}
?>

<?php
// auth_handler.php - Handle Firebase authentication and user management
session_start();

require_once 'config/database.php';
require_once 'config/firebase_config.php';
require_once 'classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action'])) {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);
        
        switch ($input['action']) {
            case 'verify_auth':
                if (isset($input['firebase_uid']) && isset($input['email'])) {
                    $firebase_uid = $input['firebase_uid'];
                    $email = $input['email'];
                    
                    if ($user->findByFirebaseUid($firebase_uid)) {
                        $_SESSION['user_id'] = $user->id;
                        $_SESSION['firebase_uid'] = $user->firebase_uid;
                        $_SESSION['email'] = $user->email;
                        $_SESSION['username'] = $user->username;
                        $_SESSION['display_name'] = $user->display_name;
                        
                        echo json_encode([
                            'success' => true,
                            'has_username' => $user->hasUsername(),
                            'user' => [
                                'id' => $user->id,
                                'username' => $user->username,
                                'display_name' => $user->display_name,
                                'email' => $user->email
                            ]
                        ]);
                    } else {
                        // Create new user
                        if ($user->createFromFirebase($firebase_uid, $email)) {
                            $_SESSION['user_id'] = $user->id;
                            $_SESSION['firebase_uid'] = $user->firebase_uid;
                            $_SESSION['email'] = $user->email;
                            $_SESSION['username'] = null;
                            $_SESSION['display_name'] = null;
                            
                            echo json_encode([
                                'success' => true,
                                'has_username' => false,
                                'user' => [
                                    'id' => $user->id,
                                    'username' => null,
                                    'display_name' => null,
                                    'email' => $user->email
                                ]
                            ]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to create user']);
                        }
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid data']);
                }
                break;
                
            case 'update_profile':
                if (isset($_SESSION['firebase_uid']) && isset($input['username']) && isset($input['display_name'])) {
                    if ($user->findByFirebaseUid($_SESSION['firebase_uid'])) {
                        $user->username = $input['username'];
                        $user->display_name = $input['display_name'];
                        
                        if ($user->updateProfile()) {
                            $_SESSION['username'] = $user->username;
                            $_SESSION['display_name'] = $user->display_name;
                            
                            echo json_encode([
                                'success' => true,
                                'message' => 'Profile updated successfully'
                            ]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid data']);
                }
                break;
                
            case 'logout':
                session_destroy();
                echo json_encode(['success' => true]);
                break;
        }
    }
    exit;
}
?>

<?php
// dashboard.php - Main dashboard file
session_start();

require_once 'config/database.php';
require_once 'config/firebase_config.php';
require_once 'classes/User.php';
require_once 'classes/StudyPlan.php';

// Check if user is logged in
$user_data = null;
if (isset($_SESSION['user_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    if ($user->findByFirebaseUid($_SESSION['firebase_uid'])) {
        $user_data = [
            'id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'email' => $user->email,
            'has_username' => $user->hasUsername()
        ];
        
        // Get study plan data
        $study_plan = new StudyPlan($db);
        $has_active_plan = $study_plan->getActiveByUser($user->id);
    }
}

$firebase_config = FirebaseConfig::getConfig();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudySync Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        #sidebar {
            transition: transform 0.3s ease-in-out;
        }
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.open {
                transform: translateX(0);
            }
        }
        .profile-modal {
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gray-100 transition-colors duration-300" id="body">
    <!-- Loading Screen -->
    <div id="loading-screen" class="fixed inset-0 bg-white flex items-center justify-center z-50">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Loading StudySync...</p>
        </div>
    </div>

    <!-- Profile Setup Modal -->
    <div id="profile-modal" class="fixed inset-0 bg-black bg-opacity-50 profile-modal flex items-center justify-center z-50 hidden">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full mx-4">
            <h2 class="text-2xl font-bold mb-4 text-center">Complete Your Profile</h2>
            <p class="text-gray-600 mb-6 text-center">Please set up your username to get started</p>
            
            <div id="profile-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"></div>
            
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input type="text" id="username" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your username">
            </div>
            
            <div class="mb-6">
                <label for="display-name" class="block text-sm font-medium text-gray-700 mb-2">Display Name</label>
                <input type="text" id="display-name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your display name">
            </div>
            
            <button id="save-profile" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                Save Profile
            </button>
        </div>
    </div>

    <!-- Main Dashboard -->
    <div id="main-dashboard" class="hidden">
        <header class="bg-blue-600 text-white p-4 flex justify-between items-center transition-colors duration-300" id="header">
            <div class="flex items-center space-x-4">
                <button id="menu-toggle" class="md:hidden focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
                <h1 class="text-xl font-bold">StudySync</h1>
            </div>
            <nav class="hidden md:flex space-x-4">
                <a href="#" class="hover:underline">Dashboard</a>
                <a href="#" class="hover:underline">Study Plan</a>
                <a href="#" class="hover:underline">Timetable</a>
                <a href="#" class="hover:underline">Resources</a>
                <a href="#" class="hover:underline">Lessons</a>
                <a href="#" class="hover:underline">Chat</a>
            </nav>
            <div class="flex items-center space-x-4">
                <button id="dark-mode-toggle" class="bg-blue-400 hover:bg-blue-500 text-white font-semibold py-1 px-3 rounded transition-colors duration-300">Dark Mode</button>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-blue-400 rounded-full flex items-center justify-center">
                        <span id="user-avatar" class="text-sm font-semibold">?</span>
                    </div>
                    <span id="user-display-name" class="font-medium">Guest</span>
                    <button id="logout-btn" class="text-blue-200 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <div class="flex min-h-screen">
            <aside id="sidebar" class="bg-white w-64 p-6 shadow-lg md:static absolute min-h-screen">
                <h2 class="text-lg font-semibold mb-4">Menu</h2>
                <ul class="space-y-2">
                    <li><a href="#" class="block p-2 rounded hover:bg-blue-100">Study Plan</a></li>
                    <li><a href="#" class="block p-2 rounded hover:bg-blue-100">Timetable & Attendance</a></li>
                    <li><a href="#" class="block p-2 rounded hover:bg-blue-100">Notes & Pastpapers</a></li>
                    <li><a href="#" class="block p-2 rounded hover:bg-blue-100">Interactive Lessons</a></li>
                    <li><a href="#" class="block p-2 rounded hover:bg-blue-100">Chat Room</a></li>
                </ul>
            </aside>

            <main class="flex-1 p-6">
                <section class="mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Welcome to StudySync</h2>
                    <p class="text-gray-600">Plan, learn, connect, and succeed!</p>
                </section>

                <section class="mb-6">
                    <h3 class="text-xl font-medium text-gray-800 mb-4">Your Study Plan</h3>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <?php if (isset($has_active_plan) && $has_active_plan): ?>
                            <h4 class="font-semibold"><?php echo htmlspecialchars($study_plan->title); ?></h4>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($study_plan->description); ?></p>
                            <div class="mt-2">
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $study_plan->progress; ?>%"></div>
                                </div>
                                <p class="text-sm text-gray-600 mt-1"><?php echo $study_plan->progress; ?>% Complete</p>
                            </div>
                        <?php else: ?>
                            <h4 class="font-semibold">Weekly Goals</h4>
                            <p class="text-sm text-gray-600">Complete 5 math chapters, 3 science quizzes, and 1 history essay.</p>
                            <div class="mt-2">
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: 60%"></div>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">60% Complete</p>
                            </div>
                        <?php endif; ?>
                        <button class="mt-2 text-blue-600 hover:underline">Update Plan</button>
                    </div>
                </section>

                <section class="mb-6">
                    <h3 class="text-xl font-medium text-gray-800 mb-4">Timetable & Attendance</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded-lg shadow">
                            <h4 class="font-semibold">Math Class</h4>
                            <p class="text-sm text-gray-600">Today, 2:00 PM - 3:30 PM</p>
                            <p class="text-sm text-green-600">Attended</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow">
                            <h4 class="font-semibold">Science Lab</h4>
                            <p class="text-sm text-gray-600">Tomorrow, 10:00 AM - 11:30 AM</p>
                            <p class="text-sm text-gray-600">Pending</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow">
                            <h4 class="font-semibold">History Seminar</h4>
                            <p class="text-sm text-gray-600">Wed, 1:00 PM - 2:30 PM</p>
                            <p class="text-sm text-red-600">Missed</p>
                        </div>
                    </div>
                </section>

                <section class="mb-6">
                    <h3 class="text-xl font-medium text-gray-800 mb-4">Notes & Pastpapers</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-white p-4 rounded-lg shadow">
                            <h4 class="font-semibold">Math Notes</h4>
                            <p class="text-sm text-gray-600">Algebra and calculus summaries.</p>
                            <a href="#" class="mt-2 text-blue-600 hover:underline">Download</a>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow">
                            <h4 class="font-semibold">Science Pastpapers</h4>
                            <p class="text-sm text-gray-600">2019-2024 O/L papers.</p>
                            <a href="#" class="mt-2 text-blue-600 hover:underline">Download</a>
                        </div>
                    </div>
                </section>

                <section class="mb-6">
                    <h3 class="text-xl font-medium text-gray-800 mb-4">Interactive Lessons</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-white p-4 rounded-lg shadow">
                            <h4 class="font-semibold">Math Quiz</h4>
                            <p class="text-sm text-gray-600">Test your algebra skills with 10 questions.</p>
                            <button class="mt-2 text-blue-600 hover:underline">Start Quiz</button>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow">
                            <h4 class="font-semibold">Flashcards: Biology</h4>
                            <p class="text-sm text-gray-600">Memorize key terms with interactive cards.</p>
                            <button class="mt-2 text-blue-600 hover:underline">Start Flashcards</button>
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="text-xl font-medium text-gray-800 mb-4">Chat Room</h3>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <h4 class="font-semibold">Study Group Chat</h4>
                        <div class="mt-2 h-40 overflow-y-auto bg-gray-50 p-2 rounded">
                            <p class="text-sm text-gray-600">[AI Assistant] Need help with math? Ask away!</p>
                            <p class="text-sm text-gray-600">[User1] Can someone explain quadratic equations?</p>
                            <p class="text-sm text-gray-600">[User2] Sure, let's break it down!</p>
                        </div>
                        <div class="mt-2 flex">
                            <input type="text" placeholder="Type a message..." class="flex-1 p-2 border rounded-l-lg focus:outline-none">
                            <button class="bg-blue-600 text-white p-2 rounded-r-lg hover:bg-blue-700">Send</button>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- Firebase SDK -->
    <script type="module">
        // Import Firebase modules
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import { 
            getAuth, 
            onAuthStateChanged,
            signOut
        } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';

        // Firebase configuration
        const firebaseConfig = <?php echo json_encode($firebase_config); ?>;

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);

        // PHP user data
        const phpUserData = <?php echo json_encode($user_data); ?>;

        // DOM elements
        const loadingScreen = document.getElementById('loading-screen');
        const profileModal = document.getElementById('profile-modal');
        const mainDashboard = document.getElementById('main-dashboard');
        const userDisplayName = document.getElementById('user-display-name');
        const userAvatar = document.getElementById('user-avatar');
        const saveProfileBtn = document.getElementById('save-profile');
        const logoutBtn = document.getElementById('logout-btn');
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        const body = document.getElementById('body');

        // State
        let currentUser = null;

        // Initialize app
        onAuthStateChanged(auth, async (user) => {
            if (user) {
                currentUser = user;
                
                if (phpUserData) {
                    // User exists in PHP session
                    if (phpUserData.has_username) {
                        showDashboard(phpUserData);
                    } else {
                        showProfileModal();
                    }
                } else {
                    // Verify auth with PHP backend
                    try {
                        const response = await fetch('auth_handler.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'verify_auth',
                                firebase_uid: user.uid,
                                email: user.email
                            })
                        });

                        const result = await response.json();
                        
                        if (result.success) {
                            if (result.has_username) {
                                showDashboard(result.user);
                            } else {
                                showProfileModal();
                            }
                        } else {
                            console.error('Auth verification failed:', result.message);
                            redirectToLogin();
                        }
                    } catch (error) {
                        console.error('Error verifying auth:', error);
                        redirectToLogin();
                    }
                }
            } else {
                redirectToLogin();
            }
        });

        // Show dashboard
        function showDashboard(userData) {
            loadingScreen.classList.add('hidden');
            profileModal.classList.add('hidden');
            mainDashboard.classList.remove('hidden');
            
            if (userData.display_name) {
                userDisplayName.textContent = userData.display_name;
                userAvatar.textContent = userData.display_name.charAt(0).toUpperCase();
            } else if (userData.username) {
                userDisplayName.textContent = userData.username;
                userAvatar.textContent = userData.username.charAt(0).toUpperCase();
            } else {
                userDisplayName.textContent = userData.email;
                userAvatar.textContent = userData.email.charAt(0).toUpperCase();
            }
        }

        // Show profile modal
        function showProfileModal() {
            loadingScreen.classList.add('hidden');
            mainDashboard.classList.add('hidden');
            profileModal.classList.remove('hidden');
            
            // Pre-fill with Firebase data if available
            if (currentUser) {
                document.getElementById('display-name').value = currentUser.displayName || '';
            }
        }

        // Save profile
        saveProfileBtn.addEventListener('click', async () => {
            const username = document.getElementById('username').value.trim();
            const displayName = document.getElementById('display-name').value.trim();
            const errorDiv = document.getElementById('profile-error');
            
            if (!username || !displayName) {
                showProfileError('Please fill in all fields');
                return;
            }
            
            if (username.length < 3) {
                showProfileError('Username must be at least 3 characters long');
                return;
            }
            
            saveProfileBtn.disabled = true;
            saveProfileBtn.textContent = 'Saving...';
            
            try {
                const response = await fetch('auth_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_profile',
                        username: username,
                        display_name: displayName
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    showDashboard({
                        username: username,
                        display_name: displayName,
                        email: currentUser.email
                    });
                } else {
                    showProfileError(result.message || 'Failed to update profile');
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                showProfileError('An error occurred while updating your profile');
            } finally {
                saveProfileBtn.disabled = false;
                saveProfileBtn.textContent = 'Save Profile';
            }
        });

        // Show profile error
        function showProfileError(message) {
            const errorDiv = document.getElementById('profile-error');
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
        }

        // Redirect to login
        function redirectToLogin() {
            window.location.href = 'login.php';
        }

        // Logout
        logoutBtn.addEventListener('click', async () => {
            try {
                await signOut(auth);
                
                // Clear PHP session
                await fetch('auth_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'logout'
                    })
                });
                
                redirectToLogin();
            } catch (error) {
                console.error('Error signing out:', error);
            }
        });

        // Mobile menu toggle
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Dark mode toggle
        if (localStorage.getItem('theme') === 'dark') {
            body.classList.add('dark');
            darkModeToggle.textContent = 'Light Mode';
        }

        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark');
            if (body.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
                darkModeToggle.textContent = 'Light Mode';
            } else {
                localStorage.setItem('theme', 'light');
                darkModeToggle.textContent = 'Dark Mode';
            }
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768 && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>

<?php
/*
SQL Database Schema - Run this to create the necessary tables:

CREATE DATABASE studysync_db;
USE studysync_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE study_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    goals TEXT,
    progress INT DEFAULT 0,
    status ENUM('active', 'completed', 'paused') DEFAULT 'active',
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    study_plan_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    hours_allocated INT DEFAULT 0,
    hours_completed INT DEFAULT 0,
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (study_plan_id) REFERENCES study_plans(id) ON DELETE CASCADE
);

CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    class_time TIME NOT NULL,
    class_date DATE NOT NULL,
    duration INT DEFAULT 90,
    status ENUM('pending', 'attended', 'missed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    file_path VARCHAR(500),
    note_type ENUM('text', 'file', 'link') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
);

CREATE TABLE quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    quiz_name VARCHAR(255) NOT NULL,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    time_taken INT, -- in seconds
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

*/
?>

<?php
// login.php - Login page for Firebase authentication
$firebase_config = FirebaseConfig::getConfig();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudySync - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">StudySync</h1>
                <p class="text-gray-600">Sign in to access your dashboard</p>
            </div>

            <div class="user-type-selector flex mb-6 bg-gray-100 rounded-lg p-1">
                <button class="user-type-btn flex-1 py-2 px-4 rounded-md transition-all duration-200 font-medium active bg-blue-600 text-white" data-type="student">
                    Student
                </button>
                <button class="user-type-btn flex-1 py-2 px-4 rounded-md transition-all duration-200 font-medium text-gray-600 hover:text-gray-800" data-type="admin">
                    Administrator
                </button>
            </div>

            <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"></div>
            <div id="success-message" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"></div>

            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input type="email" id="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Enter your email">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" id="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Enter your password">
                </div>
            </div>

            <button id="submit-btn" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors mt-6">
                Sign In
            </button>

            <div class="text-center mt-4">
                <span class="text-gray-600">Don't have an account? </span>
                <a href="#" id="toggle-mode" class="text-blue-600 hover:underline font-medium">Register here</a>
            </div>
        </div>
    </div>

    <!-- Firebase SDK -->
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import { 
            getAuth, 
            createUserWithEmailAndPassword, 
            signInWithEmailAndPassword,
            onAuthStateChanged
        } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';

        // Firebase configuration
        const firebaseConfig = <?php echo json_encode($firebase_config); ?>;

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);

        // DOM elements
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const submitBtn = document.getElementById('submit-btn');
        const toggleMode = document.getElementById('toggle-mode');
        const errorMessage = document.getElementById('error-message');
        const successMessage = document.getElementById('success-message');
        const userTypeButtons = document.querySelectorAll('.user-type-btn');

        // State
        let isLoginMode = true;
        let selectedUserType = 'student';

        // User type selection
        userTypeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                userTypeButtons.forEach(b => {
                    b.classList.remove('active', 'bg-blue-600', 'text-white');
                    b.classList.add('text-gray-600');
                });
                btn.classList.add('active', 'bg-blue-600', 'text-white');
                btn.classList.remove('text-gray-600');
                selectedUserType = btn.dataset.type;
            });
        });

        // Toggle between login and register
        toggleMode.addEventListener('click', (e) => {
            e.preventDefault();
            isLoginMode = !isLoginMode;
            
            if (isLoginMode) {
                submitBtn.textContent = 'Sign In';
                toggleMode.textContent = 'Register here';
                toggleMode.parentElement.firstChild.textContent = "Don't have an account? ";
            } else {
                submitBtn.textContent = 'Register';
                toggleMode.textContent = 'Sign in here';
                toggleMode.parentElement.firstChild.textContent = 'Already have an account? ';
            }
            
            clearMessages();
        });

        // Form submission
        submitBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();
            
            if (!email || !password) {
                showError('Please fill in all fields');
                return;
            }

            if (password.length < 6) {
                showError('Password must be at least 6 characters');
                return;
            }

            // Validate admin email domain
            if (selectedUserType === 'admin' && !email.endsWith('@admin.studysync.com')) {
                showError('Admin accounts must use @admin.studysync.com email domain');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = isLoginMode ? 'Signing In...' : 'Registering...';
            
            try {
                if (isLoginMode) {
                    await signInWithEmailAndPassword(auth, email, password);
                } else {
                    await createUserWithEmailAndPassword(auth, email, password);
                }
            } catch (error) {
                showError(getErrorMessage(error.code));
                submitBtn.disabled = false;
                submitBtn.textContent = isLoginMode ? 'Sign In' : 'Register';
            }
        });

        // Auth state observer
        onAuthStateChanged(auth, (user) => {
            if (user) {
                // Redirect to dashboard
                window.location.href = 'dashboard.php';
            }
        });

        // Utility functions
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.classList.remove('hidden');
            successMessage.classList.add('hidden');
        }

        function showSuccess(message) {
            successMessage.textContent = message;
            successMessage.classList.remove('hidden');
            errorMessage.classList.add('hidden');
        }

        function clearMessages() {
            errorMessage.classList.add('hidden');
            successMessage.classList.add('hidden');
        }

        function getErrorMessage(errorCode) {
            switch (errorCode) {
                case 'auth/user-not-found':
                    return 'No account found with this email address';
                case 'auth/wrong-password':
                    return 'Incorrect password';
                case 'auth/email-already-in-use':
                    return 'An account with this email already exists';
                case 'auth/weak-password':
                    return 'Password is too weak';
                case 'auth/invalid-email':
                    return 'Invalid email address';
                case 'auth/too-many-requests':
                    return 'Too many failed attempts. Please try again later';
                default:
                    return 'An error occurred. Please try again';
            }
        }

        // Enter key support
        document.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                submitBtn.click();
            }
        });
    </script>
</body>
</html>