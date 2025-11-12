<?php
require_once 'config.php';
check_role(['kasir']);

$page_title = 'Pendapatan Tunai';
$current_page = 'pendapatan-tunai';

// Get master data
$pelanggan = $conn->query("SELECT * FROM master_pelanggan ORDER BY nama_pelanggan");
$jasa = $conn->query("SELECT * FROM master_jasa ORDER BY kategori, nama_jasa");

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = clean_input($_POST['tanggal']);
    $id_pelanggan = clean_input($_POST['id_pelanggan']);
    $jenis_jasa = clean_input($_POST['jenis_jasa']);
    $kategori = clean_input($_POST['kategori']);
    $jumlah = floatval($_POST['jumlah']);
    $metode = clean_input($_POST['metode_pembayaran']);
    $keterangan = clean_input($_POST['keterangan']);
    $created_by = $_SESSION['user_id'];
    
    // Insert transaksi pendapatan
    $stmt = $conn->prepare("INSERT INTO transaksi_pendapatan 
            (tanggal, id_pelanggan, jenis_jasa, kategori, jumlah, metode_pembayaran, keterangan, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissdssi", $tanggal, $id_pelanggan, $jenis_jasa, $kategori, $jumlah, $metode, $keterangan, $created_by);
    
    if ($stmt->execute()) {
        $id_transaksi = $stmt->insert_id;
        
        // Get akun ID
        $kas_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'")->fetch_assoc()['id_akun'];
        
        // Determine pendapatan account based on kategori
        $kode_pendapatan = '';
        switch($kategori) {
            case 'Printing': $kode_pendapatan = '4-101'; break;
            case 'Fotocopy': $kode_pendapatan = '4-102'; break;
            case 'Jilid': $kode_pendapatan = '4-103'; break;
            default: $kode_pendapatan = '4-101';
        }
        
        $pendapatan_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '$kode_pendapatan'")->fetch_assoc()['id_akun'];
        
        // Create jurnal entry: Debit Kas, Kredit Pendapatan
        $deskripsi = "Pendapatan Tunai - $jenis_jasa";
        insert_jurnal($tanggal, $deskripsi, $kas_akun, $pendapatan_akun, $jumlah, "PT-$id_transaksi", "Pendapatan Tunai");
        
        alert('Transaksi pendapatan tunai berhasil disimpan dan jurnal otomatis dibuat!', 'success');
        redirect('pendapatan-tunai.php');
    } else {
        alert('Gagal menyimpan transaksi: ' . $conn->error, 'danger');
    }
}

// Get recent transactions
$recent = $conn->query("SELECT tp.*, mp.nama_pelanggan 
                        FROM transaksi_pendapatan tp
                        LEFT JOIN master_pelanggan mp ON tp.id_pelanggan = mp.id_pelanggan
                        ORDER BY tp.created_at DESC LIMIT 10");

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-cash-coin me-2"></i>Pendapatan Tunai</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Pendapatan Tunai</li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Form Input -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Input Transaksi</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
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
                               placeholder="Contoh: Cetak A4 Warna 100 lembar" required 
                               list="jasa-list">
                        <datalist id="jasa-list">
                            <?php 
                            $jasa->data_seek(0);
                            while ($j = $jasa->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $j['nama_jasa']; ?>">
                            <?php endwhile; ?>
                        </datalist>
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
                        <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah" class="form-control" 
                               placeholder="0" required min="0" step="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                        <select name="metode_pembayaran" class="form-select" required>
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="E-Wallet">E-Wallet</option>
                            <option value="QRIS">QRIS</option>
                        </select>
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
                                <th>Pelanggan</th>
                                <th>Jenis Jasa</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent->num_rows > 0): ?>
                                <?php while ($row = $recent->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_tanggal($row['tanggal']); ?></td>
                                        <td><?php echo $row['nama_pelanggan']; ?></td>
                                        <td>
                                            <small class="text-muted"><?php echo $row['kategori']; ?></small><br>
                                            <?php echo $row['jenis_jasa']; ?>
                                        </td>
                                        <td class="text-end fw-bold">
                                            <?php echo format_rupiah($row['jumlah']); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Belum ada transaksi
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