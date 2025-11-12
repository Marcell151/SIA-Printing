<?php
require_once 'config.php';
check_role(['akuntan', 'owner']);

$page_title = 'Neraca Saldo';
$current_page = 'neraca-saldo';

// Filter periode
$periode = isset($_GET['periode']) ? $_GET['periode'] : date('Y-m');

// Process generate neraca saldo
if (isset($_POST['generate'])) {
    $periode_generate = $_POST['periode'];
    
    // Delete existing neraca saldo for this period
    $conn->query("DELETE FROM neraca_saldo WHERE periode = '$periode_generate'");
    
    // Get all accounts
    $accounts = $conn->query("SELECT * FROM master_akun ORDER BY kode_akun");
    
    while ($akun = $accounts->fetch_assoc()) {
        $id_akun = $akun['id_akun'];
        $tipe_akun = $akun['tipe_akun'];
        
        // Calculate saldo from jurnal up to end of period
        // EXCLUDE Jurnal Penyesuaian (tipe_transaksi != 'Jurnal Penyesuaian')
        $tanggal_akhir = date('Y-m-t', strtotime($periode_generate . '-01'));
        
        $debit_total = $conn->query("SELECT COALESCE(SUM(nominal), 0) as total 
                                     FROM jurnal_umum 
                                     WHERE id_akun_debit = $id_akun 
                                     AND tanggal <= '$tanggal_akhir'
                                     AND (tipe_transaksi IS NULL OR tipe_transaksi != 'Jurnal Penyesuaian')")->fetch_assoc()['total'];
        
        $kredit_total = $conn->query("SELECT COALESCE(SUM(nominal), 0) as total 
                                      FROM jurnal_umum 
                                      WHERE id_akun_kredit = $id_akun 
                                      AND tanggal <= '$tanggal_akhir'
                                      AND (tipe_transaksi IS NULL OR tipe_transaksi != 'Jurnal Penyesuaian')")->fetch_assoc()['total'];
        
        // Determine saldo debit or kredit based on account type
        $saldo_debit = 0;
        $saldo_kredit = 0;
        
        if (strpos($tipe_akun, '1-Aktiva') !== false || strpos($tipe_akun, '5-Beban') !== false) {
            // Normal saldo debit
            $saldo = $debit_total - $kredit_total;
            if ($saldo > 0) {
                $saldo_debit = $saldo;
            } else {
                $saldo_kredit = abs($saldo);
            }
        } else {
            // Normal saldo kredit (Kewajiban, Modal, Pendapatan)
            $saldo = $kredit_total - $debit_total;
            if ($saldo > 0) {
                $saldo_kredit = $saldo;
            } else {
                $saldo_debit = abs($saldo);
            }
        }
        
        // Insert only if there's a balance
        if ($saldo_debit > 0 || $saldo_kredit > 0) {
            $conn->query("INSERT INTO neraca_saldo (periode, id_akun, saldo_debit, saldo_kredit) 
                         VALUES ('$periode_generate', $id_akun, $saldo_debit, $saldo_kredit)");
        }
    }
    
    alert('Neraca Saldo berhasil di-generate!', 'success');
    redirect("neraca-saldo.php?periode=$periode_generate");
}

// Get neraca saldo data
$query = "SELECT ns.*, ma.kode_akun, ma.nama_akun, ma.tipe_akun
          FROM neraca_saldo ns
          JOIN master_akun ma ON ns.id_akun = ma.id_akun
          WHERE ns.periode = '$periode'
          ORDER BY ma.kode_akun";
$result = $conn->query($query);

$total_debit = 0;
$total_kredit = 0;

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-calculator me-2"></i>Neraca Saldo</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Neraca Saldo</li>
        </ol>
    </nav>
</div>

<!-- Info Alert -->
<div class="alert alert-warning no-print">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Catatan:</strong> Neraca Saldo ini menampilkan saldo SEBELUM jurnal penyesuaian. 
    Jurnal penyesuaian TIDAK termasuk dalam perhitungan neraca ini.
    Untuk melihat saldo setelah penyesuaian, gunakan menu "Neraca Setelah Penyesuaian".
</div>

<!-- Action Bar -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Periode</label>
                        <input type="month" name="periode" class="form-control" value="<?php echo $periode; ?>">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-1"></i>Tampilkan
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-success no-print">
                            <i class="bi bi-printer me-1"></i>Cetak
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($_SESSION['user_role'] == 'akuntan'): ?>
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#generateModal">
                    <i class="bi bi-gear me-1"></i>Generate Neraca Saldo
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Neraca Saldo Table -->
<div class="card">
    <div class="card-header bg-primary text-white text-center">
        <h4 class="mb-1">CV. JASA PRINTING</h4>
        <h5 class="mb-0">NERACA SALDO</h5>
        <p class="mb-0">
            Per <?php 
            $nama_bulan = array(
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            );
            $pecah = explode('-', $periode);
            echo '31 ' . $nama_bulan[$pecah[1]] . ' ' . $pecah[0];
            ?>
        </p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="15%" class="text-center">Kode Akun</th>
                        <th width="45%">Nama Akun</th>
                        <th width="20%" class="text-end">Debit (Rp)</th>
                        <th width="20%" class="text-end">Kredit (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php 
                        $current_tipe = '';
                        while ($row = $result->fetch_assoc()): 
                            $total_debit += $row['saldo_debit'];
                            $total_kredit += $row['saldo_kredit'];
                            
                            if ($current_tipe != $row['tipe_akun']) {
                                $current_tipe = $row['tipe_akun'];
                        ?>
                                <tr class="table-secondary">
                                    <td colspan="4" class="fw-bold">
                                        <i class="bi bi-folder me-2"></i><?php echo $current_tipe; ?>
                                    </td>
                                </tr>
                        <?php } ?>
                            <tr>
                                <td class="text-center"><?php echo $row['kode_akun']; ?></td>
                                <td><?php echo $row['nama_akun']; ?></td>
                                <td class="text-end">
                                    <?php echo ($row['saldo_debit'] > 0) ? format_rupiah($row['saldo_debit']) : '-'; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo ($row['saldo_kredit'] > 0) ? format_rupiah($row['saldo_kredit']) : '-'; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        
                        <tr class="table-primary">
                            <td colspan="2" class="text-center fw-bold">TOTAL</td>
                            <td class="text-end fw-bold"><?php echo format_rupiah($total_debit); ?></td>
                            <td class="text-end fw-bold"><?php echo format_rupiah($total_kredit); ?></td>
                        </tr>
                        
                        <?php if ($total_debit == $total_kredit): ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                <div class="alert alert-success mb-0">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Neraca Saldo SEIMBANG</strong> (Debit = Kredit)
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                <div class="alert alert-danger mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Neraca Saldo TIDAK SEIMBANG!</strong>
                                    Selisih: <?php echo format_rupiah(abs($total_debit - $total_kredit)); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                <h5>Belum ada data Neraca Saldo untuk periode ini</h5>
                                <?php if ($_SESSION['user_role'] == 'akuntan'): ?>
                                <p>Klik tombol "Generate Neraca Saldo" untuk membuat neraca saldo</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Generate Modal -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bi bi-gear me-2"></i>Generate Neraca Saldo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Perhatian:</strong> Proses ini akan menghitung ulang saldo semua akun 
                        berdasarkan jurnal umum sampai akhir periode yang dipilih.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Pilih Periode <span class="text-danger">*</span></label>
                        <input type="month" name="periode" class="form-control" 
                               value="<?php echo $periode; ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="generate" class="btn btn-warning">
                        <i class="bi bi-gear me-1"></i>Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>