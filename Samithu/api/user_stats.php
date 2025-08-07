session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get study plans count
    $query = "SELECT COUNT(*) as plan_count FROM study_plans WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $plan_count = $stmt->fetch(PDO::FETCH_ASSOC)['plan_count'];

    // Get total study hours
    $query = "SELECT SUM(hours_completed) as total_hours 
              FROM subjects s 
              JOIN study_plans sp ON s.study_plan_id = sp.id 
              WHERE sp.user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $total_hours = $stmt->fetch(PDO::FETCH_ASSOC)['total_hours'] ?: 0;

    // Get average progress
    $query = "SELECT AVG(progress) as avg_progress FROM study_plans WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $avg_progress = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_progress'] ?: 0);

    echo json_encode([
        'success' => true,
        'stats' => [
            'plan_count' => $plan_count,
            'total_hours' => $total_hours,
            'avg_progress' => $avg_progress
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch statistics']);
}
?>

<?php
// .htaccess - URL rewriting and security
/*
RewriteEngine On

# Redirect to login if not authenticated
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} ^/dashboard
RewriteRule ^(.*)$ login.php [R=302,L]

# Pretty URLs
RewriteRule ^login/?$ login.php [L]
RewriteRule ^dashboard/?$ dashboard.php [L]
RewriteRule ^profile/?$ profile.php [L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"

# Prevent access to sensitive files
<Files "*.php~">
    Deny from all
</Files>
<Files ".htaccess">
    Deny from all
</Files>
<Files "config.php">
    Deny from all
</Files>
*/
?>
