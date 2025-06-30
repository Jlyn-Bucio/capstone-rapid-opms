<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "rapid_opms");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name, email, password, position FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Check password
        if (password_verify($password, $user['password'])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_position"] = $user["position"];
            header("Location: main.php"); // redirect to dashboard
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .container {
      display: flex;
      width: 700px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border-radius: 10px;
      overflow: hidden;
    }
    .left {
      background: white;
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .left img {
      width: 120px;
    }
    .left h2 {
      margin-top: 20px;
    }
    .right {
      flex: 1;
      background: #7cd32a;
      padding: 30px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    input {
      padding: 8px;
      margin: 10px 0;
      border: none;
      border-radius: 5px;
      width: 100%;
        box-sizing: border-box;
        font-size: 16px;
        
    }
    .btn {
      background: black;
      color: white;
      font-weight: bold;
      cursor: pointer;
        border: none;
        padding: 10px;
        border-radius: 5px;
        transition: background 0.3s;
    }
    .error {
      color: red;
    }
  </style>
<div class="container">
  <div class="left">
    <img src="assets/pic/companylogo.png" alt="Logo">
    <h2>Rapid ConcreteTech</h2>
    <p>Builders Corporation</p>
  </div>
  <div class="right">
    <h2>LOGIN</h2>

    <?php if ($error): ?>
      <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <input class="btn" type="submit" value="LOGIN">
    </form>
  </div>
</div>
