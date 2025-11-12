<?php
/**
 * File: umur-piutang.php
 * Laporan umur Piutang - Analisis umur piutang
 */
require_once 'config.php';
check_role(['kasir', 'akuntan', 'owner']);

$page_title = 'Umur Piutang';
$current_page = 'umur-piutang';

// Get tanggal analisis (default = hari ini)
$tanggal_analisis = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Get piutang belum lunas
$query = "SELECT p.*, mp.nama_pelanggan, mp.telepon,
          DATEDIFF('$tanggal_analisis', p.jatuh_tempo) as hari_lewat,
          DATEDIFF('$tanggal_analisis', p.tanggal) as umur_piutang
          FROM piutang p
          JOIN master_pelanggan mp ON p.id_pelanggan = mp.id_pelanggan
          WHERE p.status = 'Belum Lunas'
          AND p.sisa > 0
          ORDER BY hari_lewat DESC, p.tanggal ASC";

$result = $conn->query($query);

// Kategorisasi umur
$umur_categories = [
    'belum_jatuh_tempo' => ['label' => 'Belum Jatuh Tempo', 'data' => [], 'total' => 0],
    '1_30_hari' => ['label' => '1-30 Hari', 'data' => [], 'total' => 0],
    '31_60_hari' => ['label' => '31-60 Hari', 'data' => [], 'total' => 0],
    '61_90_hari' => ['label' => '61-90 Hari', 'data' => [], 'total' => 0],
    'lebih_90_hari' => ['label' => '> 90 Hari', 'data' => [], 'total' => 0],
];

$total_keseluruhan = 0;

while ($row = $result->fetch_assoc()) {
    $hari_lewat = $row['hari_lewat'];
    $sisa = $row['sisa'];
    
    if ($hari_lewat < 0) {
        $umur_categories['belum_jatuh_tempo']['data'][] = $row;
        $umur_categories['belum_jatuh_tempo']['total'] += $sisa;
    } elseif ($hari_lewat <= 30) {
        $umur_categories['1_30_hari']['data'][] = $row;
        $umur_categories['1_30_hari']['total'] += $sisa;
    } elseif ($hari_lewat <= 60) {
        $umur_categories['31_60_hari']['data'][] = $row;
        $umur_categories['31_60_hari']['total'] += $sisa;
    } elseif ($hari_lewat <= 90) {
        $umur_categories['61_90_hari']['data'][] = $row;
        $umur_categories['61_90_hari']['total'] += $sisa;
    } else {
        $umur_categories['lebih_90_hari']['data'][] = $row;
        $umur_categories['lebih_90_hari']['total'] += $sisa;
    }
    
    $total_keseluruhan += $sisa;
}

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-clock-history me-2"></i>Umur Piutang (Analisis Umur)</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Umur Piutang</li>
        </ol>
    </nav>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal Analisis</label>
                <input type="date" name="tanggal" class="form-control" value="<?php echo $tanggal_analisis; ?>">
                <small class="text-muted">Default: Hari ini</small>
            </div>
            <div class="col-md-8 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Analisis
                </button>
                <button type="button" onclick="window.print()" class="btn btn-success no-print me-2">
                    <i class="bi bi-printer me-1"></i>Cetak
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-light">
            <div class="card-body text-center">
                <small class="text-muted d-block mb-1">Belum Jatuh Tempo</small>
                <h5 class="mb-0 fw-bold"><?php echo format_rupiah($umur_categories['belum_jatuh_tempo']['total']); ?></h5>
                <small class="badge bg-success"><?php echo count($umur_categories['belum_jatuh_tempo']['data']); ?> transaksi</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning bg-opacity-25">
            <div class="card-body text-center">
                <small class="text-muted d-block mb-1">1-30 Hari</small>
                <h5 class="mb-0 fw-bold"><?php echo format_rupiah($umur_categories['1_30_hari']['total']); ?></h5>
                <small class="badge bg-warning text-dark"><?php echo count($umur_categories['1_30_hari']['data']); ?> transaksi</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning bg-opacity-50">
            <div class="card-body text-center">
                <small class="text-muted d-block mb-1">31-60 Hari</small>
                <h5 class="mb-0 fw-bold"><?php echo format_rupiah($umur_categories['31_60_hari']['total']); ?></h5>
                <small class="badge bg-warning text-dark"><?php echo count($umur_categories['31_60_hari']['data']); ?> transaksi</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger bg-opacity-25">
            <div class="card-body text-center">
                <small class="text-muted d-block mb-1">61-90 Hari</small>
                <h5 class="mb-0 fw-bold"><?php echo format_rupiah($umur_categories['61_90_hari']['total']); ?></h5>
                <small class="badge bg-danger"><?php echo count($umur_categories['61_90_hari']['data']); ?> transaksi</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger bg-opacity-75 text-white">
            <div class="card-body text-center">
                <small class="opacity-75 d-block mb-1">> 90 Hari</small>
                <h5 class="mb-0 fw-bold"><?php echo format_rupiah($umur_categories['lebih_90_hari']['total']); ?></h5>
                <small class="badge bg-dark"><?php echo count($umur_categories['lebih_90_hari']['data']); ?> transaksi</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <small class="opacity-75 d-block mb-1">TOTAL PIUTANG</small>
                <h5 class="mb-0 fw-bold"><?php echo format_rupiah($total_keseluruhan); ?></h5>
                <small class="badge bg-dark"><?php echo array_sum(array_column($umur_categories, 'total')) > 0 ? 'Aktif' : 'Kosong'; ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Detail Per Kategori -->
<?php foreach ($umur_categories as $key => $category): ?>
    <?php if (count($category['data']) > 0): ?>
        <div class="card mb-4">
            <div class="card-header <?php 
                echo ($key == 'belum_jatuh_tempo') ? 'bg-success text-white' :
                     (($key == '1_30_hari') ? 'bg-warning' :
                     (($key == '31_60_hari') ? 'bg-warning' :
                     (($key == '61_90_hari') ? 'bg-danger text-white' : 'bg-danger text-white')));
            ?>">
                <h5 class="mb-0">
                    <i class="bi bi-clock me-2"></i><?php echo $category['label']; ?> 
                    - Total: <?php echo format_rupiah($category['total']); ?>
                    (<?php echo count($category['data']); ?> transaksi)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="12%">No. Piutang</th>
                                <th width="10%">Tanggal</th>
                                <th width="20%">Pelanggan</th>
                                <th width="8%" class="text-center">Umur (Hari)</th>
                                <th width="10%">Jatuh Tempo</th>
                                <th width="8%" class="text-center">Lewat (Hari)</th>
                                <th width="12%" class="text-end">Total</th>
                                <th width="15%" class="text-end">Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($category['data'] as $row): 
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo $row['no_piutang']; ?></strong>
                                        <br><small class="text-muted"><?php echo substr($row['jenis_jasa'], 0, 30); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td>
                                        <?php echo $row['nama_pelanggan']; ?>
                                        <?php if ($row['telepon']): ?>
                                            <br><small class="text-muted"><i class="bi bi-telephone"></i> <?php echo $row['telepon']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?php echo $row['umur_piutang']; ?></span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['jatuh_tempo'])); ?></td>
                                    <td class="text-center">
                                        <?php if ($row['hari_lewat'] < 0): ?>
                                            <span class="badge bg-success">-</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php echo $row['hari_lewat']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?php echo format_rupiah($row['total']); ?></td>
                                    <td class="text-end fw-bold text-danger"><?php echo format_rupiah($row['sisa']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <th colspan="8" class="text-end">Subtotal:</th>
                                <th class="text-end"><?php echo format_rupiah($category['total']); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php if ($total_keseluruhan == 0): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-emoji-smile display-1 text-success d-block mb-3"></i>
            <h5 class="text-success">Tidak Ada Piutang Belum Lunas!</h5>
            <p class="text-muted">Semua piutang sudah lunas atau tidak ada piutang aktif</p>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>