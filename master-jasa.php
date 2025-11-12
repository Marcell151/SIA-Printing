<?php
/**
 * File: master-jasa.php (NEW FILE)
 * CRUD Master Jasa / Layanan
 */
require_once 'config.php';
check_role(['kasir', 'akuntan', 'owner']);

$page_title = 'Master Jasa / Layanan';
$current_page = 'master-jasa';

// ========== CREATE / UPDATE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        $nama_jasa = clean_input($_POST['nama_jasa']);
        $kategori = clean_input($_POST['kategori']);
        $harga_satuan = floatval(str_replace('.', '', $_POST['harga_satuan']));
        
        // Validasi
        if (empty($nama_jasa)) {
            alert('Nama jasa wajib diisi!', 'danger');
            redirect('master-jasa.php');
            exit;
        }
        
        if ($action == 'create') {
            // Cek duplikasi nama
            $cek = $conn->query("SELECT id_jasa FROM master_jasa WHERE nama_jasa = '$nama_jasa' AND kategori = '$kategori'");
            if ($cek->num_rows > 0) {
                alert('Jasa dengan nama dan kategori yang sama sudah terdaftar!', 'warning');
                redirect('master-jasa.php');
                exit;
            }
            
            // Insert
            $stmt = $conn->prepare("INSERT INTO master_jasa (nama_jasa, kategori, harga_satuan) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $nama_jasa, $kategori, $harga_satuan);
            
            if ($stmt->execute()) {
                alert('Jasa berhasil ditambahkan!', 'success');
            } else {
                alert('Gagal menambahkan jasa: ' . $conn->error, 'danger');
            }
            
        } elseif ($action == 'update') {
            $id_jasa = intval($_POST['id_jasa']);
            
            // Cek duplikasi nama (kecuali untuk jasa yang sedang diedit)
            $cek = $conn->query("SELECT id_jasa FROM master_jasa WHERE nama_jasa = '$nama_jasa' AND kategori = '$kategori' AND id_jasa != $id_jasa");
            if ($cek->num_rows > 0) {
                alert('Jasa dengan nama dan kategori yang sama sudah digunakan!', 'warning');
                redirect('master-jasa.php');
                exit;
            }
            
            // Update
            $stmt = $conn->prepare("UPDATE master_jasa SET nama_jasa = ?, kategori = ?, harga_satuan = ? WHERE id_jasa = ?");
            $stmt->bind_param("ssdi", $nama_jasa, $kategori, $harga_satuan, $id_jasa);
            
            if ($stmt->execute()) {
                alert('Data jasa berhasil diupdate!', 'success');
            } else {
                alert('Gagal mengupdate jasa: ' . $conn->error, 'danger');
            }
        }
        
        redirect('master-jasa.php');
    }
}

// ========== DELETE ==========
if (isset($_GET['delete'])) {
    $id_jasa = intval($_GET['delete']);
    
    // Cek apakah jasa sudah digunakan di transaksi
    $cek_transaksi = $conn->query("SELECT COUNT(*) as total FROM transaksi_pendapatan WHERE jenis_jasa LIKE (SELECT CONCAT('%', nama_jasa, '%') FROM master_jasa WHERE id_jasa = $id_jasa)");
    $total = $cek_transaksi->fetch_assoc()['total'];
    
    if ($total > 0) {
        alert('Jasa tidak dapat dihapus karena sudah digunakan di ' . $total . ' transaksi!', 'danger');
    } else {
        $conn->query("DELETE FROM master_jasa WHERE id_jasa = $id_jasa");
        alert('Jasa berhasil dihapus!', 'success');
    }
    
    redirect('master-jasa.php');
}

// ========== READ ==========
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$filter_kategori = isset($_GET['filter_kategori']) ? $_GET['filter_kategori'] : '';

$query = "SELECT * FROM master_jasa WHERE 1=1";

if ($search) {
    $query .= " AND nama_jasa LIKE '%$search%'";
}

if ($filter_kategori) {
    $query .= " AND kategori = '$filter_kategori'";
}

$query .= " ORDER BY kategori ASC, nama_jasa ASC";

$result = $conn->query($query);

// Get distinct kategori untuk filter
$kategori_list = $conn->query("SELECT DISTINCT kategori FROM master_jasa ORDER BY kategori");

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-briefcase me-2"></i>Master Jasa / Layanan</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Master Jasa</li>
        </ol>
    </nav>
</div>

<!-- Action Bar -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cari nama jasa..." 
                               value="<?php echo $search; ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="filter_kategori" class="form-select">
                            <option value="">Semua Kategori</option>
                            <?php while ($kat = $kategori_list->fetch_assoc()): ?>
                                <option value="<?php echo $kat['kategori']; ?>" 
                                        <?php echo ($filter_kategori == $kat['kategori']) ? 'selected' : ''; ?>>
                                    <?php echo $kat['kategori']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
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
                    <i class="bi bi-plus-circle me-1"></i>Tambah Jasa
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Table Master Jasa -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="bi bi-table me-2"></i>Daftar Jasa / Layanan
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="40%">Nama Jasa</th>
                        <th width="20%">Kategori</th>
                        <th width="20%" class="text-end">Harga Satuan</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php 
                        $no = 1;
                        $current_kategori = '';
                        while ($row = $result->fetch_assoc()): 
                            // Grouping by kategori
                            if ($current_kategori != $row['kategori'] && !$filter_kategori) {
                                $current_kategori = $row['kategori'];
                        ?>
                                <tr class="table-secondary">
                                    <td colspan="5" class="fw-bold">
                                        <i class="bi bi-tag me-2"></i>
                                        <?php echo $current_kategori; ?>
                                    </td>
                                </tr>
                        <?php } ?>
                            <tr>
                                <td class="text-center"><?php echo $no++; ?></td>
                                <td><?php echo $row['nama_jasa']; ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo ($row['kategori'] == 'Printing') ? 'bg-primary' :
                                             (($row['kategori'] == 'Fotocopy') ? 'bg-info' :
                                             (($row['kategori'] == 'Jilid') ? 'bg-warning' : 'bg-success'));
                                    ?>">
                                        <?php echo $row['kategori']; ?>
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">
                                    <?php echo ($row['harga_satuan'] > 0) ? format_rupiah($row['harga_satuan']) : '-'; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            onclick="editJasa(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="hapusJasa(<?php echo $row['id_jasa']; ?>, '<?php echo $row['nama_jasa']; ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                Tidak ada data jasa
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Jasa -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Jasa Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Jasa <span class="text-danger">*</span></label>
                        <input type="text" name="nama_jasa" class="form-control" 
                               placeholder="Contoh: Cetak A4 Warna" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="kategori" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Printing">Printing</option>
                            <option value="Fotocopy">Fotocopy</option>
                            <option value="Jilid">Jilid</option>
                            <option value="Laminasi">Laminasi</option>
                            <option value="Desain">Desain</option>
                        </select>
                        <small class="text-muted">Atau ketik kategori baru</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Harga Satuan (Rp)</label>
                        <input type="text" name="harga_satuan" class="form-control currency-input" 
                               placeholder="0">
                        <small class="text-muted">Opsional - biarkan 0 jika harga bervariasi</small>
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

<!-- Modal Edit Jasa -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>Edit Jasa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_jasa" id="edit_id_jasa">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Jasa <span class="text-danger">*</span></label>
                        <input type="text" name="nama_jasa" id="edit_nama_jasa" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="kategori" id="edit_kategori" class="form-select" required>
                            <option value="Printing">Printing</option>
                            <option value="Fotocopy">Fotocopy</option>
                            <option value="Jilid">Jilid</option>
                            <option value="Laminasi">Laminasi</option>
                            <option value="Desain">Desain</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Harga Satuan (Rp)</label>
                        <input type="text" name="harga_satuan" id="edit_harga_satuan" 
                               class="form-control currency-input" placeholder="0">
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
// Format currency input dengan titik ribuan
document.querySelectorAll('.currency-input').forEach(function(input) {
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

// Function edit jasa
function editJasa(data) {
    document.getElementById('edit_id_jasa').value = data.id_jasa;
    document.getElementById('edit_nama_jasa').value = data.nama_jasa;
    document.getElementById('edit_kategori').value = data.kategori;
    document.getElementById('edit_harga_satuan').value = data.harga_satuan.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    
    var modalEdit = new bootstrap.Modal(document.getElementById('modalEdit'));
    modalEdit.show();
}

// Function hapus jasa
function hapusJasa(id, nama) {
    if (confirm('Apakah Anda yakin ingin menghapus jasa:\n\n' + nama + '\n\nJasa yang sudah digunakan di transaksi tidak dapat dihapus.')) {
        window.location.href = 'master-jasa.php?delete=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?>