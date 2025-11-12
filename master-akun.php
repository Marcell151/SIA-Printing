<?php
/**
 * File: master-akun.php
 * CRUD Master Akun
 * Hanya bisa diakses oleh Akuntan
 */
require_once 'config.php';
check_role(['akuntan', 'owner']);

$page_title = 'Master Akun';
$current_page = 'master-akun';

// ========== CREATE / UPDATE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Validasi input
        $kode_akun = strtoupper(clean_input($_POST['kode_akun']));
        $nama_akun = clean_input($_POST['nama_akun']);
        $tipe_akun = clean_input($_POST['tipe_akun']);
        $saldo = isset($_POST['saldo']) ? floatval(str_replace('.', '', $_POST['saldo'])) : 0;
        
        // Validasi format kode akun (harus X-XXX)
        if (!preg_match('/^[1-5]-\d{3}$/', $kode_akun)) {
            alert('Format kode akun harus X-XXX (contoh: 1-101, 4-201)', 'danger');
            redirect('master-akun.php');
            exit;
        }
        
        // Validasi tipe akun sesuai dengan digit pertama kode
        $digit_pertama = substr($kode_akun, 0, 1);
        $tipe_mapping = [
            '1' => '1-Aktiva',
            '2' => '2-Kewajiban',
            '3' => '3-Modal',
            '4' => '4-Pendapatan',
            '5' => '5-Beban'
        ];
        
        if (!isset($tipe_mapping[$digit_pertama]) || $tipe_mapping[$digit_pertama] != $tipe_akun) {
            alert('Tipe akun tidak sesuai dengan kode akun! Kode ' . $digit_pertama . ' harus bertipe ' . $tipe_mapping[$digit_pertama], 'danger');
            redirect('master-akun.php');
            exit;
        }
        
        if ($action == 'create') {
            // Cek duplikasi kode akun
            $cek = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '$kode_akun'");
            if ($cek->num_rows > 0) {
                alert('Kode akun sudah digunakan!', 'danger');
                redirect('master-akun.php');
                exit;
            }
            
            // Insert
            $stmt = $conn->prepare("INSERT INTO master_akun (kode_akun, nama_akun, tipe_akun, saldo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssd", $kode_akun, $nama_akun, $tipe_akun, $saldo);
            
            if ($stmt->execute()) {
                alert('Akun berhasil ditambahkan!', 'success');
            } else {
                alert('Gagal menambahkan akun: ' . $conn->error, 'danger');
            }
            
        } elseif ($action == 'update') {
            $id_akun = intval($_POST['id_akun']);
            
            // Cek duplikasi kode akun (kecuali untuk akun yang sedang diedit)
            $cek = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '$kode_akun' AND id_akun != $id_akun");
            if ($cek->num_rows > 0) {
                alert('Kode akun sudah digunakan oleh akun lain!', 'danger');
                redirect('master-akun.php');
                exit;
            }
            
            // Update
            $stmt = $conn->prepare("UPDATE master_akun SET kode_akun = ?, nama_akun = ?, tipe_akun = ?, saldo = ? WHERE id_akun = ?");
            $stmt->bind_param("sssdi", $kode_akun, $nama_akun, $tipe_akun, $saldo, $id_akun);
            
            if ($stmt->execute()) {
                alert('Akun berhasil diupdate!', 'success');
            } else {
                alert('Gagal mengupdate akun: ' . $conn->error, 'danger');
            }
        }
        
        redirect('master-akun.php');
    }
}

// ========== DELETE ==========
if (isset($_GET['delete'])) {
    $id_akun = intval($_GET['delete']);
    
    // Cek apakah akun sudah digunakan di jurnal
    $cek_jurnal = $conn->query("SELECT COUNT(*) as total FROM jurnal_umum 
                                WHERE id_akun_debit = $id_akun OR id_akun_kredit = $id_akun");
    $total = $cek_jurnal->fetch_assoc()['total'];
    
    if ($total > 0) {
        alert('Akun tidak dapat dihapus karena sudah digunakan di ' . $total . ' transaksi jurnal!', 'danger');
    } else {
        $conn->query("DELETE FROM master_akun WHERE id_akun = $id_akun");
        alert('Akun berhasil dihapus!', 'success');
    }
    
    redirect('master-akun.php');
}

// ========== READ ==========
$filter_tipe = isset($_GET['filter_tipe']) ? $_GET['filter_tipe'] : '';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

$query = "SELECT * FROM master_akun WHERE 1=1";
if ($filter_tipe) {
    $query .= " AND tipe_akun = '$filter_tipe'";
}
if ($search) {
    $query .= " AND (kode_akun LIKE '%$search%' OR nama_akun LIKE '%$search%')";
}
$query .= " ORDER BY kode_akun ASC";

$result = $conn->query($query);

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-list-ul me-2"></i>Master Akun</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Master Akun</li>
        </ol>
    </nav>
</div>

<!-- Action Bar -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <select name="filter_tipe" class="form-select">
                            <option value="">Semua Tipe</option>
                            <option value="1-Aktiva" <?php echo ($filter_tipe == '1-Aktiva') ? 'selected' : ''; ?>>1 - Aktiva</option>
                            <option value="2-Kewajiban" <?php echo ($filter_tipe == '2-Kewajiban') ? 'selected' : ''; ?>>2 - Kewajiban</option>
                            <option value="3-Modal" <?php echo ($filter_tipe == '3-Modal') ? 'selected' : ''; ?>>3 - Modal</option>
                            <option value="4-Pendapatan" <?php echo ($filter_tipe == '4-Pendapatan') ? 'selected' : ''; ?>>4 - Pendapatan</option>
                            <option value="5-Beban" <?php echo ($filter_tipe == '5-Beban') ? 'selected' : ''; ?>>5 - Beban</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cari kode atau nama akun..." 
                               value="<?php echo $search; ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Cari
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Akun
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Table Master Akun -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="bi bi-table me-2"></i>Daftar Akun
            <?php if ($filter_tipe): ?>
                - <?php echo $filter_tipe; ?>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="15%">Kode Akun</th>
                        <th width="35%">Nama Akun</th>
                        <th width="20%">Tipe Akun</th>
                        <th width="15%" class="text-end">Saldo (Rp)</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php 
                        $no = 1;
                        $current_tipe = '';
                        while ($row = $result->fetch_assoc()): 
                            // Grouping by tipe
                            if ($current_tipe != $row['tipe_akun'] && !$filter_tipe) {
                                $current_tipe = $row['tipe_akun'];
                        ?>
                                <tr class="table-secondary">
                                    <td colspan="6" class="fw-bold">
                                        <i class="bi bi-folder2-open me-2"></i>
                                        <?php echo $current_tipe; ?>
                                    </td>
                                </tr>
                        <?php } ?>
                            <tr>
                                <td class="text-center"><?php echo $no++; ?></td>
                                <td><strong><?php echo $row['kode_akun']; ?></strong></td>
                                <td><?php echo $row['nama_akun']; ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo (strpos($row['tipe_akun'], '1-') !== false) ? 'bg-primary' :
                                             ((strpos($row['tipe_akun'], '2-') !== false) ? 'bg-danger' :
                                             ((strpos($row['tipe_akun'], '3-') !== false) ? 'bg-success' :
                                             ((strpos($row['tipe_akun'], '4-') !== false) ? 'bg-info' : 'bg-warning')));
                                    ?>">
                                        <?php echo $row['tipe_akun']; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php echo format_rupiah($row['saldo']); ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            onclick="editAkun(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="hapusAkun(<?php echo $row['id_akun']; ?>, '<?php echo $row['kode_akun']; ?>', '<?php echo $row['nama_akun']; ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                Tidak ada data akun
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Akun -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Akun Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Akun <span class="text-danger">*</span></label>
                        <input type="text" name="kode_akun" class="form-control" 
                               placeholder="Contoh: 1-104, 4-105" 
                               pattern="[1-5]-\d{3}" 
                               title="Format: X-XXX (contoh: 1-104)"
                               required>
                        <small class="text-muted">Format: X-XXX (X = 1-5, XXX = 3 digit angka)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Akun <span class="text-danger">*</span></label>
                        <input type="text" name="nama_akun" class="form-control" 
                               placeholder="Contoh: Kas Kecil" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipe Akun <span class="text-danger">*</span></label>
                        <select name="tipe_akun" class="form-select" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="1-Aktiva">1 - Aktiva (Aset)</option>
                            <option value="2-Kewajiban">2 - Kewajiban (Hutang)</option>
                            <option value="3-Modal">3 - Modal (Ekuitas)</option>
                            <option value="4-Pendapatan">4 - Pendapatan (Revenue)</option>
                            <option value="5-Beban">5 - Beban (Expense)</option>
                        </select>
                        <small class="text-muted">Tipe harus sesuai dengan digit pertama kode akun</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Saldo Awal</label>
                        <input type="text" name="saldo" class="form-control currency" 
                               placeholder="0" value="0">
                        <small class="text-muted">Opsional - biarkan 0 jika akun baru</small>
                    </div>
                    
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Perhatian:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Kode akun harus unik (tidak boleh duplikat)</li>
                            <li>Digit pertama kode harus sesuai dengan tipe akun</li>
                            <li>Contoh: Kode 1-xxx harus bertipe "1-Aktiva"</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Akun -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>Edit Akun
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_akun" id="edit_id_akun">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Akun <span class="text-danger">*</span></label>
                        <input type="text" name="kode_akun" id="edit_kode_akun" class="form-control" 
                               pattern="[1-5]-\d{3}" 
                               title="Format: X-XXX (contoh: 1-104)"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Akun <span class="text-danger">*</span></label>
                        <input type="text" name="nama_akun" id="edit_nama_akun" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipe Akun <span class="text-danger">*</span></label>
                        <select name="tipe_akun" id="edit_tipe_akun" class="form-select" required>
                            <option value="1-Aktiva">1 - Aktiva (Aset)</option>
                            <option value="2-Kewajiban">2 - Kewajiban (Hutang)</option>
                            <option value="3-Modal">3 - Modal (Ekuitas)</option>
                            <option value="4-Pendapatan">4 - Pendapatan (Revenue)</option>
                            <option value="5-Beban">5 - Beban (Expense)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Saldo</label>
                        <input type="text" name="saldo" id="edit_saldo" class="form-control currency" 
                               placeholder="0">
                        <small class="text-muted">Hati-hati mengubah saldo manual</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-1"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Format currency input
document.querySelectorAll('.currency').forEach(function(input) {
    input.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        if (!value) {
            this.value = '';
            return;
        }
        this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    });
    
    // Saat form submit, hapus titik
    input.form.addEventListener('submit', function() {
        input.value = input.value.replace(/\./g, '');
    });
});

// Function edit akun
function editAkun(data) {
    document.getElementById('edit_id_akun').value = data.id_akun;
    document.getElementById('edit_kode_akun').value = data.kode_akun;
    document.getElementById('edit_nama_akun').value = data.nama_akun;
    document.getElementById('edit_tipe_akun').value = data.tipe_akun;
    document.getElementById('edit_saldo').value = data.saldo.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    
    var modalEdit = new bootstrap.Modal(document.getElementById('modalEdit'));
    modalEdit.show();
}

// Function hapus akun
function hapusAkun(id, kode, nama) {
    if (confirm('Apakah Anda yakin ingin menghapus akun:\n\n' + kode + ' - ' + nama + '\n\nAkun yang sudah digunakan di transaksi tidak dapat dihapus.')) {
        window.location.href = 'master-akun.php?delete=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?>