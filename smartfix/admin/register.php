<?php
session_start();
include '../includes/db.php'; // Your DB connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $user_type = $_POST['user_type']; // 'admin' or 'user'

    // Check if passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Hash the password for storage
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists
        $query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            $error = "Email is already registered!";
        } else {
            // Insert the user into the database
            $insert_query = "INSERT INTO users (name, email, password, user_type) 
                             VALUES ('$name', '$email', '$hashed_password', '$user_type')";
            if (mysqli_query($conn, $insert_query)) {
                $success = "Registration successful. You can now log in.";
            } else {
                $error = "Error occurred during registration.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        /* Add some basic styling */
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; }
        input[type="text"], input[type="email"], input[type="password"], select { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background-color: #5cb85c; color: white; border: none; border-radius: 4px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>

<div class="container">
    <h2>Register</h2>

    <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
    <?php if (isset($success)) { echo "<p class='success'>$success</p>"; } ?>

    <form method="POST" action="register.php">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        
        <select name="user_type" required>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

</body>
</html>
