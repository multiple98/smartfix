<?php
session_start();
include('includes/db.php');

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: admin_login.php");
  exit();
}

// Check if product ID is passed
if (!isset($_GET['id'])) {
  echo "Product ID not found.";
  exit();
}

// Fetch the product data
$product_id = $_GET['id'];
$query = "SELECT * FROM products WHERE id = $product_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
  echo "Product not found.";
  exit();
}

$product = mysqli_fetch_assoc($result);

// Update product if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $description = mysqli_real_escape_string($conn, $_POST['description']);
  $price = $_POST['price'];
  $category = $_POST['category'];

  // Image upload (optional)
  $image_path = $product['image']; // Keep the existing image if not updated
  if ($_FILES['image']['name']) {
    $image_name = $_FILES['image']['name'];
    $tmp_name = $_FILES['image']['tmp_name'];
    $upload_dir = "uploads/";
    $image_path = $upload_dir . basename($image_name);
    move_uploaded_file($tmp_name, $image_path);
  }

  // Update query
  $query = "UPDATE products SET name = '$name', description = '$description', price = '$price', category = '$category', image = '$image_path' WHERE id = $product_id";
  
  if (mysqli_query($conn, $query)) {
    header("Location: admin_dashboard.php?success=1");
    exit();
  } else {
    echo "Database error: " . mysqli_error($conn);
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Product</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <h1>Edit Product</h1>

  <!-- Edit product form -->
  <form method="post" enctype="multipart/form-data">
    <label for="name">Product Name</label>
    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($product['name']); ?>" required><br><br>

    <label for="description">Description</label>
    <textarea name="description" id="description" required><?php echo htmlspecialchars($product['description']); ?></textarea><br><br>

    <label for="price">Price</label>
    <input type="number" name="price" id="price" value="<?php echo $product['price']; ?>" required><br><br>

    <label for="category">Category</label>
    <input type="text" name="category" id="category" value="<?php echo htmlspecialchars($product['category']); ?>" required><br><br>

    <label for="image">Product Image (optional)</label>
    <input type="file" name="image" id="image"><br><br>

    <input type="submit" value="Update Product">
  </form>

</body>
</html>
