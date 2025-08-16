<?php
include('includes/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $price = floatval($_POST['price']);
  $description = mysqli_real_escape_string($conn, $_POST['description']);
  $category = mysqli_real_escape_string($conn, $_POST['category']);

  $image_path = '';

  // Upload image
  if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $image_name = time() . '_' . basename($_FILES['image']['name']);
    $upload_dir = "uploads/";
    $image_path = $upload_dir . $image_name;

    move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
  }

  // Check if user_id column exists
  $check_user_id = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'user_id'");
  $user_id_exists = mysqli_num_rows($check_user_id) > 0;
  
  // Get admin user_id from session or default to 1
  $admin_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
  
  if ($user_id_exists) {
    $query = "INSERT INTO products (name, price, description, category, image, user_id, created_at)
              VALUES ('$name', $price, '$description', '$category', '$image_path', $admin_user_id, NOW())";
  } else {
    $query = "INSERT INTO products (name, price, description, category, image, created_at)
              VALUES ('$name', $price, '$description', '$category', '$image_path', NOW())";
  }

  if (mysqli_query($conn, $query)) {
    echo "✅ Product uploaded successfully!";
  } else {
    echo "❌ Error: " . mysqli_error($conn);
  }
}
?>
<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: admin_login.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmartFix - Upload Product</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #007bff;
      --success-color: #28a745;
      --danger-color: #dc3545;
      --warning-color: #ffc107;
      --light-color: #f8f9fa;
      --dark-color: #343a40;
      --border-color: #dee2e6;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 2rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .form-container {
      background: white;
      max-width: 600px;
      width: 100%;
      padding: 2rem;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .form-header {
      text-align: center;
      margin-bottom: 2rem;
      color: var(--dark-color);
    }

    .form-header h2 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }

    .form-header p {
      color: #6c757d;
      font-size: 0.9rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
      position: relative;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: var(--dark-color);
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 0.75rem;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: white;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
      outline: none;
    }

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    .file-input-wrapper {
      position: relative;
      overflow: hidden;
      border: 2px dashed var(--border-color);
      border-radius: 8px;
      padding: 2rem;
      text-align: center;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .file-input-wrapper:hover {
      border-color: var(--primary-color);
      background: rgba(0,123,255,0.05);
    }

    .file-input-wrapper.dragover {
      border-color: var(--success-color);
      background: rgba(40,167,69,0.1);
    }

    .file-input {
      position: absolute;
      left: -9999px;
    }

    .file-upload-text {
      color: #6c757d;
      font-size: 0.9rem;
    }

    .file-upload-icon {
      font-size: 3rem;
      color: var(--primary-color);
      margin-bottom: 1rem;
    }

    .file-preview {
      margin-top: 1rem;
      text-align: center;
      display: none;
    }

    .file-preview img {
      max-width: 200px;
      max-height: 200px;
      border-radius: 8px;
      border: 2px solid var(--border-color);
    }

    .file-info {
      margin-top: 0.5rem;
      font-size: 0.8rem;
      color: #6c757d;
    }

    .submit-btn {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(135deg, var(--primary-color), #0056b3);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0,123,255,0.3);
    }

    .submit-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .submit-btn .loading {
      display: none;
    }

    .submit-btn.loading .loading {
      display: inline-block;
    }

    .submit-btn.loading .normal-text {
      display: none;
    }

    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      border-left: 4px solid;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
      border-color: var(--success-color);
    }

    .alert-danger {
      background: #f8d7da;
      color: #721c24;
      border-color: var(--danger-color);
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      color: var(--primary-color);
      text-decoration: none;
      margin-top: 1rem;
      font-weight: 500;
      transition: color 0.3s;
    }

    .back-link:hover {
      color: #0056b3;
    }

    .back-link i {
      margin-right: 0.5rem;
    }

    .progress-bar {
      width: 100%;
      height: 4px;
      background: var(--light-color);
      border-radius: 2px;
      overflow: hidden;
      margin-top: 1rem;
      display: none;
    }

    .progress-bar .progress {
      height: 100%;
      background: linear-gradient(90deg, var(--primary-color), var(--success-color));
      width: 0%;
      transition: width 0.3s ease;
    }

    @media (max-width: 768px) {
      body {
        padding: 1rem;
      }

      .form-container {
        padding: 1.5rem;
      }

      .form-header h2 {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="form-container">
    <div class="form-header">
      <h2><i class="fas fa-plus-circle"></i> Add New Product</h2>
      <p>Upload product information and images to your store</p>
    </div>

    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($_GET['success']); ?>
      </div>
    <?php endif; ?>

    <form action="save_product.php" method="POST" enctype="multipart/form-data" id="productForm">
      <input type="hidden" name="csrf_token" value="<?php 
        require_once 'includes/SecurityManager.php';
        $security = new SecurityManager($pdo);
        echo htmlspecialchars($security->generateCSRFToken());
      ?>">

      <div class="form-group">
        <label for="name">
          <i class="fas fa-tag"></i> Product Name *
        </label>
        <input type="text" name="name" id="name" placeholder="Enter product name" required maxlength="255">
      </div>

      <div class="form-group">
        <label for="description">
          <i class="fas fa-align-left"></i> Description *
        </label>
        <textarea name="description" id="description" placeholder="Describe your product..." required maxlength="1000"></textarea>
      </div>

      <div class="form-group">
        <label for="price">
          <i class="fas fa-dollar-sign"></i> Price *
        </label>
        <input type="number" name="price" id="price" placeholder="0.00" step="0.01" min="0.01" required>
      </div>

      <div class="form-group">
        <label for="category">
          <i class="fas fa-list"></i> Category *
        </label>
        <select name="category" id="category" required>
          <option value="">Select Category</option>
          <option value="phone">📱 Phones</option>
          <option value="computer">💻 Computers</option>
          <option value="spare">🔧 Spare Parts</option>
          <option value="car">🚗 Cars</option>
        </select>
      </div>

      <div class="form-group">
        <label>
          <i class="fas fa-image"></i> Product Image *
        </label>
        <div class="file-input-wrapper" id="fileWrapper">
          <div class="file-upload-icon">
            <i class="fas fa-cloud-upload-alt"></i>
          </div>
          <div class="file-upload-text">
            <strong>Click to upload</strong> or drag and drop<br>
            <small>PNG, JPG or JPEG (Max: 5MB)</small>
          </div>
          <input type="file" name="image" id="image" class="file-input" accept="image/jpeg,image/jpg,image/png" required>
        </div>
        <div class="file-preview" id="filePreview"></div>
      </div>

      <div class="progress-bar" id="progressBar">
        <div class="progress" id="progress"></div>
      </div>

      <button type="submit" class="submit-btn" id="submitBtn">
        <span class="normal-text">
          <i class="fas fa-upload"></i> Upload Product
        </span>
        <span class="loading">
          <i class="fas fa-spinner fa-spin"></i> Uploading...
        </span>
      </button>
    </form>

    <a href="admin/admin_dashboard.php" class="back-link">
      <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
  </div>

  <script>
    const fileInput = document.getElementById('image');
    const fileWrapper = document.getElementById('fileWrapper');
    const filePreview = document.getElementById('filePreview');
    const form = document.getElementById('productForm');
    const submitBtn = document.getElementById('submitBtn');
    const progressBar = document.getElementById('progressBar');
    const progress = document.getElementById('progress');

    // File input click handler
    fileWrapper.addEventListener('click', () => {
      fileInput.click();
    });

    // Drag and drop handlers
    fileWrapper.addEventListener('dragover', (e) => {
      e.preventDefault();
      fileWrapper.classList.add('dragover');
    });

    fileWrapper.addEventListener('dragleave', () => {
      fileWrapper.classList.remove('dragover');
    });

    fileWrapper.addEventListener('drop', (e) => {
      e.preventDefault();
      fileWrapper.classList.remove('dragover');
      
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        handleFile(files[0]);
      }
    });

    // File input change handler
    fileInput.addEventListener('change', (e) => {
      if (e.target.files.length > 0) {
        handleFile(e.target.files[0]);
      }
    });

    function handleFile(file) {
      // Validate file type
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
      if (!allowedTypes.includes(file.type)) {
        alert('Please select a valid image file (JPG, JPEG, or PNG)');
        return;
      }

      // Validate file size (5MB)
      if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        return;
      }

      // Update the file input
      const dt = new DataTransfer();
      dt.items.add(file);
      fileInput.files = dt.files;

      // Show preview
      const reader = new FileReader();
      reader.onload = (e) => {
        filePreview.innerHTML = `
          <img src="${e.target.result}" alt="Preview">
          <div class="file-info">
            <strong>${file.name}</strong><br>
            Size: ${(file.size / 1024 / 1024).toFixed(2)} MB
          </div>
        `;
        filePreview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }

    // Form submission with progress
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      
      submitBtn.disabled = true;
      submitBtn.classList.add('loading');
      progressBar.style.display = 'block';

      const formData = new FormData(form);
      const xhr = new XMLHttpRequest();

      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const percentComplete = (e.loaded / e.total) * 100;
          progress.style.width = percentComplete + '%';
        }
      });

      xhr.onload = function() {
        if (xhr.status === 200) {
          try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
              window.location.href = response.redirect + '?success=' + encodeURIComponent(response.message);
            } else {
              alert('Error: ' + response.message);
              resetForm();
            }
          } catch (e) {
            // Handle non-JSON response (redirect)
            window.location.reload();
          }
        } else {
          alert('Upload failed. Please try again.');
          resetForm();
        }
      };

      xhr.onerror = function() {
        alert('Network error occurred. Please check your connection.');
        resetForm();
      };

      xhr.open('POST', 'save_product.php');
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.send(formData);
    });

    function resetForm() {
      submitBtn.disabled = false;
      submitBtn.classList.remove('loading');
      progressBar.style.display = 'none';
      progress.style.width = '0%';
    }

    // Form validation
    form.addEventListener('input', () => {
      const name = document.getElementById('name').value.trim();
      const description = document.getElementById('description').value.trim();
      const price = parseFloat(document.getElementById('price').value);
      const category = document.getElementById('category').value;
      const image = document.getElementById('image').files.length > 0;

      const isValid = name && description && price > 0 && category && image;
      submitBtn.disabled = !isValid;
    });
  </script>
</body>
</html>
