<?php
require_once 'config.php';
check_role(['akuntan', 'owner']);

$page_title = 'Jurnal Umum';
$current_page = 'jurnal-umum';

// Filter
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tipe = isset($_GET['tipe']) ? $_GET['tipe'] : '';

// Query jurnal umum
$query = "SELECT ju.*, 
          ma_debit.kode_akun as kode_debit, ma_debit.nama_akun as nama_debit,
          ma_kredit.kode_akun as kode_kredit, ma_kredit.nama_akun as nama_kredit
          FROM jurnal_umum ju
          JOIN master_akun ma_debit ON ju.id_akun_debit = ma_debit.id_akun
          JOIN master_akun ma_kredit ON ju.id_akun_kredit = ma_kredit.id_akun
          WHERE DATE_FORMAT(ju.tanggal, '%Y-%m') = '$bulan'";

if ($tipe) {
    $query .= " AND ju.tipe_transaksi = '$tipe'";
}

$query .= " ORDER BY ju.tanggal ASC, ju.id_jurnal ASC";

$result = $conn->query($query);

// Get total
$total_nominal = 0;
if ($result->num_rows > 0) {
    $temp_result = $conn->query($query);
    while ($row = $temp_result->fetch_assoc()) {
        $total_nominal += $row['nominal'];
    }
}

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-journal-text me-2"></i>Jurnal Umum</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Jurnal Umum</li>
        </ol>
    </nav>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Periode</label>
                <input type="month" name="bulan" class="form-control" value="<?php echo $bulan; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipe Transaksi</label>
                <select name="tipe" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="Pendapatan Tunai" <?php echo ($tipe == 'Pendapatan Tunai') ? 'selected' : ''; ?>>
                        Pendapatan Tunai
                    </option>
                    <option value="Pendapatan Kredit" <?php echo ($tipe == 'Pendapatan Kredit') ? 'selected' : ''; ?>>
                        Pendapatan Kredit
                    </option>
                    <option value="Penerimaan Piutang" <?php echo ($tipe == 'Penerimaan Piutang') ? 'selected' : ''; ?>>
                        Penerimaan Piutang
                    </option>
                    <option value="Pendapatan Lainnya" <?php echo ($tipe == 'Pendapatan Lainnya') ? 'selected' : ''; ?>>
                        Pendapatan Lainnya
                    </option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="jurnal-umum.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Jurnal Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-book me-2"></i>Daftar Jurnal Umum
            <?php 
            $nama_bulan = array(
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            );
            $pecah_bulan = explode('-', $bulan);
            echo '- ' . $nama_bulan[$pecah_bulan[1]] . ' ' . $pecah_bulan[0];
            ?>
        </h5>
        <button onclick="window.print()" class="btn btn-sm btn-success no-print">
            <i class="bi bi-printer me-1"></i>Cetak
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="10%" class="text-center">Tanggal</th>
                        <th width="10%">Referensi</th>
                        <th width="30%">Deskripsi</th>
                        <th width="15%">Debit</th>
                        <th width="15%">Kredit</th>
                        <th width="20%" class="text-end">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php 
                        $current_date = '';
                        while ($row = $result->fetch_assoc()): 
                            if ($current_date != $row['tanggal']) {
                                $current_date = $row['tanggal'];
                        ?>
                                <tr class="table-secondary">
                                    <td colspan="6" class="fw-bold">
                                        <i class="bi bi-calendar3 me-2"></i>
                                        <?php echo format_tanggal($row['tanggal']); ?>
                                    </td>
                                </tr>
                        <?php } ?>
                            <tr>
                                <td class="text-center"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                <td>
                                    <small class="text-muted"><?php echo $row['referensi']; ?></small>
                                </td>
                                <td>
                                    <?php echo $row['deskripsi']; ?>
                                    <?php if ($row['tipe_transaksi']): ?>
                                        <br><small class="badge bg-info"><?php echo $row['tipe_transaksi']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $row['kode_debit']; ?></strong>
                                    <br><small class="text-muted"><?php echo $row['nama_debit']; ?></small>
                                </td>
                                <td>
                                    <strong><?php echo $row['kode_kredit']; ?></strong>
                                    <br><small class="text-muted"><?php echo $row['nama_kredit']; ?></small>
                                </td>
                                <td class="text-end fw-bold">
                                    <?php echo format_rupiah($row['nominal']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        
                        <tr class="table-primary">
                            <td colspan="5" class="text-end fw-bold">TOTAL TRANSAKSI:</td>
                            <td class="text-end fw-bold"><?php echo format_rupiah($total_nominal); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                Tidak ada data jurnal untuk periode ini
                            </td>
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
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Tentang Jurnal Umum</h6>
                <p class="small mb-2">
                    Jurnal Umum adalah catatan kronologis transaksi keuangan yang terjadi dalam perusahaan. 
                    Setiap transaksi dicatat dengan sistem double-entry (berpasangan) antara akun Debit dan Kredit.
                </p>
                <div class="row">
                    <div class="col-md-6">
                        <strong class="small">Akun yang di-Debit:</strong>
                        <ul class="small mb-0">
                            <li>Aktiva bertambah</li>
                            <li>Beban bertambah</li>
                            <li>Kewajiban berkurang</li>
                            <li>Modal berkurang</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong class="small">Akun yang di-Kredit:</strong>
                        <ul class="small mb-0">
                            <li>Aktiva berkurang</li>
                            <li>Beban berkurang</li>
                            <li>Kewajiban bertambah</li>
                            <li>Modal bertambah</li>
                            <li>Pendapatan bertambah</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>