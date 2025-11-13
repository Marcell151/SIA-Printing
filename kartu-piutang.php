<?php
/**
 * File: kartu-piutang.php (FIXED - Per Invoice)
 * Kartu Piutang - 1 Invoice = 1 Kartu Piutang
 */
require_once 'config.php';
check_role(['kasir', 'akuntan', 'owner']);

$page_title = 'Kartu Piutang';
$current_page = 'kartu-piutang';

// Filter
$id_pelanggan = isset($_GET['id_pelanggan']) ? intval($_GET['id_pelanggan']) : 0;
$id_piutang = isset($_GET['id_piutang']) ? intval($_GET['id_piutang']) : 0;
$periode_dari = isset($_GET['periode_dari']) ? $_GET['periode_dari'] : '';
$periode_sampai = isset($_GET['periode_sampai']) ? $_GET['periode_sampai'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get list pelanggan yang punya piutang
$pelanggan_list = $conn->query("SELECT DISTINCT mp.id_pelanggan, mp.nama_pelanggan
                                 FROM master_pelanggan mp
                                 JOIN piutang p ON mp.id_pelanggan = p.id_pelanggan
                                 ORDER BY mp.nama_pelanggan");

// Get list piutang jika pelanggan sudah dipilih
$piutang_list = null;
if ($id_pelanggan > 0) {
    $query_list = "SELECT p.id_piutang, p.no_piutang, p.tanggal, p.jenis_jasa, p.total, p.sisa, p.status
                   FROM piutang p
                   WHERE p.id_pelanggan = $id_pelanggan";
    
    if ($periode_dari && $periode_sampai) {
        $query_list .= " AND p.tanggal BETWEEN '$periode_dari' AND '$periode_sampai'";
    }
    
    if ($status_filter) {
        $query_list .= " AND p.status = '$status_filter'";
    }
    
    $query_list .= " ORDER BY p.tanggal DESC";
    $piutang_list = $conn->query($query_list);
}

$kartu_piutang_data = [];
$piutang_info = null;
$pelanggan_info = null;

// Jika ID piutang spesifik dipilih, generate kartu piutang
if ($id_piutang > 0) {
    // Get data piutang dan pelanggan
    $piutang_info = $conn->query("SELECT p.*, mp.nama_pelanggan, mp.telepon, mp.alamat
                                   FROM piutang p
                                   JOIN master_pelanggan mp ON p.id_pelanggan = mp.id_pelanggan
                                   WHERE p.id_piutang = $id_piutang")->fetch_assoc();
    
    if ($piutang_info) {
        $pelanggan_info = $piutang_info;
        
        // Saldo awal = 0 (karena ini transaksi piutang baru)
        $saldo_berjalan = 0;
        
        // Baris 1: Transaksi Piutang (DEBET) - Piutang bertambah
        $saldo_berjalan += $piutang_info['total'];
        
        $kartu_piutang_data[] = [
            'tanggal' => $piutang_info['tanggal'],
            'keterangan' => 'Piutang - ' . $piutang_info['jenis_jasa'],
            'folio' => $piutang_info['no_piutang'],
            'mutasi_debet' => $piutang_info['total'],
            'mutasi_kredit' => 0,
            'saldo_debet' => $saldo_berjalan,
            'saldo_kredit' => 0,
            'row_type' => 'piutang'
        ];
        
        // Get pembayaran (DP + cicilan) dari tabel pembayaran_piutang
        $pembayaran = $conn->query("SELECT * FROM pembayaran_piutang 
                                    WHERE id_piutang = $id_piutang 
                                    ORDER BY tanggal ASC, is_dp DESC");
        
        while ($bayar = $pembayaran->fetch_assoc()) {
            $mutasi_kredit = $bayar['jumlah_bayar'];
            $saldo_berjalan -= $mutasi_kredit;
            
            // Label pembayaran
            if ($bayar['is_dp'] == 1) {
                $label_bayar = 'Pembayaran - DP';
            } else {
                $label_bayar = 'Pembayaran - Cicilan ke-' . $bayar['cicilan_ke'];
            }
            
            if ($bayar['metode_pembayaran']) {
                $label_bayar .= ' (' . $bayar['metode_pembayaran'] . ')';
            }
            
            $kartu_piutang_data[] = [
                'tanggal' => $bayar['tanggal'],
                'keterangan' => $label_bayar,
                'folio' => $piutang_info['no_piutang'],
                'mutasi_debet' => 0,
                'mutasi_kredit' => $mutasi_kredit,
                'saldo_debet' => $saldo_berjalan > 0 ? $saldo_berjalan : 0,
                'saldo_kredit' => $saldo_berjalan < 0 ? abs($saldo_berjalan) : 0,
                'row_type' => $bayar['is_dp'] == 1 ? 'dp' : 'pembayaran'
            ];
        }
    }
}

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-card-text me-2"></i>Kartu Piutang</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Kartu Piutang</li>
        </ol>
    </nav>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter Kartu Piutang</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3" id="formFilter">
            <div class="col-md-4">
                <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
                <select name="id_pelanggan" id="selectPelanggan" class="form-select" required>
                    <option value="">-- Pilih Pelanggan --</option>
                    <?php 
                    $pelanggan_list->data_seek(0);
                    while ($pel = $pelanggan_list->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $pel['id_pelanggan']; ?>" 
                                <?php echo ($id_pelanggan == $pel['id_pelanggan']) ? 'selected' : ''; ?>>
                            <?php echo $pel['nama_pelanggan']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="col-md-4" id="divPiutang" style="<?php echo $id_pelanggan > 0 ? '' : 'display:none;'; ?>">
                <label class="form-label">Pilih Piutang/Invoice <span class="text-danger">*</span></label>
                <select name="id_piutang" id="selectPiutang" class="form-select">
                    <option value="">-- Pilih Invoice --</option>
                    <?php if ($piutang_list && $piutang_list->num_rows > 0): ?>
                        <?php while ($pit = $piutang_list->fetch_assoc()): ?>
                            <option value="<?php echo $pit['id_piutang']; ?>"
                                    <?php echo ($id_piutang == $pit['id_piutang']) ? 'selected' : ''; ?>>
                                <?php echo $pit['no_piutang']; ?> - 
                                <?php echo date('d/m/Y', strtotime($pit['tanggal'])); ?> - 
                                <?php echo substr($pit['jenis_jasa'], 0, 30); ?> - 
                                <?php echo format_rupiah($pit['sisa']); ?>
                                (<?php echo $pit['status']; ?>)
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <small class="text-muted">Pilih invoice/piutang yang ingin dilihat kartunya</small>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="periode_dari" class="form-control" value="<?php echo $periode_dari; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="periode_sampai" class="form-control" value="<?php echo $periode_sampai; ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="Belum Lunas" <?php echo ($status_filter == 'Belum Lunas') ? 'selected' : ''; ?>>Belum Lunas</option>
                    <option value="Lunas" <?php echo ($status_filter == 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Tampilkan Kartu
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($id_piutang > 0 && $piutang_info): ?>

<!-- KARTU PIUTANG - FORMAT STANDAR PER INVOICE -->
<div class="card">
    <div class="card-header bg-white border-bottom-0">
        <div class="text-center mb-3">
            <h4 class="mb-1 fw-bold">CV. JASA PRINTING</h4>
            <h5 class="mb-3">KARTU PIUTANG</h5>
        </div>
        
        <!-- Info Pelanggan dan Invoice -->
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="120" class="fw-semibold">No Rekening</td>
                        <td>: <?php echo str_pad($pelanggan_info['id_pelanggan'], 3, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Nama</td>
                        <td>: <strong><?php echo $pelanggan_info['nama_pelanggan']; ?></strong></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Alamat</td>
                        <td>: <?php echo $pelanggan_info['alamat'] ?: '-'; ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="140" class="fw-semibold">No. Invoice</td>
                        <td>: <strong><?php echo $piutang_info['no_piutang']; ?></strong></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Tanggal Invoice</td>
                        <td>: <?php echo format_tanggal($piutang_info['tanggal']); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Syarat</td>
                        <td>: <strong><?php echo $piutang_info['syarat_kredit'] ?? 'Net 30'; ?></strong></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Jatuh Tempo</td>
                        <td>: <?php echo format_tanggal($piutang_info['jatuh_tempo']); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Status</td>
                        <td>: <span class="badge <?php echo ($piutang_info['status'] == 'Lunas') ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo $piutang_info['status']; ?>
                        </span></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Info Jenis Jasa -->
        <div class="alert alert-info mb-3">
            <strong>Jenis Jasa:</strong> <?php echo $piutang_info['jenis_jasa']; ?>
            <br><strong>Kategori:</strong> <?php echo $piutang_info['kategori']; ?>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr class="text-center">
                        <th width="10%" rowspan="2" class="align-middle">TGL</th>
                        <th width="35%" rowspan="2" class="align-middle">KETERANGAN</th>
                        <th width="10%" rowspan="2" class="align-middle">FOL</th>
                        <th colspan="2" class="border-bottom">MUTASI</th>
                        <th colspan="2" class="border-bottom">SALDO</th>
                    </tr>
                    <tr class="text-center">
                        <th width="11%">DEBET</th>
                        <th width="11%">KREDIT</th>
                        <th width="11%">DEBET</th>
                        <th width="12%">KREDIT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($kartu_piutang_data) > 0): ?>
                        <?php 
                        foreach ($kartu_piutang_data as $row): 
                            $row_class = '';
                            if ($row['row_type'] == 'piutang') {
                                $row_class = 'table-warning';
                            } elseif ($row['row_type'] == 'dp') {
                                $row_class = 'table-info';
                            } elseif ($row['row_type'] == 'pembayaran') {
                                $row_class = 'table-success';
                            }
                        ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td class="text-center"><?php echo date('d/m', strtotime($row['tanggal'])); ?></td>
                                <td><?php echo $row['keterangan']; ?></td>
                                <td class="text-center"><small><?php echo $row['folio']; ?></small></td>
                                <td class="text-end">
                                    <?php echo ($row['mutasi_debet'] > 0) ? format_rupiah($row['mutasi_debet']) : '--'; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo ($row['mutasi_kredit'] > 0) ? format_rupiah($row['mutasi_kredit']) : '--'; ?>
                                </td>
                                <td class="text-end fw-semibold">
                                    <?php echo ($row['saldo_debet'] > 0) ? format_rupiah($row['saldo_debet']) : '--'; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo ($row['saldo_kredit'] > 0) ? format_rupiah($row['saldo_kredit']) : '--'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Saldo Akhir -->
                        <tr class="table-primary fw-bold">
                            <td colspan="5" class="text-end">SALDO AKHIR:</td>
                            <td class="text-end">
                                <?php 
                                $last_row = end($kartu_piutang_data);
                                $saldo_akhir = $last_row['saldo_debet'] > 0 ? $last_row['saldo_debet'] : 0;
                                echo format_rupiah($saldo_akhir); 
                                ?>
                            </td>
                            <td class="text-end">--</td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Tidak ada mutasi untuk invoice ini
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Summary -->
    <div class="card-footer bg-light">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted d-block">Total Invoice</small>
                <strong><?php echo format_rupiah($piutang_info['total']); ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Sudah Dibayar</small>
                <strong class="text-success"><?php echo format_rupiah($piutang_info['dibayar']); ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Sisa Piutang</small>
                <strong class="text-danger"><?php echo format_rupiah($piutang_info['sisa']); ?></strong>
            </div>
            <div class="col-md-3 text-end">
                <button onclick="window.print()" class="btn btn-success no-print">
                    <i class="bi bi-printer me-1"></i>Cetak Kartu
                </button>
            </div>
        </div>
    </div>
</div>

<?php elseif ($id_pelanggan > 0 && $piutang_list && $piutang_list->num_rows > 0): ?>

<!-- Daftar Invoice untuk dipilih -->
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Pelanggan dipilih!</strong> Silakan pilih invoice/piutang yang ingin ditampilkan kartu piutangnya dari dropdown di atas.
</div>

<div class="card">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Daftar Invoice yang Tersedia</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Tanggal</th>
                        <th>Jenis Jasa</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Sisa</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $piutang_list->data_seek(0);
                    while ($pit = $piutang_list->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><strong><?php echo $pit['no_piutang']; ?></strong></td>
                            <td><?php echo format_tanggal($pit['tanggal']); ?></td>
                            <td><?php echo substr($pit['jenis_jasa'], 0, 50); ?></td>
                            <td class="text-end"><?php echo format_rupiah($pit['total']); ?></td>
                            <td class="text-end fw-bold text-danger"><?php echo format_rupiah($pit['sisa']); ?></td>
                            <td>
                                <span class="badge <?php echo ($pit['status'] == 'Lunas') ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $pit['status']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="?id_pelanggan=<?php echo $id_pelanggan; ?>&id_piutang=<?php echo $pit['id_piutang']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> Lihat Kartu
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php else: ?>

<?php endif; ?>

<script>
// Auto reload saat pelanggan dipilih untuk load daftar invoice
document.getElementById('selectPelanggan').addEventListener('change', function() {
    if (this.value) {
        document.getElementById('divPiutang').style.display = 'block';
        // Reload untuk load invoice list
        window.location.href = '?id_pelanggan=' + this.value;
    } else {
        document.getElementById('divPiutang').style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>