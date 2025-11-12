<?php
/**
 * File: master-pelanggan.php
 * CRUD Master Pelanggan
 */
require_once 'config.php';
check_role(['kasir', 'akuntan', 'owner']);

$page_title = 'Master Pelanggan';
$current_page = 'master-pelanggan';

// ========== CREATE / UPDATE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        $nama_pelanggan = clean_input($_POST['nama_pelanggan']);
        $alamat = clean_input($_POST['alamat']);
        $telepon = clean_input($_POST['telepon']);
        $email = clean_input($_POST['email']);
        
        // Validasi
        if (empty($nama_pelanggan)) {
            alert('Nama pelanggan wajib diisi!', 'danger');
            redirect('master-pelanggan.php');
            exit;
        }
        
        // Validasi email format (jika diisi)
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            alert('Format email tidak valid!', 'danger');
            redirect('master-pelanggan.php');
            exit;
        }
        
        // Validasi telepon (minimal 10 digit jika diisi)
        if (!empty($telepon) && strlen($telepon) < 10) {
            alert('Nomor telepon minimal 10 digit!', 'danger');
            redirect('master-pelanggan.php');
            exit;
        }
        
        if ($action == 'create') {
            // Cek duplikasi nama
            $cek = $conn->query("SELECT id_pelanggan FROM master_pelanggan WHERE nama_pelanggan = '$nama_pelanggan'");
            if ($cek->num_rows > 0) {
                alert('Nama pelanggan sudah terdaftar!', 'warning');
                redirect('master-pelanggan.php');
                exit;
            }
            
            // Insert
            $stmt = $conn->prepare("INSERT INTO master_pelanggan (nama_pelanggan, alamat, telepon, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nama_pelanggan, $alamat, $telepon, $email);
            
            if ($stmt->execute()) {
                alert('Pelanggan berhasil ditambahkan!', 'success');
            } else {
                alert('Gagal menambahkan pelanggan: ' . $conn->error, 'danger');
            }
            
        } elseif ($action == 'update') {
            $id_pelanggan = intval($_POST['id_pelanggan']);
            
            // Cek duplikasi nama (kecuali untuk pelanggan yang sedang diedit)
            $cek = $conn->query("SELECT id_pelanggan FROM master_pelanggan WHERE nama_pelanggan = '$nama_pelanggan' AND id_pelanggan != $id_pelanggan");
            if ($cek->num_rows > 0) {
                alert('Nama pelanggan sudah digunakan pelanggan lain!', 'warning');
                redirect('master-pelanggan.php');
                exit;
            }
            
            // Update
            $stmt = $conn->prepare("UPDATE master_pelanggan SET nama_pelanggan = ?, alamat = ?, telepon = ?, email = ? WHERE id_pelanggan = ?");
            $stmt->bind_param("ssssi", $nama_pelanggan, $alamat, $telepon, $email, $id_pelanggan);
            
            if ($stmt->execute()) {
                alert('Data pelanggan berhasil diupdate!', 'success');
            } else {
                alert('Gagal mengupdate pelanggan: ' . $conn->error, 'danger');
            }
        }
        
        redirect('master-pelanggan.php');
    }
}

// ========== DELETE ==========
if (isset($_GET['delete'])) {
    $id_pelanggan = intval($_GET['delete']);
    
    // Cek apakah pelanggan sudah ada transaksi
    $cek_transaksi = $conn->query("SELECT COUNT(*) as total FROM piutang WHERE id_pelanggan = $id_pelanggan");
    $total = $cek_transaksi->fetch_assoc()['total'];
    
    if ($total > 0) {
        alert('Pelanggan tidak dapat dihapus karena sudah memiliki ' . $total . ' transaksi piutang!', 'danger');
    } else {
        $conn->query("DELETE FROM master_pelanggan WHERE id_pelanggan = $id_pelanggan");
        alert('Pelanggan berhasil dihapus!', 'success');
    }
    
    redirect('master-pelanggan.php');
}

// ========== READ ==========
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

$query = "SELECT *, 
          (SELECT COUNT(*) FROM piutang WHERE id_pelanggan = master_pelanggan.id_pelanggan) as jumlah_transaksi,
          (SELECT COALESCE(SUM(total), 0) FROM piutang WHERE id_pelanggan = master_pelanggan.id_pelanggan) as total_transaksi
          FROM master_pelanggan 
          WHERE 1=1";

if ($search) {
    $query .= " AND (nama_pelanggan LIKE '%$search%' OR telepon LIKE '%$search%' OR alamat LIKE '%$search%')";
}

$query .= " ORDER BY nama_pelanggan ASC";

$result = $conn->query($query);

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-people me-2"></i>Master Pelanggan</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Master Pelanggan</li>
        </ol>
    </nav>
</div>

<!-- Action Bar -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cari nama, telepon, atau alamat..." 
                               value="<?php echo $search; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Cari
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Pelanggan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Table Master Pelanggan -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="bi bi-table me-2"></i>Daftar Pelanggan
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="20%">Nama Pelanggan</th>
                        <th width="25%">Alamat</th>
                        <th width="12%">Telepon</th>
                        <th width="15%">Email</th>
                        <th width="8%" class="text-center">Transaksi</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php 
                        $no = 1;
                        while ($row = $result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo $row['nama_pelanggan']; ?></strong>
                                    <?php if ($row['jumlah_transaksi'] > 0): ?>
                                        <br><small class="badge bg-info"><?php echo $row['jumlah_transaksi']; ?> transaksi</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['alamat'] ?: '-'; ?></td>
                                <td>
                                    <?php if ($row['telepon']): ?>
                                        <i class="bi bi-telephone"></i> <?php echo $row['telepon']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['email']): ?>
                                        <i class="bi bi-envelope"></i> <?php echo $row['email']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['total_transaksi'] > 0): ?>
                                        <span class="text-success fw-bold"><?php echo format_rupiah($row['total_transaksi']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            onclick='editPelanggan(<?php echo json_encode($row); ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="hapusPelanggan(<?php echo $row['id_pelanggan']; ?>, '<?php echo $row['nama_pelanggan']; ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php if ($row['jumlah_transaksi'] > 0): ?>
                                        <a href="kartu-piutang.php?id_pelanggan=<?php echo $row['id_pelanggan']; ?>" 
                                           class="btn btn-sm btn-info" title="Lihat Piutang">
                                            <i class="bi bi-card-text"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                Tidak ada data pelanggan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Pelanggan -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Pelanggan Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_pelanggan" class="form-control" 
                               placeholder="Nama lengkap pelanggan" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" 
                                  placeholder="Alamat lengkap (opsional)"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="telepon" class="form-control" 
                               placeholder="08xx-xxxx-xxxx" 
                               pattern="[0-9+\-\s]+" 
                               minlength="10">
                        <small class="text-muted">Minimal 10 digit</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               placeholder="email@example.com">
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

<!-- Modal Edit Pelanggan -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>Edit Pelanggan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_pelanggan" id="edit_id_pelanggan">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_pelanggan" id="edit_nama_pelanggan" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" id="edit_alamat" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="telepon" id="edit_telepon" class="form-control" 
                               pattern="[0-9+\-\s]+" minlength="10">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
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
// Function edit pelanggan
function editPelanggan(data) {
    document.getElementById('edit_id_pelanggan').value = data.id_pelanggan;
    document.getElementById('edit_nama_pelanggan').value = data.nama_pelanggan;
    document.getElementById('edit_alamat').value = data.alamat || '';
    document.getElementById('edit_telepon').value = data.telepon || '';
    document.getElementById('edit_email').value = data.email || '';
    
    var modalEdit = new bootstrap.Modal(document.getElementById('modalEdit'));
    modalEdit.show();
}

// Function hapus pelanggan
function hapusPelanggan(id, nama) {
    if (confirm('Apakah Anda yakin ingin menghapus pelanggan:\n\n' + nama + '\n\nPelanggan yang sudah memiliki transaksi tidak dapat dihapus.')) {
        window.location.href = 'master-pelanggan.php?delete=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?>