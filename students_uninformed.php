<?php
session_start();
include("database/config.php");

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$department_id = $_SESSION['department_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT first_name, last_name, email FROM Users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Role Display Formatting
$roleDisplay = match ($role) {
    'Admin' => 'Administrator',
    'HOD' => 'Head of Department',
    'Faculty' => 'Faculty Member',
    default => 'User'
};

// Handle "Mark as Informed" action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['mark_informed'])) {
    $student_id = $_POST['student_id'];
    $reason = $_POST['reason'] ?? NULL;
    
    // Check if entry already exists
    $checkStmt = $conn->prepare("SELECT 1 FROM InformedEntryExitLog WHERE user_id = ? AND DATE(informed_at) = CURDATE()");
    $checkStmt->execute([$student_id]);

    if ($checkStmt->fetch()) {
        $message = "Student already marked as informed for today.";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO InformedEntryExitLog (user_id, time, type, reason) VALUES (?, NOW(), 'Exit', ?)");
        if ($insertStmt->execute([$student_id, $reason])) {
            $message = "Student successfully marked as informed.";
        } else {
            $message = "Error marking student.";
        }
    }
}

// Fetch uninformed students grouped by date and batch
try {
    $query = "SELECT DATE(NOW()) AS record_date, B.batch_name, B.batch_id, 
                     COUNT(U.user_id) AS total_uninformed, 
                     F.first_name AS faculty_first, F.last_name AS faculty_last
              FROM Users U
              JOIN Batches B ON U.batch_id = B.batch_id
              LEFT JOIN Users F ON B.faculty_id = F.user_id
              WHERE U.role = 'Student' 
              AND NOT EXISTS (
                  SELECT 1 FROM InformedEntryExitLog EEL 
                  WHERE EEL.user_id = U.user_id 
                  AND DATE(EEL.informed_at) = CURDATE()
              )";

    if ($role === 'HOD') {
        $query .= " AND U.department_id = :department_id";
    } elseif ($role === 'Faculty') {
        $query .= " AND U.batch_id IN (SELECT batch_id FROM Batches WHERE faculty_id = :user_id)";
    }

    $query .= " GROUP BY record_date, B.batch_id 
                ORDER BY record_date DESC, B.batch_name";

    $stmt = $conn->prepare($query);

    if ($role === 'HOD') {
        $stmt->bindParam(':department_id', $department_id);
    } elseif ($role === 'Faculty') {
        $stmt->bindParam(':user_id', $user_id);
    }

    $stmt->execute();
    $uninformedData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Uninformed Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="d-flex">
 <!-- Sidebar -->
    <div class="bg-dark text-white vh-100 p-3" style="width: 250px;">
        <p class="text-center">
            <strong><?= htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?></strong> <br>
            <small class="text-warning">(<?= htmlspecialchars($roleDisplay); ?>)</small> <br>
            <small><?= htmlspecialchars($user['email']); ?></small>
        </p>
        <hr>
        <a href="students_uninformed.php" class="text-white text-decoration-none d-block p-2">Uninformed Students</a>
        
        <?php if ($role === 'Admin'): ?>
            <a href="manage_departments.php" class="text-white text-decoration-none d-block p-2">Manage Departments</a>
			<a href="manage_faculty.php" class="text-white text-decoration-none d-block p-2">Manage Faculty</a>
            <a href="manage_batches.php" class="text-white text-decoration-none d-block p-2">Manage Batches</a>
            <a href="manage_students.php" class="text-white text-decoration-none d-block p-2">Manage Students</a>
        
        <?php elseif ($role === 'HOD'): ?>
            <a href="manage_faculty.php" class="text-white text-decoration-none d-block p-2">Manage Faculty</a>
            <a href="manage_batches.php" class="text-white text-decoration-none d-block p-2">Manage Batches</a>
            <a href="manage_students.php" class="text-white text-decoration-none d-block p-2">Manage Students</a>
        
        <?php elseif ($role === 'Faculty'): ?>
            <a href="manage_students.php" class="text-white text-decoration-none d-block p-2">Manage Assigned Students</a>
        <?php endif; ?>

        <a href="logout.php" class="text-white text-decoration-none d-block p-2">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2>Students Uninformed List</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="accordion" id="uninformedAccordion">
            <?php
            foreach ($uninformedData as $record): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#batch_<?= $record['batch_id']; ?>">
                            <?= htmlspecialchars($record['batch_name']); ?> (Total Uninformed: <?= $record['total_uninformed']; ?>)
                        </button>
                    </h2>
                    <div id="batch_<?= $record['batch_id']; ?>" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <?php
                            $studentQuery = $conn->prepare("SELECT user_id, first_name, last_name, roll_number FROM Users WHERE batch_id = ? AND NOT EXISTS (
                                SELECT 1 FROM InformedEntryExitLog WHERE user_id = Users.user_id AND DATE(informed_at) = CURDATE()
                            ) ORDER BY first_name");
                            $studentQuery->execute([$record['batch_id']]);
                            $students = $studentQuery->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <?php foreach ($students as $student): ?>
                                <p>
                                    <?= htmlspecialchars($student['first_name'] . " " . $student['last_name'] . " (" . $student['roll_number'] . ")"); ?>
                                    <?php if ($role === 'HOD' || $role === 'Faculty'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="student_id" value="<?= $student['user_id']; ?>">
                                            <input type="text" name="reason" placeholder="Reason" required>
                                            <button type="submit" name="mark_informed" class="btn btn-sm btn-success">Mark as Informed</button>
                                        </form>
                                    <?php endif; ?>
                                </p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</body>
</html>
