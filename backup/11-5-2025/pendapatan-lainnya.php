<?php
require_once 'config.php';
check_role(['kasir']);

$page_title = 'Pendapatan Lainnya';
$current_page = 'pendapatan-lainnya';

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = clean_input($_POST['tanggal']);
    $sumber_pendapatan = clean_input($_POST['sumber_pendapatan']);
    $jumlah = floatval($_POST['jumlah']);
    $keterangan = clean_input($_POST['keterangan']);
    $created_by = $_SESSION['user_id'];
    
    // Insert pendapatan lainnya
    $stmt = $conn->prepare("INSERT INTO pendapatan_lainnya 
            (tanggal, sumber_pendapatan, jumlah, keterangan, created_by) 
            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsi", $tanggal, $sumber_pendapatan, $jumlah, $keterangan, $created_by);
    
    if ($stmt->execute()) {
        $id_pendapatan = $stmt->insert_id;
        
        // Get akun ID
        $kas_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'")->fetch_assoc()['id_akun'];
        $pendapatan_lain_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '4-104'")->fetch_assoc()['id_akun'];
        
        // Create jurnal entry: Debit Kas, Kredit Pendapatan Lain-Lain
        $deskripsi = "Pendapatan Lain-Lain - $sumber_pendapatan";
        insert_jurnal($tanggal, $deskripsi, $kas_akun, $pendapatan_lain_akun, $jumlah, "PL-$id_pendapatan", "Pendapatan Lainnya");
        
        alert('Pendapatan lainnya berhasil disimpan!', 'success');
        redirect('pendapatan-lainnya.php');
    } else {
        alert('Gagal menyimpan transaksi: ' . $conn->error, 'danger');
    }
}

// Get recent transactions
$recent = $conn->query("SELECT * FROM pendapatan_lainnya ORDER BY created_at DESC LIMIT 10");

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-wallet2 me-2"></i>Pendapatan Lainnya</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Pendapatan Lainnya</li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Form Input -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Input Pendapatan Lainnya</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sumber Pendapatan <span class="text-danger">*</span></label>
                        <input type="text" name="sumber_pendapatan" class="form-control" 
                               placeholder="Contoh: Sewa Peralatan, Bunga Bank, dll" required
                               list="sumber-list">
                        <datalist id="sumber-list">
                            <option value="Sewa Peralatan">
                            <option value="Bunga Bank">
                            <option value="Penjualan Aset">
                            <option value="Komisi">
                            <option value="Bonus">
                            <option value="Hadiah">
                        </datalist>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah" class="form-control" 
                               placeholder="0" required min="0" step="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan <span class="text-danger">*</span></label>
                        <textarea name="keterangan" class="form-control" rows="3" 
                                  placeholder="Deskripsi detail pendapatan" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-2"></i>Simpan Transaksi
                    </button>
                </form>
            </div>
        </div>

        <!-- Info Box -->
        <div class="card mt-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Informasi</h6>
                <p class="small text-muted mb-2">
                    Pendapatan lainnya adalah pendapatan yang diterima diluar kegiatan usaha utama, seperti:
                </p>
                <ul class="small text-muted">
                    <li>Sewa peralatan atau ruangan</li>
                    <li>Bunga bank atau investasi</li>
                    <li>Penjualan aset bekas</li>
                    <li>Komisi atau bonus</li>
                    <li>Pendapatan insidental lainnya</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Transaksi Terakhir</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Sumber Pendapatan</th>
                                <th>Keterangan</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent->num_rows > 0): ?>
                                <?php while ($row = $recent->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_tanggal($row['tanggal']); ?></td>
                                        <td>
                                            <strong><?php echo $row['sumber_pendapatan']; ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo substr($row['keterangan'], 0, 50); ?>
                                                <?php echo strlen($row['keterangan']) > 50 ? '...' : ''; ?>
                                            </small>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            <?php echo format_rupiah($row['jumlah']); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                        Belum ada transaksi
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Summary Card -->
        <?php
        $bulan_ini = date('Y-m');
        $total_bulan_ini = $conn->query("SELECT COALESCE(SUM(jumlah), 0) as total 
                                          FROM pendapatan_lainnya 
                                          WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'")
                                  ->fetch_assoc()['total'];
        $total_semua = $conn->query("SELECT COALESCE(SUM(jumlah), 0) as total FROM pendapatan_lainnya")
                            ->fetch_assoc()['total'];
        ?>
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Ringkasan</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <p class="text-muted small mb-1">Bulan Ini</p>
                        <h4 class="mb-0 fw-bold text-success">
                            <?php echo format_rupiah($total_bulan_ini); ?>
                        </h4>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small mb-1">Total Keseluruhan</p>
                        <h4 class="mb-0 fw-bold text-primary">
                            <?php echo format_rupiah($total_semua); ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>