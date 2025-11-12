<?php
/**
 * Database Configuration
 * SIA Revenue Cycle - Jasa Printing
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sia_printing');

// Application Configuration
define('APP_NAME', 'SIA Printing');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/sia-printing/');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Helper Functions
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function alert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type // success, danger, warning, info
    ];
}

function show_alert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $alertClass = 'alert-' . $alert['type'];
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($alert['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['alert']);
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function check_login() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function check_role($allowed_roles) {
    if (!is_logged_in()) {
        redirect('login.php');
    }
    
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        alert('Anda tidak memiliki akses ke halaman ini!', 'danger');
        redirect('dashboard.php');
    }
}

// Tambahkan fungsi baru di config.php setelah function check_role

// Check if user can edit (only akuntan)
function can_edit() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'akuntan';
}

// Check if user can view (owner and akuntan)
function can_view_reports() {
    return isset($_SESSION['user_role']) && 
           in_array($_SESSION['user_role'], ['owner', 'akuntan']);
}

// Check if user can view accounting (owner can view, akuntan can edit)
function can_view_accounting() {
    return isset($_SESSION['user_role']) && 
           in_array($_SESSION['user_role'], ['owner', 'akuntan']);
}

// Fungsi VOID jurnal (soft delete dengan audit trail)
function void_jurnal($id_jurnal, $void_reason) {
    global $conn;
    
    if (empty($void_reason)) {
        return ['success' => false, 'message' => 'Alasan void wajib diisi!'];
    }
    
    // Get jurnal data
    $query = "SELECT * FROM jurnal_umum WHERE id_jurnal = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_jurnal);
    $stmt->execute();
    $jurnal = $stmt->get_result()->fetch_assoc();
    
    if (!$jurnal) {
        return ['success' => false, 'message' => 'Data jurnal tidak ditemukan!'];
    }
    
    if ($jurnal['is_void'] == 1) {
        return ['success' => false, 'message' => 'Jurnal ini sudah di-void sebelumnya!'];
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // 1. Set void flag
        $stmt = $conn->prepare("UPDATE jurnal_umum 
                                SET is_void = 1, 
                                    voided_by = ?, 
                                    voided_at = NOW(), 
                                    void_reason = ? 
                                WHERE id_jurnal = ?");
        $stmt->bind_param("isi", $_SESSION['user_id'], $void_reason, $id_jurnal);
        $stmt->execute();
        
        // 2. Kembalikan saldo akun (reverse)
        // Debit dikurangi, Kredit ditambah (kebalikan dari saat input)
        $nominal = $jurnal['nominal'];
        $id_akun_debit = $jurnal['id_akun_debit'];
        $id_akun_kredit = $jurnal['id_akun_kredit'];
        
        // Get tipe akun untuk tahu cara update saldo
        $akun_debit = $conn->query("SELECT tipe_akun FROM master_akun WHERE id_akun = $id_akun_debit")->fetch_assoc();
        $akun_kredit = $conn->query("SELECT tipe_akun FROM master_akun WHERE id_akun = $id_akun_kredit")->fetch_assoc();
        
        // Reverse debit
        if (strpos($akun_debit['tipe_akun'], '1-Aktiva') !== false || strpos($akun_debit['tipe_akun'], '5-Beban') !== false) {
            // Aktiva/Beban: dikurangi saat void
            $conn->query("UPDATE master_akun SET saldo = saldo - $nominal WHERE id_akun = $id_akun_debit");
        } else {
            // Kewajiban/Modal/Pendapatan: ditambah saat void
            $conn->query("UPDATE master_akun SET saldo = saldo + $nominal WHERE id_akun = $id_akun_debit");
        }
        
        // Reverse kredit
        if (strpos($akun_kredit['tipe_akun'], '1-Aktiva') !== false || strpos($akun_kredit['tipe_akun'], '5-Beban') !== false) {
            // Aktiva/Beban: ditambah saat void
            $conn->query("UPDATE master_akun SET saldo = saldo + $nominal WHERE id_akun = $id_akun_kredit");
        } else {
            // Kewajiban/Modal/Pendapatan: dikurangi saat void
            $conn->query("UPDATE master_akun SET saldo = saldo - $nominal WHERE id_akun = $id_akun_kredit");
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Jurnal berhasil di-void!'];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Gagal void jurnal: ' . $e->getMessage()];
    }
}

// Fungsi VOID jurnal penyesuaian
function void_jurnal_penyesuaian($id_penyesuaian, $void_reason) {
    global $conn;
    
    if (empty($void_reason)) {
        return ['success' => false, 'message' => 'Alasan void wajib diisi!'];
    }
    
    // Get data
    $query = "SELECT * FROM jurnal_penyesuaian WHERE id_penyesuaian = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_penyesuaian);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    if (!$data) {
        return ['success' => false, 'message' => 'Data tidak ditemukan!'];
    }
    
    if ($data['is_void'] == 1) {
        return ['success' => false, 'message' => 'Jurnal penyesuaian ini sudah di-void!'];
    }
    
    $conn->begin_transaction();
    
    try {
        // 1. Set void flag di jurnal_penyesuaian
        $stmt = $conn->prepare("UPDATE jurnal_penyesuaian 
                                SET is_void = 1, 
                                    voided_by = ?, 
                                    voided_at = NOW(), 
                                    void_reason = ? 
                                WHERE id_penyesuaian = ?");
        $stmt->bind_param("isi", $_SESSION['user_id'], $void_reason, $id_penyesuaian);
        $stmt->execute();
        
        // 2. Void entry di jurnal_umum yang terkait (berdasarkan referensi)
        $referensi = 'AJE-' . $id_penyesuaian;
        $stmt = $conn->prepare("UPDATE jurnal_umum 
                                SET is_void = 1, 
                                    voided_by = ?, 
                                    voided_at = NOW(), 
                                    void_reason = ? 
                                WHERE referensi = ?");
        $stmt->bind_param("iss", $_SESSION['user_id'], $void_reason, $referensi);
        $stmt->execute();
        
        // 3. Reverse saldo akun (sama seperti void_jurnal)
        $nominal = $data['nominal'];
        $id_akun_debit = $data['id_akun_debit'];
        $id_akun_kredit = $data['id_akun_kredit'];
        
        $akun_debit = $conn->query("SELECT tipe_akun FROM master_akun WHERE id_akun = $id_akun_debit")->fetch_assoc();
        $akun_kredit = $conn->query("SELECT tipe_akun FROM master_akun WHERE id_akun = $id_akun_kredit")->fetch_assoc();
        
        // Reverse debit
        if (strpos($akun_debit['tipe_akun'], '1-Aktiva') !== false || strpos($akun_debit['tipe_akun'], '5-Beban') !== false) {
            $conn->query("UPDATE master_akun SET saldo = saldo - $nominal WHERE id_akun = $id_akun_debit");
        } else {
            $conn->query("UPDATE master_akun SET saldo = saldo + $nominal WHERE id_akun = $id_akun_debit");
        }
        
        // Reverse kredit
        if (strpos($akun_kredit['tipe_akun'], '1-Aktiva') !== false || strpos($akun_kredit['tipe_akun'], '5-Beban') !== false) {
            $conn->query("UPDATE master_akun SET saldo = saldo + $nominal WHERE id_akun = $id_akun_kredit");
        } else {
            $conn->query("UPDATE master_akun SET saldo = saldo - $nominal WHERE id_akun = $id_akun_kredit");
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Jurnal penyesuaian berhasil di-void!'];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Gagal void: ' . $e->getMessage()];
    }
}

function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function format_tanggal($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $pecah = explode('-', $tanggal);
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

function generate_no_piutang() {
    global $conn;
    $tahun = date('Y');
    $bulan = date('m');
    
    // Get last number
    $query = "SELECT no_piutang FROM piutang 
              WHERE no_piutang LIKE 'INV-$tahun$bulan%' 
              ORDER BY id_piutang DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastNo = intval(substr($row['no_piutang'], -4));
        $newNo = $lastNo + 1;
    } else {
        $newNo = 1;
    }
    
    return 'INV-' . $tahun . $bulan . str_pad($newNo, 4, '0', STR_PAD_LEFT);
}

// Auto insert to Jurnal Umum
function insert_jurnal($tanggal, $deskripsi, $id_akun_debit, $id_akun_kredit, $nominal, $referensi = '', $tipe_transaksi = '') {
    global $conn;
    
    // Insert jurnal
    $stmt = $conn->prepare("INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiidss", $tanggal, $deskripsi, $id_akun_debit, $id_akun_kredit, $nominal, $referensi, $tipe_transaksi);
    $stmt->execute();
    
    // Update saldo akun debit (tambah)
    $conn->query("UPDATE master_akun SET saldo = saldo + $nominal WHERE id_akun = $id_akun_debit");
    
    // Update saldo akun kredit (kurang untuk aktiva, tambah untuk pasiva/pendapatan/beban)
    $akun_kredit = $conn->query("SELECT tipe_akun FROM master_akun WHERE id_akun = $id_akun_kredit")->fetch_assoc();
    
    if (strpos($akun_kredit['tipe_akun'], '1-Aktiva') !== false) {
        // Jika kredit adalah aktiva, kurangi saldo
        $conn->query("UPDATE master_akun SET saldo = saldo - $nominal WHERE id_akun = $id_akun_kredit");
    } else {
        // Jika kredit adalah kewajiban/modal/pendapatan, tambah saldo
        $conn->query("UPDATE master_akun SET saldo = saldo + $nominal WHERE id_akun = $id_akun_kredit");
    }
    
    return $stmt->insert_id;
}

// Sanitize input
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}
?>