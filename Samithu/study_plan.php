<?php
// study_plan.php - Study plan management page
session_start();

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/StudyPlan.php';
require_once 'classes/Subject.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$study_plan = new StudyPlan($db);

// Get user data
$user->findByFirebaseUid($_SESSION['firebase_uid']);

// Get active study plan
$has_active_plan = $study_plan->getActiveByUser($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Plan - StudySync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-blue-200 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h1 class="text-xl font-bold">Study Plan</h1>
            </div>
            <div class="flex items-center space-x-2">
                <span><?php echo htmlspecialchars($user->display_name ?: $user->username ?: $user->email); ?></span>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6">
        <?php if ($has_active_plan): ?>
            <!-- Existing Study Plan -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($study_plan->title); ?></h2>
                        <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($study_plan->description); ?></p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-blue-600"><?php echo $study_plan->progress; ?>%</div>
                        <div class="text-sm text-gray-500">Complete</div>
                    </div>
                </div>
                
                <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: <?php echo $study_plan->progress; ?>%"></div>
                </div>

                <div class="flex space-x-4">
                    <button id="add-subject-btn" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Add Subject
                    </button>
                    <button id="edit-plan-btn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Edit Plan
                    </button>
                </div>
            </div>

            <!-- Subjects List -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold mb-4">Subjects</h3>
                <div id="subjects-list" class="space-y-4">
                    <!-- Subjects will be loaded here via JavaScript -->
                </div>
            </div>
        <?php else: ?>
            <!-- Create New Study Plan -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Create Your Study Plan</h2>
                <p class="text-gray-600 mb-6">Start your learning journey by creating a structured study plan.</p>
                
                <div id="create-plan-form" class="space-y-4">
                    <div>
                        <label for="plan-title" class="block text-sm font-medium text-gray-700 mb-2">Plan Title</label>
                        <input type="text" id="plan-title" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., O/L Preparation 2024">
                    </div>
                    
                    <div>
                        <label for="plan-description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="plan-description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Describe your study goals and objectives..."></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="start-date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <input type="date" id="start-date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="end-date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                            <input type="date" id="end-date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <button id="create-plan-btn" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        Create Study Plan
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Subject Modal -->
    <div id="subject-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4">Add Subject</h3>
            
            <div class="space-y-4">
                <div>
                    <label for="subject-name" class="block text-sm font-medium text-gray-700 mb-2">Subject Name</label>
                    <input type="text" id="subject-name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Mathematics">
                </div>
                
                <div>
                    <label for="subject-description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="subject-description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Subject details..."></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="hours-allocated" class="block text-sm font-medium text-gray-700 mb-2">Hours Allocated</label>
                        <input type="number" id="hours-allocated" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="40">
                    </div>
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <select id="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="high">High</option>
                            <option value="medium" selected>Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button id="cancel-subject" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button id="save-subject" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Subject</button>
            </div>
        </div>
    </div>

    <script>
        const hasActivePlan = <?php echo $has_active_plan ? 'true' : 'false'; ?>;
        const currentPlanId = <?php echo $has_active_plan ? $study_plan->id : 'null'; ?>;

        // Create plan functionality
        if (!hasActivePlan) {
            document.getElementById('create-plan-btn').addEventListener('click', async () => {
                const title = document.getElementById('plan-title').value.trim();
                const description = document.getElementById('plan-description').value.trim();
                const startDate = document.getElementById('start-date').value;
                const endDate = document.getElementById('end-date').value;

                if (!title || !description || !startDate || !endDate) {
                    alert('Please fill in all fields');
                    return;
                }

                if (new Date(startDate) >= new Date(endDate)) {
                    alert('End date must be after start date');
                    return;
                }

                try {
                    const response = await fetch('study_plan_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'create_plan',
                            title, description, start_date: startDate, end_date: endDate
                        })
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        location.reload();
                    } else {
                        alert(result.message || 'Failed to create study plan');
                    }
                } catch (error) {
                    alert('An error occurred while creating the study plan');
                }
            });
        }

        // Add subject modal functionality
        if (hasActivePlan) {
            const subjectModal = document.getElementById('subject-modal');
            const addSubjectBtn = document.getElementById('add-subject-btn');
            const cancelSubjectBtn = document.getElementById('cancel-subject');
            const saveSubjectBtn = document.getElementById('save-subject');

            addSubjectBtn.addEventListener('click', () => {
                subjectModal.classList.remove('hidden');
            });

            cancelSubjectBtn.addEventListener('click', () => {
                subjectModal.classList.add('hidden');
                clearSubjectForm();
            });

            saveSubjectBtn.addEventListener('click', async () => {
                const name = document.getElementById('subject-name').value.trim();
                const description = document.getElementById('subject-description').value.trim();
                const hoursAllocated = document.getElementById('hours-allocated').value;
                const priority = document.getElementById('priority').value;

                if (!name || !hoursAllocated) {
                    alert('Please fill in required fields');
                    return;
                }

                try {
                    const response = await fetch('study_plan_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'add_subject',
                            study_plan_id: currentPlanId,
                            name, description, hours_allocated: hoursAllocated, priority
                        })
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        subjectModal.classList.add('hidden');
                        clearSubjectForm();
                        loadSubjects();
                    } else {
                        alert(result.message || 'Failed to add subject');
                    }
                } catch (error) {
                    alert('An error occurred while adding the subject');
                }
            });

            function clearSubjectForm() {
                document.getElementById('subject-name').value = '';
                document.getElementById('subject-description').value = '';
                document.getElementById('hours-allocated').value = '';
                document.getElementById('priority').value = 'medium';
            }

            // Load subjects
            async function loadSubjects() {
                try {
                    const response = await fetch(`api/subjects.php?plan_id=${currentPlanId}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        displaySubjects(result.subjects);
                    }
                } catch (error) {
                    console.error('Error loading subjects:', error);
                }
            }

            function displaySubjects(subjects) {
                const subjectsList = document.getElementById('subjects-list');
                
                if (subjects.length === 0) {
                    subjectsList.innerHTML = '<p class="text-gray-500">No subjects added yet. Click "Add Subject" to get started.</p>';
                    return;
                }

                subjectsList.innerHTML = subjects.map(subject => `
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="font-semibold text-lg">${subject.name}</h4>
                                <p class="text-gray-600 text-sm mt-1">${subject.description || 'No description'}</p>
                                <div class="flex items-center space-x-4 mt-2">
                                    <span class="text-sm text-gray-500">Priority: <span class="capitalize font-medium text-${getPriorityColor(subject.priority)}-600">${subject.priority}</span></span>
                                    <span class="text-sm text-gray-500">${subject.hours_completed}/${subject.hours_allocated} hours</span>
                                </div>
                            </div>
                            <div class="ml-4 text-right">
                                <div class="text-2xl font-bold text-blue-600">${Math.round((subject.hours_completed / subject.hours_allocated) * 100)}%</div>
                                <button class="text-blue-600 hover:text-blue-800 text-sm mt-1" onclick="updateProgress(${subject.id}, ${subject.hours_allocated}, ${subject.hours_completed})">
                                    Update Progress
                                </button>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: ${Math.min((subject.hours_completed / subject.hours_allocated) * 100, 100)}%"></div>
                        </div>
                    </div>
                `).join('');
            }

            function getPriorityColor(priority) {
                switch (priority) {
                    case 'high': return 'red';
                    case 'medium': return 'yellow';
                    case 'low': return 'green';
                    default: return 'gray';
                }
            }

            // Update progress function
            window.updateProgress = function(subjectId, hoursAllocated, currentHours) {
                const newHours = prompt(`Update study hours for this subject (Current: ${currentHours}/${hoursAllocated}):`, currentHours);
                
                if (newHours !== null && !isNaN(newHours) && newHours >= 0) {
                    updateSubjectProgress(subjectId, parseInt(newHours), hoursAllocated);
                }
            };

            async function updateSubjectProgress(subjectId, hoursCompleted, hoursAllocated) {
                try {
                    const response = await fetch('study_plan_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'update_progress',
                            subject_id: subjectId,
                            hours_completed: hoursCompleted,
                            hours_allocated: hoursAllocated
                        })
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        loadSubjects();
                        // Optionally reload the page to update overall progress
                        location.reload();
                    } else {
                        alert(result.message || 'Failed to update progress');
                    }
                } catch (error) {
                    alert('An error occurred while updating progress');
                }
            }

            // Load subjects on page load
            loadSubjects();
        }

        // Set default dates
        const today = new Date().toISOString().split('T')[0];
        const nextMonth = new Date();
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        const nextMonthStr = nextMonth.toISOString().split('T')[0];

        if (document.getElementById('start-date')) {
            document.getElementById('start-date').value = today;
            document.getElementById('end-date').value = nextMonthStr;
        }
    </script>
</body>
</html>

<?php
// api/subjects.php - API endpoint for subjects
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../classes/Subject.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_GET['plan_id'])) {
    echo json_encode(['success' => false, 'message' => 'Plan ID required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$subject = new Subject($db);

try {
    $stmt = $subject->readByStudyPlan($_GET['plan_id']);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'subjects' => $subjects]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch subjects']);
}
?>