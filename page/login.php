<?php
require_once '../asset/lib/config.php';

// Set session timeout to 7 days
$lifetime = 60 * 60 * 24 * 7; // 7 days in seconds
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'domain' => '',  // Adjust if needed
    'httponly' => true,  // Prevent JavaScript access
    'samesite' => 'Lax'  // Prevent CSRF attacks
]);
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT id, password FROM user WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Extend session time
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['LAST_ACTIVITY'] = time(); // Track last activity time
            
            // Regenerate session ID for security
            session_regenerate_id(true);

            header("Location: ../index.php");
            exit();
        }
    }
    
    $error = "نام کاربری یا رمز عبور اشتباه است!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت دارایی</title>
    <link rel="stylesheet" href="../asset/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h3>لطفا مشخصات کاربری خود را وارد کنید.</h3>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">نام کاربری</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">رمز عبور</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">ورود</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
