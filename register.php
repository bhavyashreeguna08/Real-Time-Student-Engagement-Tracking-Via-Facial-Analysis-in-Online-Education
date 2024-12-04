<?php
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $servername = "localhost";
    $db_username = "root";
    $db_password = "";
    $dbname = "attendance_db";

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);

    if ($stmt->execute()) {
        $message = "Registration successful. <a href='login.html'>Login</a>";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #87ceeb; /* Sky blue background color */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
        }
        .register-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 20px;
            color: green;
        }
        .error {
            color: red;
            margin-bottom: 20px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register</h2>
        <?php
        if ($message) {
            $messageClass = strpos($message, 'Error') === false ? 'message' : 'error';
            echo "<div class='$messageClass'>$message</div>";
        }
        ?>
        <form action="register.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>
            <label for="role">Role:</label>
            <select id="role" name="role">
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="administrator">Administrator</option>
            </select><br><br>
            <input type="submit" value="Register">
        </form>
    </div>
</body>
</html>
register.php