<?php
require_once 'config.php';
check_role(['akuntan', 'owner']);

$page_title = 'Buku Besar Setelah Penyesuaian';
$current_page = 'buku-besar-penyesuaian';

// Get list of accounts
$accounts = $conn->query("SELECT * FROM master_akun ORDER BY kode_akun");

// Filter
$id_akun = isset($_GET['id_akun']) ? $_GET['id_akun'] : '';
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

$akun_info = null;
$transaksi = [];
$saldo_awal = 0;

if ($id_akun) {
    // Get account info
    $akun_info = $conn->query("SELECT * FROM master_akun WHERE id_akun = $id_akun")->fetch_assoc();
    
    // Get saldo awal (saldo sebelum periode) - TERMASUK PENYESUAIAN PERIODE SEBELUMNYA
    $bulan_sebelum = date('Y-m-d', strtotime($bulan . '-01 -1 day'));
    
    // Calculate saldo awal from jurnal (TERMASUK jurnal penyesuaian)
    $debit_sebelum = $conn->query("SELECT COALESCE(SUM(nominal), 0) as total 
                                   FROM jurnal_umum 
                                   WHERE id_akun_debit = $id_akun 
                                   AND tanggal <= '$bulan_sebelum'")->fetch_assoc()['total'];
    
    $kredit_sebelum = $conn->query("SELECT COALESCE(SUM(nominal), 0) as total 
                                    FROM jurnal_umum 
                                    WHERE id_akun_kredit = $id_akun 
                                    AND tanggal <= '$bulan_sebelum'")->fetch_assoc()['total'];
    
    // Determine saldo awal based on account type
    $tipe = $akun_info['tipe_akun'];
    if (strpos($tipe, '1-Aktiva') !== false || strpos($tipe, '5-Beban') !== false) {
        $saldo_awal = $debit_sebelum - $kredit_sebelum;
    } else {
        $saldo_awal = $kredit_sebelum - $debit_sebelum;
    }
    
    // Get ALL transactions for the period (INCLUDING adjusting entries)
    $query = "
        SELECT 'Debit' as posisi, tanggal, deskripsi, nominal, referensi, tipe_transaksi
        FROM jurnal_umum
        WHERE id_akun_debit = $id_akun
        AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'
        
        UNION ALL
        
        SELECT 'Kredit' as posisi, tanggal, deskripsi, nominal, referensi, tipe_transaksi
        FROM jurnal_umum
        WHERE id_akun_kredit = $id_akun
        AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'
        
        ORDER BY tanggal ASC, tipe_transaksi ASC
    ";
    
    $result = $conn->query($query);
    
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
    <h1><i class="bi bi-book-half me-2"></i>Buku Besar Setelah Penyesuaian</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Buku Besar Setelah Penyesuaian</li>
        </ol>
    </nav>
</div>

<!-- Info Alert -->
<div class="alert alert-info no-print">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Catatan:</strong> Buku besar ini menampilkan saldo SETELAH jurnal penyesuaian. 
    Saldo akhir di sini sama dengan saldo di Neraca Saldo Setelah Penyesuaian.
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-5">
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
            <div class="col-md-4">
                <label class="form-label">Periode</label>
                <input type="month" name="bulan" class="form-control" value="<?php echo $bulan; ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
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
    <div class="card-header bg-success text-white">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-0">BUKU BESAR SETELAH PENYESUAIAN</h5>
            </div>
            <div class="col-md-6 text-end">
                <?php 
                $nama_bulan = array(
                    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                );
                $pecah_bulan = explode('-', $bulan);
                echo $nama_bulan[$pecah_bulan[1]] . ' ' . $pecah_bulan[0];
                ?>
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
                    <p class="mb-1 small text-muted">Saldo Awal Periode (Setelah Penyesuaian Periode Lalu)</p>
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
                        <th width="25%">Keterangan</th>
                        <th width="10%">Jenis</th>
                        <th width="13%" class="text-end">Debit</th>
                        <th width="13%" class="text-end">Kredit</th>
                        <th width="19%" class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-secondary">
                        <td colspan="6" class="text-end fw-bold">Saldo Awal</td>
                        <td class="text-end fw-bold"><?php echo format_rupiah($saldo_awal); ?></td>
                    </tr>
                    
                    <?php if (count($transaksi) > 0): ?>
                        <?php foreach ($transaksi as $tr): ?>
                            <tr <?php echo ($tr['tipe_transaksi'] == 'Jurnal Penyesuaian') ? 'class="table-warning"' : ''; ?>>
                                <td><?php echo date('d/m/Y', strtotime($tr['tanggal'])); ?></td>
                                <td><small class="text-muted"><?php echo $tr['referensi']; ?></small></td>
                                <td><?php echo $tr['deskripsi']; ?></td>
                                <td>
                                    <?php if ($tr['tipe_transaksi'] == 'Jurnal Penyesuaian'): ?>
                                        <span class="badge bg-warning text-dark">Penyesuaian</span>
                                    <?php else: ?>
                                        <small class="text-muted"><?php echo $tr['tipe_transaksi']; ?></small>
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
                        
                        <tr class="table-success">
                            <td colspan="6" class="text-end fw-bold">SALDO AKHIR (Setelah Penyesuaian)</td>
                            <td class="text-end fw-bold">
                                <?php echo format_rupiah(end($transaksi)['saldo']); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Tidak ada transaksi pada periode ini
                            </td>
                        </tr>
                        <tr class="table-success">
                            <td colspan="6" class="text-end fw-bold">SALDO AKHIR</td>
                            <td class="text-end fw-bold"><?php echo format_rupiah($saldo_awal); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Info Box -->
<div class="row mt-4 no-print">
    <div class="col-md-12">
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Tentang Buku Besar Setelah Penyesuaian</h6>
                <p class="small mb-2">
                    Buku besar setelah penyesuaian menampilkan SEMUA transaksi termasuk jurnal penyesuaian. 
                    Saldo akhir di buku besar ini akan sama dengan saldo di Neraca Saldo Setelah Penyesuaian.
                </p>
                <div class="row small">
                    <div class="col-md-6">
                        <strong>Perbedaan dengan Buku Besar Biasa:</strong>
                        <ul class="mb-0">
                            <li><strong>Buku Besar:</strong> Hanya transaksi sebelum penyesuaian</li>
                            <li><strong>Buku Besar Setelah Penyesuaian:</strong> Termasuk jurnal penyesuaian (ditandai kuning)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Fungsi:</strong>
                        <ul class="mb-0">
                            <li>Menampilkan saldo final setelah adjustment</li>
                            <li>Dasar untuk pembuatan laporan keuangan</li>
                            <li>Audit trail lengkap termasuk penyesuaian</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-search display-1 text-muted d-block mb-3"></i>
        <h5 class="text-muted">Pilih akun untuk menampilkan buku besar setelah penyesuaian</h5>
        <p class="text-muted">Buku besar ini menampilkan saldo termasuk jurnal penyesuaian</p>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>