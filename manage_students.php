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
$department_id = $_SESSION['department_id'] ?? null; // Default to null if not set

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

// Function to generate a random password
function generateRandomPassword($length = 8) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

// Function to generate unique roll number
function generateUniqueRollNumber($conn) {
    do {
        $roll_number = strtoupper(substr(sha1(rand()), 0, 10)); // Generate random roll number
        $stmt = $conn->prepare("SELECT roll_number FROM Users WHERE roll_number = ?");
        $stmt->execute([$roll_number]);
        $existingRoll = $stmt->fetch(PDO::FETCH_ASSOC);
    } while ($existingRoll); // Ensure it's unique
    
    return $roll_number;
}

// Handle Add Student
if (isset($_POST['add_student'])) {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = 'Student';  // Students are always 'Student'
    $batch_year = $_POST['batch_year'] ?? '';
    $department_id = $_POST['department_id'] ?? null;  // Default to null if not set

    if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($phone) && !empty($batch_year) && !empty($department_id)) {
        $checkStmt = $conn->prepare("SELECT email, phone, roll_number FROM Users WHERE email = ? OR phone = ? OR roll_number = ?");
        $checkStmt->execute([$email, $phone, $roll_number]);
        $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            if ($existingUser['email'] == $email) {
                $message = "Error: This email is already in use!";
            } elseif ($existingUser['phone'] == $phone) {
                $message = "Error: This phone number is already in use!";
            } elseif ($existingUser['roll_number'] == $roll_number) {
                $message = "Error: This roll number is already in use!";
            }
        } else {
            $roll_number = generateUniqueRollNumber($conn);
            $randomPassword = generateRandomPassword();

            $stmt = $conn->prepare("INSERT INTO Users (first_name, last_name, email, phone, role, department_id, password, roll_number, batch_year) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt->execute([$first_name, $last_name, $email, $phone, $role, $department_id, $randomPassword, $roll_number, $batch_year])) {
                $message = "Student added successfully!";
            } else {
                $message = "Error: Failed to add student. Please try again!";
            }
        }
    } else {
        $message = "Error: All fields are required.";
    }
}

// Handle Edit Student
if (isset($_POST['edit_student'])) {
    $edit_id = $_POST['edit_id'] ?? '';
    $edit_first_name = $_POST['edit_first_name'] ?? '';
    $edit_last_name = $_POST['edit_last_name'] ?? '';
    $edit_email = $_POST['edit_email'] ?? '';
    $edit_phone = $_POST['edit_phone'] ?? '';
    $edit_batch_year = $_POST['edit_batch_year'] ?? '';
    $edit_department_id = $_POST['edit_department_id'] ?? null;  // Default to null if not set

    if (!empty($edit_id) && !empty($edit_first_name) && !empty($edit_last_name) && !empty($edit_email) && !empty($edit_phone) && !empty($edit_batch_year) && !empty($edit_department_id)) {
        $checkStmt = $conn->prepare("SELECT user_id FROM Users WHERE (email = ? OR phone = ?) AND user_id != ?");
        $checkStmt->execute([$edit_email, $edit_phone, $edit_id]);
        $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $message = "Error: Email or phone number is already in use!";
        } else {
            $updateStmt = $conn->prepare("UPDATE Users SET first_name=?, last_name=?, email=?, phone=?, batch_year=?, department_id=? WHERE user_id=?");
            if ($updateStmt->execute([$edit_first_name, $edit_last_name, $edit_email, $edit_phone, $edit_batch_year, $edit_department_id, $edit_id])) {
                $message = "Student updated successfully!";
            } else {
                $message = "Error: Failed to update student.";
            }
        }
    } else {
        $message = "Error: All fields are required.";
    }
}

// Handle Delete Student
if (isset($_POST['delete_student'])) {
    $delete_id = $_POST['delete_id'];
    
    if (!empty($delete_id)) {
        $deleteStmt = $conn->prepare("DELETE FROM Users WHERE user_id = ?");
        if ($deleteStmt->execute([$delete_id])) {
            $message = "Student deleted successfully.";
        } else {
            $message = "Error: Failed to delete student.";
        }
    } else {
        $message = "Error: Invalid student ID.";
    }
}

// Fetch Departments
$deptStmt = $conn->query("SELECT department_id, department_name FROM Departments ORDER BY department_name ASC");
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Students List
$stmt = $conn->query("SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.batch_year, d.department_name, u.roll_number 
                      FROM Users u 
                      LEFT JOIN Departments d ON u.department_id = d.department_id 
                      WHERE u.role = 'Student' 
                      ORDER BY u.first_name DESC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="bg-dark text-white vh-100 p-3" style="width: 250px;">
        <p class="text-center">
            <strong><?= htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES); ?> <?= htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES); ?></strong> <br>
            <small class="text-warning">(<?= htmlspecialchars($roleDisplay ?? '', ENT_QUOTES); ?>)</small> <br>
            <small><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES); ?></small>
        </p>
        <hr>
        <a href="students_uninformed.php" class="text-white text-decoration-none d-block p-2">Uninformed Students</a>
        
        <?php if ($role === 'Admin'): ?>
            <a href="manage_departments.php" class="text-white text-decoration-none d-block p-2">Manage Departments</a>
            <a href="manage_faculty.php" class="text-white text-decoration-none d-block p-2">Manage Faculty</a>
            <a href="manage_batches.php" class="text-white text-decoration-none d-block p-2">Manage Batches</a>
            <a href="manage_students.php" class="text-white text-decoration-none d-block p-2">Manage Students</a>
        <?php endif; ?>

        <a href="logout.php" class="text-white text-decoration-none d-block p-2">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2>Manage Students</h2>
        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search Students...">

        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message ?? '', ENT_QUOTES); ?></div>
        <?php endif; ?>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addStudentModal">Add Student</button>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Roll Number</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Batch Year</th>
                    <th>Department</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $stu): ?>
                    <tr>
                        <td><?= htmlspecialchars($stu['roll_number'] ?? '', ENT_QUOTES); ?></td>
                        <td><?= htmlspecialchars($stu['first_name'] ?? '', ENT_QUOTES); ?></td>
                        <td><?= htmlspecialchars($stu['last_name'] ?? '', ENT_QUOTES); ?></td>
                        <td><?= htmlspecialchars($stu['email'] ?? '', ENT_QUOTES); ?></td>
                        <td><?= htmlspecialchars($stu['phone'] ?? '', ENT_QUOTES); ?></td>
                        <td><?= htmlspecialchars($stu['batch_year'] ?? '', ENT_QUOTES); ?></td>
                        <td><?= htmlspecialchars($stu['department_name'] ?? 'N/A', ENT_QUOTES); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editStudentModal"
                                onclick="fillEditModal(
                                    '<?= $stu['user_id']; ?>', 
                                    '<?= htmlspecialchars($stu['first_name'] ?? '', ENT_QUOTES); ?>', 
                                    '<?= htmlspecialchars($stu['last_name'] ?? '', ENT_QUOTES); ?>', 
                                    '<?= htmlspecialchars($stu['email'] ?? '', ENT_QUOTES); ?>', 
                                    '<?= htmlspecialchars($stu['phone'] ?? '', ENT_QUOTES); ?>', 
                                    '<?= htmlspecialchars($stu['batch_year'] ?? '', ENT_QUOTES); ?>', 
                                    '<?= htmlspecialchars($stu['department_id'] ?? '', ENT_QUOTES); ?>',
                                    '<?= htmlspecialchars($stu['roll_number'] ?? '', ENT_QUOTES); ?>'
                                )">
                                Edit
                            </button>

                            <form method="post" class="d-inline">
                                <input type="hidden" name="delete_id" value="<?= $stu['user_id']; ?>">
                                <button type="submit" name="delete_student" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Batch Year</label>
                            <input type="text" class="form-control" name="batch_year" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select class="form-control" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id']; ?>"><?= $dept['department_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_student" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" id="edit_id" name="edit_id">
                        <div class="mb-3"><label>First Name</label><input type="text" class="form-control" id="edit_first_name" name="edit_first_name" required></div>
                        <div class="mb-3"><label>Last Name</label><input type="text" class="form-control" id="edit_last_name" name="edit_last_name" required></div>
                        <div class="mb-3"><label>Email</label><input type="email" class="form-control" id="edit_email" name="edit_email" required></div>
                        <div class="mb-3"><label>Phone</label><input type="text" class="form-control" id="edit_phone" name="edit_phone" required></div>
                        <div class="mb-3"><label>Batch Year</label><input type="text" class="form-control" id="edit_batch_year" name="edit_batch_year" required></div>
                        <div class="mb-3"><label>Department</label>
                            <select class="form-control" id="edit_department_id" name="edit_department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id']; ?>"><?= $dept['department_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="edit_student" class="btn btn-warning">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillEditModal(id, firstName, lastName, email, phone, batchYear, departmentId, rollNumber) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_first_name').value = firstName;
            document.getElementById('edit_last_name').value = lastName;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_batch_year').value = batchYear;
            document.getElementById('edit_department_id').value = departmentId;
        }
    </script>
</body>
</html>
