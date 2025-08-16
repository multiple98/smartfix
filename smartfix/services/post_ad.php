<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include '../includes/db.php';

$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $user_id = $_SESSION['user_id'];

    // Handle file upload
    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];
    move_uploaded_file($tmp, "../img/$image");

    $stmt = $pdo->prepare("INSERT INTO shop_items (user_id, title, description, category, price, image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $description, $category, $price, $image]);

    $msg = "Item posted successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Post Ad</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #eef2f3; }
        form { background: white; padding: 20px; width: 400px; margin: auto; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        input, textarea, select { width: 100%; padding: 8px; margin-bottom: 12px; }
        button { background: #28a745; color: white; padding: 10px 20px; border: none; }
        .msg { color: green; text-align: center; }
    </style>
</head>
<body>

<h2 style="text-align:center;">Post your products</h2>

<?php if ($msg): ?>
    <p class="msg"><?= $msg ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Item Title" required>
    <textarea name="description" placeholder="Description" required></textarea>
    <select name="category">
        <option value="Phone">Phone</option>
        <option value="Computer">Computer</option>
        <option value="Car">Car</option>
        <option value="Spare Part">Spare Part</option>
    </select>
    <input type="number" name="price" placeholder="Price" step="1" required>
    <input type="file" name="image" accept="image/*" required>
    <button type="submit">Post Ad</button>
</form>

</body>
</html>