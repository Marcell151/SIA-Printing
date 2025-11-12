<?php
require_once 'config.php';
check_role(['kasir']);

$page_title = 'Pendapatan Kredit (Piutang)';
$current_page = 'pendapatan-kredit';

// Get master data
$pelanggan = $conn->query("SELECT * FROM master_pelanggan ORDER BY nama_pelanggan");

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = clean_input($_POST['tanggal']);
    $id_pelanggan = clean_input($_POST['id_pelanggan']);
    $jenis_jasa = clean_input($_POST['jenis_jasa']);
    $kategori = clean_input($_POST['kategori']);
    $total = floatval($_POST['total']);
    $dibayar = floatval($_POST['dibayar']);
    $sisa = $total - $dibayar;
    $jatuh_tempo = clean_input($_POST['jatuh_tempo']);
    $keterangan = clean_input($_POST['keterangan']);
    $created_by = $_SESSION['user_id'];
    
    // Generate nomor piutang
    $no_piutang = generate_no_piutang();
    
    // Status
    $status = ($sisa <= 0) ? 'Lunas' : 'Belum Lunas';
    
    // Insert piutang
    $stmt = $conn->prepare("INSERT INTO piutang 
            (no_piutang, tanggal, id_pelanggan, jenis_jasa, kategori, total, dibayar, sisa, jatuh_tempo, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissdddss i", $no_piutang, $tanggal, $id_pelanggan, $jenis_jasa, $kategori, $total, $dibayar, $sisa, $jatuh_tempo, $status, $created_by);
    
    if ($stmt->execute()) {
        $id_piutang = $stmt->insert_id;
        
        // Get akun ID
        $piutang_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'")->fetch_assoc()['id_akun'];
        
        // Determine pendapatan account
        $kode_pendapatan = '';
        switch($kategori) {
            case 'Printing': $kode_pendapatan = '4-101'; break;
            case 'Fotocopy': $kode_pendapatan = '4-102'; break;
            case 'Jilid': $kode_pendapatan = '4-103'; break;
            default: $kode_pendapatan = '4-101';
        }
        
        $pendapatan_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '$kode_pendapatan'")->fetch_assoc()['id_akun'];
        $kas_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'")->fetch_assoc()['id_akun'];
        
        // Create jurnal entry: Debit Piutang, Kredit Pendapatan
        $deskripsi = "Pendapatan Kredit - $jenis_jasa - $no_piutang";
        insert_jurnal($tanggal, $deskripsi, $piutang_akun, $pendapatan_akun, $total, $no_piutang, "Pendapatan Kredit");
        
        // Jika ada DP, buat jurnal: Debit Kas, Kredit Piutang
        if ($dibayar > 0) {
            $deskripsi_dp = "DP Piutang - $no_piutang";
            insert_jurnal($tanggal, $deskripsi_dp, $kas_akun, $piutang_akun, $dibayar, $no_piutang, "DP Piutang");
        }
        
        alert("Transaksi pendapatan kredit berhasil disimpan! No. Piutang: $no_piutang", 'success');
        redirect('pendapatan-kredit.php');
    } else {
        alert('Gagal menyimpan transaksi: ' . $conn->error, 'danger');
    }
}

// Get recent piutang
$recent = $conn->query("SELECT p.*, mp.nama_pelanggan 
                        FROM piutang p
                        LEFT JOIN master_pelanggan mp ON p.id_pelanggan = mp.id_pelanggan
                        ORDER BY p.created_at DESC LIMIT 10");

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-credit-card me-2"></i>Pendapatan Kredit (Piutang)</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Pendapatan Kredit</li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Form Input -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Input Transaksi Kredit</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formPiutang">
                    <div class="mb-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
                        <select name="id_pelanggan" class="form-select" required>
                            <option value="">-- Pilih Pelanggan --</option>
                            <?php while ($p = $pelanggan->fetch_assoc()): ?>
                                <option value="<?php echo $p['id_pelanggan']; ?>">
                                    <?php echo $p['nama_pelanggan']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jenis Jasa <span class="text-danger">*</span></label>
                        <input type="text" name="jenis_jasa" class="form-control" 
                               placeholder="Contoh: Cetak Banner 2x3 meter" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="kategori" class="form-select" required>
                            <option value="Printing">Printing</option>
                            <option value="Fotocopy">Fotocopy</option>
                            <option value="Jilid">Jilid</option>
                            <option value="Laminasi">Laminasi</option>
                            <option value="Desain">Desain</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Total (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="total" id="total_piutang" class="form-control" 
                               placeholder="0" required min="0" step="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">DP / Dibayar (Rp)</label>
                        <input type="number" name="dibayar" id="dibayar_piutang" class="form-control" 
                               placeholder="0" value="0" min="0" step="100">
                    </div>

                    <div class="mb-3">
                        <div class="alert alert-info">
                            <strong>Sisa Piutang:</strong>
                            <span id="sisa_piutang" class="fw-bold">Rp 0</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jatuh Tempo <span class="text-danger">*</span></label>
                        <input type="date" name="jatuh_tempo" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" 
                                  placeholder="Keterangan tambahan (opsional)"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-2"></i>Simpan Transaksi
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Recent Piutang -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Piutang Terakhir</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No. Piutang</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Sisa</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent->num_rows > 0): ?>
                                <?php while ($row = $recent->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $row['no_piutang']; ?></strong></td>
                                        <td><?php echo format_tanggal($row['tanggal']); ?></td>
                                        <td>
                                            <?php echo $row['nama_pelanggan']; ?><br>
                                            <small class="text-muted"><?php echo $row['jenis_jasa']; ?></small>
                                        </td>
                                        <td class="text-end fw-bold">
                                            <?php echo format_rupiah($row['total']); ?>
                                        </td>
                                        <td class="text-end">
                                            <?php echo format_rupiah($row['sisa']); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo ($row['status'] == 'Lunas') ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada data piutang
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>