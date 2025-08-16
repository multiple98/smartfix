<!-- login.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SmartFix Login</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('./img/ma.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.25);
            max-width: 400px;
            width: 100%;
            animation: fadeIn 1.2s ease;
        }

        h2 {
            text-align: center;
            color: #28a745;
            margin-bottom: 25px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            transition: 0.3s ease;
        }

        input:focus {
            border-color: #28a745;
            outline: none;
            box-shadow: 0 0 5px rgba(40, 167, 69, 0.4);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background-color: #218838;
        }

        .message {
            text-align: center;
            margin-top: 15px;
        }

        .message a {
            color: #007bff;
            text-decoration: none;
        }

        .message a:hover {
            text-decoration: underline;
        }

        .error {
            color: red;
            text-align: center;
            margin-top: -15px;
            margin-bottom: 15px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Welcome to SmartFix</h2>

    <form method="POST" action="login.php">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <?php
   session_start();
   include '../includes/db.php'; // adjust path as needed
   
   if ($_SERVER['REQUEST_METHOD'] == 'POST') {
       $email = $_POST['email'];
       $password = $_POST['password'];
   
       $query = "SELECT * FROM users WHERE email='$email' AND password='$password' LIMIT 3";
       $result = mysqli_query($conn, $query);
   
       if ($result && mysqli_num_rows($result) == 1) {
           $user = mysqli_fetch_assoc($result);
           $_SESSION['user_id'] = $user['id'];
           $_SESSION['user_type'] = $user['user_type']; // 'admin' or 'user'
   
           // Redirect based on role
           if ($user['user_type'] == 'admin') {
               header("Location: admin/admin_dashboard.php"); // admin dashboard
           } else {
               header("Location: /dashboard.php"); // regular user dashboard
           }
           exit();
       } else {
           $error = "Invalid credentials!";
       }
   }
   
    ?>

    <div class="message">
        Don't have an account? <a href="register.php">Register here</a>
    </div>
</div>

</body>
</html>
