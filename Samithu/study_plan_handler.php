<?php
// study_plan_handler.php - Handle study plan operations
session_start();

require_once 'config/database.php';
require_once 'classes/StudyPlan.php';
require_once 'classes/Subject.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    switch ($input['action']) {
        case 'create_plan':
            $study_plan = new StudyPlan($db);
            $study_plan->user_id = $_SESSION['user_id'];
            $study_plan->title = $input['title'];
            $study_plan->description = $input['description'];
            $study_plan->start_date = $input['start_date'];
            $study_plan->end_date = $input['end_date'];
            
            if ($study_plan->create()) {
                echo json_encode(['success' => true, 'plan_id' => $study_plan->id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create study plan']);
            }
            break;
            
        case 'get_plans':
            $study_plan = new StudyPlan($db);
            $stmt = $study_plan->readByUser($_SESSION['user_id']);
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'plans' => $plans]);
            break;
            
        case 'add_subject':
            $subject = new Subject($db);
            $subject->study_plan_id = $input['study_plan_id'];
            $subject->name = $input['name'];
            $subject->description = $input['description'];
            $subject->hours_allocated = $input['hours_allocated'];
            $subject->priority = $input['priority'];
            
            if ($subject->create()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add subject']);
            }
            break;
            
        case 'update_progress':
            $subject = new Subject($db);
            $subject->id = $input['subject_id'];
            $subject->hours_completed = $input['hours_completed'];
            $subject->hours_allocated = $input['hours_allocated'];
            
            if ($subject->updateProgress()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update progress']);
            }
            break;
    }
}
?>
