<?php
require_once 'config.php';
check_role(['kasir']);

$page_title = 'Penerimaan Pembayaran Piutang';
$current_page = 'penerimaan-piutang';

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = clean_input($_POST['tanggal']);
    $id_piutang = clean_input($_POST['id_piutang']);
    $jumlah_bayar = floatval(str_replace('.', '', $_POST['jumlah_bayar']));
    $metode = clean_input($_POST['metode_pembayaran']);
    $keterangan = clean_input($_POST['keterangan']);
    $created_by = $_SESSION['user_id'];
    
    // Get piutang info
    $piutang = $conn->query("SELECT * FROM piutang WHERE id_piutang = $id_piutang")->fetch_assoc();
    
    if (!$piutang) {
        alert('Data piutang tidak ditemukan!', 'danger');
    } else if ($jumlah_bayar > $piutang['sisa']) {
        alert('Jumlah pembayaran melebihi sisa piutang!', 'danger');
    } else if ($jumlah_bayar <= 0) {
        alert('Jumlah pembayaran harus lebih dari 0!', 'danger');
    } else {
        $conn->begin_transaction();
        
        try {
            // 1. Hitung cicilan ke berapa
            $query_cicilan = "SELECT COALESCE(MAX(cicilan_ke), 0) as last_cicilan 
                             FROM pembayaran_piutang 
                             WHERE id_piutang = $id_piutang AND is_dp = 0";
            $last_cicilan = $conn->query($query_cicilan)->fetch_assoc()['last_cicilan'];
            $cicilan_ke = $last_cicilan + 1;
            
            // 2. Insert pembayaran (is_dp = 0 untuk cicilan)
            $stmt = $conn->prepare("INSERT INTO pembayaran_piutang 
                    (tanggal, id_piutang, jumlah_bayar, is_dp, cicilan_ke, metode_pembayaran, keterangan, created_by) 
                    VALUES (?, ?, ?, 0, ?, ?, ?, ?)");
            $stmt->bind_param("sidissi", $tanggal, $id_piutang, $jumlah_bayar, $cicilan_ke, $metode, $keterangan, $created_by);
            
            if (!$stmt->execute()) {
                throw new Exception("Gagal insert pembayaran: " . $stmt->error);
            }
            
            // 3. Update piutang
            $sisa_baru = $piutang['sisa'] - $jumlah_bayar;
            $dibayar_baru = $piutang['dibayar'] + $jumlah_bayar;
            $status_baru = ($sisa_baru <= 0) ? 'Lunas' : 'Belum Lunas';
            
            $update_stmt = $conn->prepare("UPDATE piutang SET 
                         dibayar = ?, 
                         sisa = ?, 
                         status = ? 
                         WHERE id_piutang = ?");
            $update_stmt->bind_param("ddsi", $dibayar_baru, $sisa_baru, $status_baru, $id_piutang);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Gagal update piutang: " . $update_stmt->error);
            }
            
            // 4. Get akun ID
            $kas_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'")->fetch_assoc()['id_akun'];
            $piutang_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'")->fetch_assoc()['id_akun'];
            
            // 5. Create jurnal entry: Debit Kas, Kredit Piutang
            $deskripsi = "Penerimaan Pembayaran Piutang - " . $piutang['no_piutang'] . " (Cicilan ke-$cicilan_ke)";
            $jurnal_result = insert_jurnal($tanggal, $deskripsi, $kas_akun, $piutang_akun, $jumlah_bayar, $piutang['no_piutang'], "Penerimaan Piutang");
            
            if (!$jurnal_result) {
                throw new Exception("Gagal insert jurnal");
            }
            
            $conn->commit();
            
            alert("Pembayaran piutang berhasil dicatat! Cicilan ke-$cicilan_ke - " . format_rupiah($jumlah_bayar), 'success');
            redirect('penerimaan-piutang.php');
            
        } catch (Exception $e) {
            $conn->rollback();
            alert('Gagal menyimpan pembayaran: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get piutang belum lunas
$piutang_list = $conn->query("SELECT p.*, mp.nama_pelanggan,
                               (SELECT COUNT(*) FROM pembayaran_piutang WHERE id_piutang = p.id_piutang AND is_dp = 0) as jumlah_cicilan
                               FROM piutang p
                               LEFT JOIN master_pelanggan mp ON p.id_pelanggan = mp.id_pelanggan
                               WHERE p.status = 'Belum Lunas'
                               ORDER BY p.tanggal ASC");

// Get recent pembayaran
$recent = $conn->query("SELECT pp.*, p.no_piutang, mp.nama_pelanggan,
                        CASE WHEN pp.is_dp = 1 THEN 'DP' ELSE CONCAT('Cicilan ke-', pp.cicilan_ke) END as jenis_bayar
                        FROM pembayaran_piutang pp
                        JOIN piutang p ON pp.id_piutang = p.id_piutang
                        LEFT JOIN master_pelanggan mp ON p.id_pelanggan = mp.id_pelanggan
                        ORDER BY pp.created_at DESC LIMIT 15");

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-receipt me-2"></i>Penerimaan Pembayaran Piutang</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Penerimaan Piutang</li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Form Input -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Input Pembayaran</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formBayar">
                    <div class="mb-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pilih Piutang <span class="text-danger">*</span></label>
                        <select name="id_piutang" id="selectPiutang" class="form-select" required>
                            <option value="">-- Pilih Piutang --</option>
                            <?php while ($p = $piutang_list->fetch_assoc()): ?>
                                <option value="<?php echo $p['id_piutang']; ?>" 
                                        data-sisa="<?php echo $p['sisa']; ?>"
                                        data-total="<?php echo $p['total']; ?>"
                                        data-dibayar="<?php echo $p['dibayar']; ?>"
                                        data-cicilan="<?php echo $p['jumlah_cicilan']; ?>">
                                    <?php echo $p['no_piutang']; ?> - 
                                    <?php echo $p['nama_pelanggan']; ?> - 
                                    Sisa: <?php echo format_rupiah($p['sisa']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="infoPiutang" style="display: none;">
                        <!-- <div class="label">
                                <div class="col-6">
                                    <medium class="text-muted d-block">Total Piutang</medium>
                                    <strong id="totalPiutangDisplay">Rp 0</strong>
                                </div>
                            <hr class="my-2">
                                <div class="col-6">
                                    <medium class="text-muted d-block">Sudah Dibayar</medium>
                                    <strong id="dibayarDisplay">Rp 0</strong>
                                </div>
                            <hr class="my-2">
                                <div class="col-6">
                                    <medium class="text-muted d-block">Cicilan ke</medium>
                                    <strong id="cicilanKeDisplay">-</strong>
                                </div>
                            <hr class="my-2">
                                <div class="col-6">
                                    <medium class="text-muted d-block">Sisa Piutang</medium>
                                    <strong class="text-danger" id="sisaPiutangDisplay">Rp 0</strong>
                                </div>
                            <hr class="my-2">
                        </div> -->
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah Bayar (Rp) <span class="text-danger">*</span></label>
                        <input type="text" name="jumlah_bayar" id="jumlahBayar" class="form-control" 
                               placeholder="0" required min="0" step="100">
                        <small class="text-muted">Maksimal sesuai sisa piutang</small>
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
                        <i class="bi bi-save me-2"></i>Simpan Pembayaran
                    </button>
                </form>
            </div>
        </div>

        <!-- Daftar Piutang Belum Lunas -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Piutang Belum Lunas</h5>
            </div>
            <div class="card-body">
                <?php 
                $piutang_list->data_seek(0);
                if ($piutang_list->num_rows > 0): 
                ?>
                    <div class="list-group">
                        <?php while ($p = $piutang_list->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?php echo $p['no_piutang']; ?></strong><br>
                                        <small class="text-muted"><?php echo $p['nama_pelanggan']; ?></small><br>
                                        <small class="text-muted">
                                            Jatuh Tempo: <?php echo format_tanggal($p['jatuh_tempo']); ?>
                                            <?php if ($p['jumlah_cicilan'] > 0): ?>
                                                <br><span class="badge bg-info"><?php echo $p['jumlah_cicilan']; ?> cicilan</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-danger">
                                            <?php echo format_rupiah($p['sisa']); ?>
                                        </div>
                                        <small class="text-muted">
                                            dari <?php echo format_rupiah($p['total']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-check-circle display-4 d-block mb-2 text-success"></i>
                        Tidak ada piutang yang belum lunas
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Riwayat Pembayaran -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Pembayaran</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Piutang</th>
                                <th>Pelanggan</th>
                                <th>Jenis</th>
                                <th class="text-end">Jumlah Bayar</th>
                                <th>Metode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent->num_rows > 0): ?>
                                <?php while ($row = $recent->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_tanggal($row['tanggal']); ?></td>
                                        <td><strong><?php echo $row['no_piutang']; ?></strong></td>
                                        <td><?php echo $row['nama_pelanggan']; ?></td>
                                        <td>
                                            <span class="badge <?php echo ($row['is_dp'] == 1) ? 'bg-warning' : 'bg-info'; ?>">
                                                <?php echo $row['jenis_bayar']; ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            <?php echo format_rupiah($row['jumlah_bayar']); ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo $row['metode_pembayaran']; ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada pembayaran
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
function formatRupiah(angka) {
    return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function bersihkan(angka) {
    return parseInt(angka.replace(/\./g, '')) || 0;
}

// Show info piutang when selected
document.getElementById('selectPiutang').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    
    if (this.value) {
        const sisa = parseFloat(selectedOption.getAttribute('data-sisa'));
        const total = parseFloat(selectedOption.getAttribute('data-total'));
        const dibayar = parseFloat(selectedOption.getAttribute('data-dibayar'));
        const cicilan = parseInt(selectedOption.getAttribute('data-cicilan'));
        
        document.getElementById('infoPiutang').style.display = 'block';
        document.getElementById('totalPiutangDisplay').textContent = 'Rp ' + formatRupiah(total);
        document.getElementById('dibayarDisplay').textContent = 'Rp ' + formatRupiah(dibayar);
        document.getElementById('sisaPiutangDisplay').textContent = 'Rp ' + formatRupiah(sisa);
        document.getElementById('cicilanKeDisplay').textContent = (cicilan + 1);
        
        document.getElementById('jumlahBayar').max = sisa;
    } else {
        document.getElementById('infoPiutang').style.display = 'none';
    }
});

// Format input jumlah bayar
const inputJumlah = document.getElementById('jumlahBayar');
inputJumlah.addEventListener('input', function(e) {
    let value = this.value.replace(/\./g, '').replace(/[^0-9]/g, '');
    this.value = formatRupiah(value);
});

// Bersihkan titik sebelum submit
document.getElementById('formBayar').addEventListener('submit', function() {
    inputJumlah.value = bersihkan(inputJumlah.value);
});
</script>

<?php include 'includes/footer.php'; ?>