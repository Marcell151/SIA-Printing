<?php
require_once 'config.php';
check_role(['owner', 'akuntan']);

$page_title = 'Neraca Akhir';
$current_page = 'neraca-akhir';

// Filter periode
$periode = isset($_GET['periode']) ? $_GET['periode'] : date('Y-m');

// Get AKTIVA
$aktiva_query = "SELECT nsp.*, ma.kode_akun, ma.nama_akun, ma.tipe_akun
                 FROM neraca_saldo_penyesuaian nsp
                 JOIN master_akun ma ON nsp.id_akun = ma.id_akun
                 WHERE nsp.periode = '$periode'
                 AND ma.tipe_akun = '1-Aktiva'
                 AND ma.kode_akun NOT IN ('3-101', '3-102')
                 ORDER BY ma.kode_akun";
$aktiva = $conn->query($aktiva_query);

// Get KEWAJIBAN
$kewajiban_query = "SELECT nsp.*, ma.kode_akun, ma.nama_akun
                    FROM neraca_saldo_penyesuaian nsp
                    JOIN master_akun ma ON nsp.id_akun = ma.id_akun
                    WHERE nsp.periode = '$periode'
                    AND ma.tipe_akun = '2-Kewajiban'
                    ORDER BY ma.kode_akun";
$kewajiban = $conn->query($kewajiban_query);

// Calculate Modal Akhir (from perubahan modal)
$modal_awal_data = $conn->query("SELECT COALESCE(saldo_kredit, 0) as saldo
                                 FROM neraca_saldo_penyesuaian nsp
                                 JOIN master_akun ma ON nsp.id_akun = ma.id_akun
                                 WHERE nsp.periode = '$periode'
                                 AND ma.kode_akun = '3-101'");
$modal_awal = 0;
if ($modal_awal_data->num_rows > 0) {
    $modal_awal = $modal_awal_data->fetch_assoc()['saldo'];
}

$prive_data = $conn->query("SELECT COALESCE(saldo_debit, 0) as saldo
                            FROM neraca_saldo_penyesuaian nsp
                            JOIN master_akun ma ON nsp.id_akun = ma.id_akun
                            WHERE nsp.periode = '$periode'
                            AND ma.kode_akun = '3-102'");
$prive = 0;
if ($prive_data->num_rows > 0) {
    $prive = $prive_data->fetch_assoc()['saldo'];
}

$total_pendapatan = $conn->query("SELECT COALESCE(SUM(saldo_kredit), 0) as total
                                  FROM neraca_saldo_penyesuaian nsp
                                  JOIN master_akun ma ON nsp.id_akun = ma.id_akun
                                  WHERE nsp.periode = '$periode'
                                  AND ma.tipe_akun = '4-Pendapatan'")->fetch_assoc()['total'];

$total_beban = $conn->query("SELECT COALESCE(SUM(saldo_debit), 0) as total
                             FROM neraca_saldo_penyesuaian nsp
                             JOIN master_akun ma ON nsp.id_akun = ma.id_akun
                             WHERE nsp.periode = '$periode'
                             AND ma.tipe_akun = '5-Beban'")->fetch_assoc()['total'];

$laba_rugi = $total_pendapatan - $total_beban;
$modal_akhir = $modal_awal + $laba_rugi - $prive;

$total_aktiva = 0;
$total_kewajiban = 0;
$total_pasiva = 0;

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-table me-2"></i>Neraca Akhir</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Neraca Akhir</li>
        </ol>
    </nav>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Periode</label>
                <input type="month" name="periode" class="form-control" value="<?php echo $periode; ?>">
            </div>
            <div class="col-md-8 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Tampilkan
                </button>
                <button type="button" onclick="window.print()" class="btn btn-success no-print">
                    <i class="bi bi-printer me-1"></i>Cetak
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Neraca -->
<div class="card">
    <div class="card-header bg-dark text-white text-center">
        <h4 class="mb-1">CV. JASA PRINTING</h4>
        <h5 class="mb-0">NERACA (BALANCE SHEET)</h5>
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
        <div class="row">
            <!-- AKTIVA (Left Side) -->
            <div class="col-md-6 border-end">
                <h5 class="fw-bold mb-3 text-primary">
                    <i class="bi bi-arrow-up-circle me-2"></i>AKTIVA
                </h5>
                
                <table class="table table-borderless">
                    <tbody>
                        <?php if ($aktiva->num_rows > 0): ?>
                            <?php while ($row = $aktiva->fetch_assoc()): 
                                $nilai = $row['saldo_debit'] > 0 ? $row['saldo_debit'] : -$row['saldo_kredit'];
                                $total_aktiva += $nilai;
                            ?>
                                <tr>
                                    <td width="60%"><?php echo $row['nama_akun']; ?></td>
                                    <td width="40%" class="text-end">
                                        <?php echo format_rupiah($nilai); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <tr class="border-top border-dark border-2">
                                <td class="fw-bold">TOTAL AKTIVA</td>
                                <td class="text-end fw-bold"><?php echo format_rupiah($total_aktiva); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">
                                    Tidak ada data aktiva
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- PASIVA (Right Side) -->
            <div class="col-md-6">
                <h5 class="fw-bold mb-3 text-success">
                    <i class="bi bi-arrow-down-circle me-2"></i>PASIVA
                </h5>
                
                <table class="table table-borderless">
                    <tbody>
                        <!-- KEWAJIBAN -->
                        <tr class="table-secondary">
                            <td colspan="2" class="fw-bold">KEWAJIBAN</td>
                        </tr>
                        
                        <?php if ($kewajiban->num_rows > 0): ?>
                            <?php while ($row = $kewajiban->fetch_assoc()): 
                                $nilai = $row['saldo_kredit'] > 0 ? $row['saldo_kredit'] : $row['saldo_debit'];
                                $total_kewajiban += $nilai;
                            ?>
                                <tr>
                                    <td width="60%" class="ps-3"><?php echo $row['nama_akun']; ?></td>
                                    <td width="40%" class="text-end">
                                        <?php echo format_rupiah($nilai); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted ps-3">-</td>
                            </tr>
                        <?php endif; ?>
                        
                        <tr class="border-top">
                            <td class="ps-3 fw-semibold">Total Kewajiban</td>
                            <td class="text-end fw-semibold"><?php echo format_rupiah($total_kewajiban); ?></td>
                        </tr>
                        
                        <tr><td colspan="2" class="py-2"></td></tr>
                        
                        <!-- MODAL -->
                        <tr class="table-secondary">
                            <td colspan="2" class="fw-bold">MODAL</td>
                        </tr>
                        
                        <tr>
                            <td class="ps-3">Modal Pemilik</td>
                            <td class="text-end"><?php echo format_rupiah($modal_akhir); ?></td>
                        </tr>
                        
                        <tr class="border-top">
                            <td class="ps-3 fw-semibold">Total Modal</td>
                            <td class="text-end fw-semibold"><?php echo format_rupiah($modal_akhir); ?></td>
                        </tr>
                        
                        <tr><td colspan="2" class="py-2"></td></tr>
                        
                        <?php $total_pasiva = $total_kewajiban + $modal_akhir; ?>
                        
                        <tr class="border-top border-dark border-2">
                            <td class="fw-bold">TOTAL PASIVA</td>
                            <td class="text-end fw-bold"><?php echo format_rupiah($total_pasiva); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Balance Check -->
        <div class="row mt-3">
            <div class="col-12">
                <?php if (abs($total_aktiva - $total_pasiva) < 0.01): ?>
                    <div class="alert alert-success text-center">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>NERACA SEIMBANG</strong> - Aktiva = Pasiva 
                        (<?php echo format_rupiah($total_aktiva); ?>)
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger text-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>NERACA TIDAK SEIMBANG!</strong>
                        <br>Aktiva: <?php echo format_rupiah($total_aktiva); ?>
                        | Pasiva: <?php echo format_rupiah($total_pasiva); ?>
                        | Selisih: <?php echo format_rupiah(abs($total_aktiva - $total_pasiva)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mt-4 no-print">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6 class="mb-1 opacity-75">Total Aktiva</h6>
                <h4 class="mb-0"><?php echo format_rupiah($total_aktiva); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning">
            <div class="card-body text-center">
                <h6 class="mb-1 text-dark opacity-75">Total Kewajiban</h6>
                <h4 class="mb-0 text-dark"><?php echo format_rupiah($total_kewajiban); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6 class="mb-1 opacity-75">Total Modal</h6>
                <h4 class="mb-0"><?php echo format_rupiah($modal_akhir); ?></h4>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>