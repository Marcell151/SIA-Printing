<?php
require_once 'config.php';
check_role(['akuntan']);

$page_title = 'Jurnal Penyesuaian';
$current_page = 'jurnal-penyesuaian';

// Get accounts
$accounts = $conn->query("SELECT * FROM master_akun ORDER BY kode_akun");

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $tanggal = clean_input($_POST['tanggal']);
    $id_akun_debit = clean_input($_POST['id_akun_debit']);
    $id_akun_kredit = clean_input($_POST['id_akun_kredit']);
    $nominal = floatval($_POST['nominal']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $periode = date('Y-m', strtotime($tanggal));
    $created_by = $_SESSION['user_id'];
    
    // Validation
    if ($id_akun_debit == $id_akun_kredit) {
        alert('Akun debit dan kredit tidak boleh sama!', 'danger');
    } else {
        // Insert jurnal penyesuaian
        $stmt = $conn->prepare("INSERT INTO jurnal_penyesuaian 
                (tanggal, id_akun_debit, id_akun_kredit, nominal, deskripsi, periode, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siidssi", $tanggal, $id_akun_debit, $id_akun_kredit, $nominal, $deskripsi, $periode, $created_by);
        
        if ($stmt->execute()) {
            // Insert to jurnal umum
            $akun_debit = $conn->query("SELECT nama_akun FROM master_akun WHERE id_akun = $id_akun_debit")->fetch_assoc()['nama_akun'];
            $akun_kredit = $conn->query("SELECT nama_akun FROM master_akun WHERE id_akun = $id_akun_kredit")->fetch_assoc()['nama_akun'];
            
            $deskripsi_jurnal = "Penyesuaian: $deskripsi";
            insert_jurnal($tanggal, $deskripsi_jurnal, $id_akun_debit, $id_akun_kredit, $nominal, "AJE-" . $stmt->insert_id, "Jurnal Penyesuaian");
            
            alert('Jurnal penyesuaian berhasil disimpan!', 'success');
            redirect('jurnal-penyesuaian.php');
        } else {
            alert('Gagal menyimpan jurnal penyesuaian: ' . $conn->error, 'danger');
        }
    }
}

// Filter
$periode_filter = isset($_GET['periode']) ? $_GET['periode'] : date('Y-m');

// Get jurnal penyesuaian
$query = "SELECT jp.*, 
          ma_debit.kode_akun as kode_debit, ma_debit.nama_akun as nama_debit,
          ma_kredit.kode_akun as kode_kredit, ma_kredit.nama_akun as nama_kredit
          FROM jurnal_penyesuaian jp
          JOIN master_akun ma_debit ON jp.id_akun_debit = ma_debit.id_akun
          JOIN master_akun ma_kredit ON jp.id_akun_kredit = ma_kredit.id_akun
          WHERE jp.periode = '$periode_filter'
          ORDER BY jp.tanggal ASC";
$result = $conn->query($query);

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-pencil-square me-2"></i>Jurnal Penyesuaian</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Jurnal Penyesuaian</li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Form Input -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Input Jurnal Penyesuaian</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" 
                               value="<?php echo date('Y-m-t'); ?>" required>
                        <small class="text-muted">Biasanya di akhir periode (tanggal 31)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Akun Debit <span class="text-danger">*</span></label>
                        <select name="id_akun_debit" class="form-select" required>
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
                        <select name="id_akun_kredit" class="form-select" required>
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
                                <option value="<?php echo $acc['id_akun']; ?>">
                                    <?php echo $acc['kode_akun'] . ' - ' . $acc['nama_akun']; ?>
                                </option>
                            <?php endwhile; ?>
                            </optgroup>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="nominal" class="form-control" 
                               placeholder="0" required min="0" step="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                        <textarea name="deskripsi" class="form-control" rows="3" 
                                  placeholder="Jelaskan penyesuaian yang dilakukan" required></textarea>
                    </div>

                    <button type="submit" name="submit" class="btn btn-warning w-100">
                        <i class="bi bi-save me-2"></i>Simpan Jurnal Penyesuaian
                    </button>
                </form>
            </div>
        </div>

        <!-- Info Box -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Contoh Jurnal Penyesuaian</h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <p class="fw-bold mb-2">1. Perlengkapan Terpakai:</p>
                    <ul class="mb-3">
                        <li>Debit: Beban Perlengkapan</li>
                        <li>Kredit: Perlengkapan</li>
                    </ul>

                    <p class="fw-bold mb-2">2. Penyusutan Peralatan:</p>
                    <ul class="mb-3">
                        <li>Debit: Beban Penyusutan</li>
                        <li>Kredit: Akumulasi Penyusutan</li>
                    </ul>

                    <p class="fw-bold mb-2">3. Sewa Dibayar Dimuka:</p>
                    <ul class="mb-3">
                        <li>Debit: Beban Sewa</li>
                        <li>Kredit: Sewa Dibayar Dimuka</li>
                    </ul>

                    <p class="fw-bold mb-2">4. Pendapatan Diterima Dimuka:</p>
                    <ul class="mb-0">
                        <li>Debit: Pendapatan Diterima Dimuka</li>
                        <li>Kredit: Pendapatan Jasa</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- List Jurnal Penyesuaian -->
    <div class="col-lg-7">
        <!-- Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Periode</label>
                        <input type="month" name="periode" class="form-control" value="<?php echo $periode_filter; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-filter me-1"></i>Filter
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-success no-print">
                            <i class="bi bi-printer me-1"></i>Cetak
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Jurnal Penyesuaian</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">Tanggal</th>
                                <th width="35%">Deskripsi</th>
                                <th width="20%">Debit</th>
                                <th width="20%">Kredit</th>
                                <th width="15%" class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                        <td><?php echo $row['deskripsi']; ?></td>
                                        <td>
                                            <strong><?php echo $row['kode_debit']; ?></strong><br>
                                            <small class="text-muted"><?php echo $row['nama_debit']; ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo $row['kode_kredit']; ?></strong><br>
                                            <small class="text-muted"><?php echo $row['nama_kredit']; ?></small>
                                        </td>
                                        <td class="text-end fw-bold">
                                            <?php echo format_rupiah($row['nominal']); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        Belum ada jurnal penyesuaian untuk periode ini
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