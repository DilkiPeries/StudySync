session_start();

require_once 'config/database.php';
require_once 'classes/User.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if ($user->findByFirebaseUid($_SESSION['firebase_uid'])) {
    $user_data = [
        'id' => $user->id,
        'username' => $user->username,
        'display_name' => $user->display_name,
        'email' => $user->email,
        'profile_picture' => $user->profile_picture,
        'created_at' => $user->created_at
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - StudySync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 font-inter">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <h1 class="ml-4 text-2xl font-bold text-gray-900">Profile Settings</h1>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-3xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">Personal Information</h3>
                        
                        <div id="profile-messages"></div>
                        
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Profile Picture -->
                            <div class="col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
                                <div class="flex items-center">
                                    <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                        <?php echo strtoupper(substr($user_data['display_name'] ?: $user_data['username'] ?: $user_data['email'], 0, 1)); ?>
                                    </div>
                                    <button class="ml-4 bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50">
                                        Change
                                    </button>
                                </div>
                            </div>

                            <!-- Username -->
                            <div class="col-span-1">
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                <input type="text" id="username" value="<?php echo htmlspecialchars($user_data['username'] ?: ''); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Display Name -->
                            <div class="col-span-1">
                                <label for="display_name" class="block text-sm font-medium text-gray-700 mb-2">Display Name</label>
                                <input type="text" id="display_name" value="<?php echo htmlspecialchars($user_data['display_name'] ?: ''); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Email (readonly) -->
                            <div class="col-span-1">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" 
                                       readonly class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-500">
                                <p class="mt-1 text-sm text-gray-500">Email cannot be changed</p>
                            </div>

                            <!-- Account Created -->
                            <div class="col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Member Since</label>
                                <p class="text-sm text-gray-900"><?php echo date('F j, Y', strtotime($user_data['created_at'])); ?></p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" id="cancel-btn" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="button" id="save-btn" class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Study Statistics -->
                <div class="mt-8 bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">Study Statistics</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600">12</div>
                                <div class="text-sm text-gray-500">Study Plans Created</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600">156</div>
                                <div class="text-sm text-gray-500">Hours Studied</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-purple-600">89%</div>
                                <div class="text-sm text-gray-500">Average Progress</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const saveBtn = document.getElementById('save-btn');
        const cancelBtn = document.getElementById('cancel-btn');
        const messagesDiv = document.getElementById('profile-messages');

        // Original values for cancel functionality
        const originalValues = {
            username: document.getElementById('username').value,
            display_name: document.getElementById('display_name').value
        };

        saveBtn.addEventListener('click', async () => {
            const username = document.getElementById('username').value.trim();
            const displayName = document.getElementById('display_name').value.trim();

            if (!username || !displayName) {
                showMessage('Please fill in all required fields', 'error');
                return;
            }

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

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
                    showMessage('Profile updated successfully!', 'success');
                    originalValues.username = username;
                    originalValues.display_name = displayName;
                } else {
                    showMessage(result.message || 'Failed to update profile', 'error');
                }
            } catch (error) {
                showMessage('An error occurred while updating your profile', 'error');
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Changes';
            }
        });

        cancelBtn.addEventListener('click', () => {
            document.getElementById('username').value = originalValues.username;
            document.getElementById('display_name').value = originalValues.display_name;
            clearMessages();
        });

        function showMessage(message, type) {
            const className = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
            messagesDiv.innerHTML = `
                <div class="rounded-md ${className} p-4 mb-6">
                    <div class="text-sm">${message}</div>
                </div>
            `;
        }

        function clearMessages() {
            messagesDiv.innerHTML = '';
        }
    </script>
</body>
</html>

