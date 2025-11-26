<?php
require_once 'config.php';
check_role(['akuntan', 'owner']);

$page_title = 'Buku Besar';
$current_page = 'buku-besar';

// Get list of accounts
$accounts = $conn->query("SELECT * FROM master_akun ORDER BY kode_akun");

// Filter - HANYA AKUN, TANPA PERIODE
$id_akun = isset($_GET['id_akun']) ? intval($_GET['id_akun']) : 0;

$akun_info = null;
$transaksi = [];
$saldo_awal = 0;

if ($id_akun) {
    // Get account info
    $akun_info = $conn->query("SELECT * FROM master_akun WHERE id_akun = $id_akun")->fetch_assoc();
    
    // Saldo awal = 0 (karena menampilkan dari awal)
    $saldo_awal = 0;
    
    // Get ALL transactions untuk akun ini (TANPA filter periode)
    // EXCLUDE Jurnal Penyesuaian
    $query = "
        SELECT 'Debit' as posisi, tanggal, deskripsi, nominal, referensi, tipe_transaksi
        FROM jurnal_umum
        WHERE id_akun_debit = $id_akun
        AND (tipe_transaksi IS NULL OR tipe_transaksi != 'Jurnal Penyesuaian')
        
        UNION ALL
        
        SELECT 'Kredit' as posisi, tanggal, deskripsi, nominal, referensi, tipe_transaksi
        FROM jurnal_umum
        WHERE id_akun_kredit = $id_akun
        AND (tipe_transaksi IS NULL OR tipe_transaksi != 'Jurnal Penyesuaian')
        
        ORDER BY tanggal ASC, tipe_transaksi ASC
    ";
    
    $result = $conn->query($query);
    
    $tipe = $akun_info['tipe_akun'];
    $saldo_berjalan = $saldo_awal;
    
    while ($row = $result->fetch_assoc()) {
        if ($row['posisi'] == 'Debit') {
            if (strpos($tipe, '1-Aktiva') !== false || strpos($tipe, '5-Beban') !== false) {
                $saldo_berjalan += $row['nominal'];
            } else {
                $saldo_berjalan -= $row['nominal'];
            }
        } else {
            if (strpos($tipe, '1-Aktiva') !== false || strpos($tipe, '5-Beban') !== false) {
                $saldo_berjalan -= $row['nominal'];
            } else {
                $saldo_berjalan += $row['nominal'];
            }
        }
        
        $row['saldo'] = $saldo_berjalan;
        $transaksi[] = $row;
    }
}

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-book me-2"></i>Buku Besar</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Buku Besar</li>
        </ol>
    </nav>
</div>

<!-- Filter - HANYA AKUN -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-9">
                <label class="form-label">Pilih Akun <span class="text-danger">*</span></label>
                <select name="id_akun" class="form-select" required>
                    <option value="">-- Pilih Akun --</option>
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
                        <option value="<?php echo $acc['id_akun']; ?>" 
                                <?php echo ($id_akun == $acc['id_akun']) ? 'selected' : ''; ?>>
                            <?php echo $acc['kode_akun'] . ' - ' . $acc['nama_akun']; ?>
                        </option>
                    <?php endwhile; ?>
                    </optgroup>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2 w-100">
                    <i class="bi bi-search me-1"></i>Tampilkan
                </button>
                <?php if ($id_akun): ?>
                <button type="button" onclick="window.print()" class="btn btn-success no-print">
                    <i class="bi bi-printer me-1"></i>Cetak
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($akun_info): ?>
<!-- Buku Besar Table -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-0">BUKU BESAR (SEMUA PERIODE)</h5>
            </div>
            <div class="col-md-6 text-end">
                <small>Dari Awal s/d <?php echo format_tanggal(date('Y-m-d')); ?></small>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="120"><strong>Kode Akun</strong></td>
                        <td>: <?php echo $akun_info['kode_akun']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Nama Akun</strong></td>
                        <td>: <?php echo $akun_info['nama_akun']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tipe Akun</strong></td>
                        <td>: <?php echo $akun_info['tipe_akun']; ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6 text-end">
                <div class="p-3 bg-light rounded">
                    <p class="mb-1 small text-muted">Saldo Awal</p>
                    <h4 class="mb-0 fw-bold"><?php echo format_rupiah($saldo_awal); ?></h4>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="10%">Tanggal</th>
                        <th width="10%">Referensi</th>
                        <th width="30%">Keterangan</th>
                        <th width="15%" class="text-end">Debit</th>
                        <th width="15%" class="text-end">Kredit</th>
                        <th width="20%" class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-secondary">
                        <td colspan="5" class="text-end fw-bold">Saldo Awal</td>
                        <td class="text-end fw-bold"><?php echo format_rupiah($saldo_awal); ?></td>
                    </tr>
                    
                    <?php if (count($transaksi) > 0): ?>
                        <?php foreach ($transaksi as $tr): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($tr['tanggal'])); ?></td>
                                <td><small class="text-muted"><?php echo $tr['referensi']; ?></small></td>
                                <td>
                                    <?php echo $tr['deskripsi']; ?>
                                    <?php if ($tr['tipe_transaksi']): ?>
                                        <br><small class="badge bg-info"><?php echo $tr['tipe_transaksi']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo ($tr['posisi'] == 'Debit') ? format_rupiah($tr['nominal']) : '-'; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo ($tr['posisi'] == 'Kredit') ? format_rupiah($tr['nominal']) : '-'; ?>
                                </td>
                                <td class="text-end fw-bold">
                                    <?php echo format_rupiah($tr['saldo']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <tr class="table-primary">
                            <td colspan="5" class="text-end fw-bold">SALDO AKHIR (<?php echo date('d/m/Y'); ?>)</td>
                            <td class="text-end fw-bold">
                                <?php echo format_rupiah(end($transaksi)['saldo']); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Tidak ada transaksi untuk akun ini
                            </td>
                        </tr>
                        <tr class="table-primary">
                            <td colspan="5" class="text-end fw-bold">SALDO AKHIR</td>
                            <td class="text-end fw-bold"><?php echo format_rupiah($saldo_awal); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-search display-1 text-muted d-block mb-3"></i>
        <h5 class="text-muted">Pilih akun untuk menampilkan buku besar</h5>
        <p class="text-muted">Buku besar akan menampilkan <strong>semua transaksi dari awal hingga hari ini</strong></p>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>