<?php
require_once 'config.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    
    // Query user
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            
            alert('Login berhasil! Selamat datang ' . $user['nama'], 'success');
            redirect('dashboard.php');
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gradient">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="login-icon mb-3">
                                <i class="bi bi-calculator-fill text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h2 class="fw-bold text-dark"><?php echo APP_NAME; ?></h2>
                            <p class="text-muted">Sistem Informasi Akuntansi</p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-person-fill text-muted"></i>
                                    </span>
                                    <input type="text" name="username" class="form-control border-start-0 ps-0" 
                                           placeholder="Masukkan username" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-lock-fill text-muted"></i>
                                    </span>
                                    <input type="password" name="password" class="form-control border-start-0 ps-0" 
                                           placeholder="Masukkan password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 fw-semibold rounded-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </button>
                        </form>

                        <div class="mt-4 p-3 bg-light rounded-3">
                            <p class="mb-2 fw-semibold text-dark small">Demo Akun:</p>
                            <div class="small text-muted">
                                <div class="mb-1"><strong>Kasir:</strong> kasir / password</div>
                                <!-- <div class="mb-1"><strong>Akuntan:</strong> akuntan / password</div> -->
                                <div><strong>Admin/Owner:</strong> owner / password</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="text-center text-white mt-3 small">
                    &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>