<?php
require_once 'config.php';
check_role(['akuntan']);

$page_title = 'Transaksi Umum';
$current_page = 'transaksi-umum';

// Get accounts
$accounts = $conn->query("SELECT * FROM master_akun ORDER BY kode_akun");

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $tanggal = clean_input($_POST['tanggal']);
    $tipe_transaksi = clean_input($_POST['tipe_transaksi']);
    $id_akun_debit = clean_input($_POST['id_akun_debit']);
    $id_akun_kredit = clean_input($_POST['id_akun_kredit']);
    $nominal = floatval($_POST['nominal']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $referensi = clean_input($_POST['referensi']);
    $created_by = $_SESSION['user_id'];
    
    if ($id_akun_debit == $id_akun_kredit) {
        alert('Akun debit dan kredit tidak boleh sama!', 'danger');
    } else {
        // Insert jurnal umum
        $result = insert_jurnal($tanggal, $deskripsi, $id_akun_debit, $id_akun_kredit, $nominal, $referensi, $tipe_transaksi);
        
        if ($result) {
            alert('Transaksi berhasil disimpan dan jurnal otomatis dibuat!', 'success');
            redirect('transaksi-umum.php');
        } else {
            alert('Gagal menyimpan transaksi: ' . $conn->error, 'danger');
        }
    }
}

// Get recent transactions
$recent = $conn->query("SELECT ju.*, 
                        ma_debit.kode_akun as kode_debit, ma_debit.nama_akun as nama_debit,
                        ma_kredit.kode_akun as kode_kredit, ma_kredit.nama_akun as nama_kredit
                        FROM jurnal_umum ju
                        JOIN master_akun ma_debit ON ju.id_akun_debit = ma_debit.id_akun
                        JOIN master_akun ma_kredit ON ju.id_akun_kredit = ma_kredit.id_akun
                        WHERE ju.tipe_transaksi IN ('Modal Awal', 'Pembelian Aset', 'Beban', 'Prive')
                        ORDER BY ju.created_at DESC LIMIT 15");

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-journal-plus me-2"></i>Transaksi Umum</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Transaksi Umum</li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Form Input -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
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
                        <label class="form-label">Tipe Transaksi <span class="text-danger">*</span></label>
                        <select name="tipe_transaksi" id="tipeTransaksi" class="form-select" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="Modal Awal">Setoran Modal Awal</option>
                            <option value="Pembelian Aset">Pembelian Aset (Perlengkapan/Peralatan)</option>
                            <option value="Beban">Pembayaran Beban</option>
                            <option value="Prive">Pengambilan Prive</option>
                        </select>
                    </div>

                    <div id="quickGuide" class="alert alert-info small" style="display: none;">
                        <strong>Panduan:</strong>
                        <div id="guideContent"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Akun Debit <span class="text-danger">*</span></label>
                        <select name="id_akun_debit" id="akunDebit" class="form-select" required>
                            <option value="">-- Pilih Akun Debit --</option>
                            <?php 
                            $accounts->data_seek(0);
                            $current_tipe = '';
                            while ($acc = $accounts->fetch_assoc()): 
                                if ($current_tipe != $acc['tipe_akun']) {
                                    if ($current_tipe != '') echo '</optgroup>';
                                    echo '<optgroup label="' . $acc['tipe_akun'] . '">';
                                    $current_tipe = $acc['tipe_akun'];
                                }
                            ?>
                                <option value="<?php echo $acc['id_akun']; ?>">
                                    <?php echo $acc['kode_akun'] . ' - ' . $acc['nama_akun']; ?>
                                </option>
                            <?php endwhile; ?>
                            </optgroup>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Akun Kredit <span class="text-danger">*</span></label>
                        <select name="id_akun_kredit" id="akunKredit" class="form-select" required>
                            <option value="">-- Pilih Akun Kredit --</option>
                            <?php 
                            $accounts->data_seek(0);
                            $current_tipe = '';
                            while ($acc = $accounts->fetch_assoc()): 
                                if ($current_tipe != $acc['tipe_akun']) {
                                    if ($current_tipe != '') echo '</optgroup>';
                                    echo '<optgroup label="' . $acc['tipe_akun'] . '">';
                                    $current_tipe = $acc['tipe_akun'];
                                }
                            ?>
                                <option value="<?php echo $acc['id_akun']; ?>" data-kode="<?php echo $acc['kode_akun']; ?>">
                                    <?php echo $acc['kode_akun'] . ' - ' . $acc['nama_akun']; ?>
                                </option>
                            <?php endwhile; ?>
                            </optgroup>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="nominal" class="form-control" 
                               placeholder="0" required min="0" step="1">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Referensi</label>
                        <input type="text" name="referensi" class="form-control" 
                               placeholder="Contoh: MODAL-001, BUY-001">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                        <textarea name="deskripsi" class="form-control" rows="3" 
                                  placeholder="Jelaskan transaksi secara detail" required></textarea>
                    </div>

                    <button type="submit" name="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-2"></i>Simpan Transaksi
                    </button>
                </form>
            </div>
        </div>

        <!-- Panduan Lengkap -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-book me-2"></i>Panduan Transaksi</h6>
            </div>
            <div class="card-body">
                <div class="accordion" id="guideAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#modal">
                                1. Setoran Modal Awal
                            </button>
                        </h2>
                        <div id="modal" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                            <div class="accordion-body small">
                                <p><strong>Debit:</strong> Kas (1-101)</p>
                                <p><strong>Kredit:</strong> Modal Pemilik (3-101)</p>
                                <p><strong>Contoh:</strong> Owner menyetor uang Rp 100.000.000 sebagai modal awal usaha</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#aset">
                                2. Pembelian Aset
                            </button>
                        </h2>
                        <div id="aset" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                            <div class="accordion-body small">
                                <p><strong>Perlengkapan:</strong></p>
                                <p>Debit: Perlengkapan (1-103), Kredit: Kas (1-101)</p>
                                <p><strong>Peralatan:</strong></p>
                                <p>Debit: Peralatan (1-201), Kredit: Kas (1-101)</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#beban">
                                3. Pembayaran Beban
                            </button>
                        </h2>
                        <div id="beban" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                            <div class="accordion-body small">
                                <p><strong>Debit:</strong> Beban Gaji/Sewa/Listrik (5-xxx)</p>
                                <p><strong>Kredit:</strong> Kas (1-101)</p>
                                <p>Pilih akun beban sesuai jenis pengeluaran</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#prive">
                                4. Pengambilan Prive
                            </button>
                        </h2>
                        <div id="prive" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                            <div class="accordion-body small">
                                <p><strong>Debit:</strong> Prive (3-102)</p>
                                <p><strong>Kredit:</strong> Kas (1-101)</p>
                                <p>Untuk keperluan pribadi pemilik</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Transaksi Terakhir</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Tipe</th>
                                <th>Deskripsi</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent->num_rows > 0): ?>
                                <?php while ($row = $recent->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                    echo ($row['tipe_transaksi'] == 'Modal Awal') ? 'bg-success' : 
                                                         (($row['tipe_transaksi'] == 'Pembelian Aset') ? 'bg-primary' : 
                                                         (($row['tipe_transaksi'] == 'Beban') ? 'bg-warning' : 'bg-danger')); 
                                                ?>">
                                                <?php echo $row['tipe_transaksi']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted d-block">
                                                D: <?php echo $row['kode_debit']; ?> | 
                                                K: <?php echo $row['kode_kredit']; ?>
                                            </small>
                                            <?php echo substr($row['deskripsi'], 0, 50); ?>
                                            <?php echo strlen($row['deskripsi']) > 50 ? '...' : ''; ?>
                                        </td>
                                        <td class="text-end fw-bold">
                                            <?php echo format_rupiah($row['nominal']); ?>
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

<script>
// Auto-suggest accounts based on transaction type
document.getElementById('tipeTransaksi').addEventListener('change', function() {
    const tipe = this.value;
    const guideDiv = document.getElementById('quickGuide');
    const guideContent = document.getElementById('guideContent');
    
    if (tipe) {
        guideDiv.style.display = 'block';
        
        switch(tipe) {
            case 'Modal Awal':
                guideContent.innerHTML = 'Debit: <strong>Kas (1-101)</strong><br>Kredit: <strong>Modal Pemilik (3-101)</strong>';
                break;
            case 'Pembelian Aset':
                guideContent.innerHTML = 'Debit: <strong>Perlengkapan (1-103)</strong> atau <strong>Peralatan (1-201)</strong><br>Kredit: <strong>Kas (1-101)</strong>';
                break;
            case 'Beban':
                guideContent.innerHTML = 'Debit: <strong>Beban [pilih sesuai jenis] (5-xxx)</strong><br>Kredit: <strong>Kas (1-101)</strong>';
                break;
            case 'Prive':
                guideContent.innerHTML = 'Debit: <strong>Prive (3-102)</strong><br>Kredit: <strong>Kas (1-101)</strong>';
                break;
        }
    } else {
        guideDiv.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>