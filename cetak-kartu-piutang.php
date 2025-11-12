<?php
/**
 * File: cetak-kartu-piutang.php
 * Cetak Kartu Piutang - Hanya tabel (tanpa header website)
 */
require_once 'config.php';
check_role(['kasir', 'akuntan', 'owner']);

// Get parameters
$id_piutang = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_piutang == 0) {
    die('Piutang tidak ditemukan');
}

// Get data piutang
$query = "SELECT p.*, mp.nama_pelanggan, mp.telepon, mp.alamat
          FROM piutang p
          JOIN master_pelanggan mp ON p.id_pelanggan = mp.id_pelanggan
          WHERE p.id_piutang = $id_piutang";

$result = $conn->query($query);
if ($result->num_rows == 0) {
    die('Data piutang tidak ditemukan');
}

$piutang = $result->fetch_assoc();

// Get pembayaran
$pembayaran = $conn->query("SELECT * FROM pembayaran_piutang 
                            WHERE id_piutang = $id_piutang 
                            ORDER BY tanggal ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kartu Piutang - <?php echo $piutang['no_piutang']; ?></title>
    <style>
        @media print {
            @page { margin: 1cm; }
            body { margin: 0; }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
        }
        
        .header h3 {
            margin: 5px 0;
            font-size: 16px;
            font-weight: normal;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .info-table td {
            padding: 5px;
            border: none;
        }
        
        .info-table td:first-child {
            width: 150px;
            font-weight: bold;
        }
        
        .data-table {
            border: 1px solid #333;
        }
        
        .data-table th,
        .data-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .summary-box {
            background-color: #f9f9f9;
            border: 2px solid #333;
            padding: 15px;
            margin-top: 20px;
        }
        
        .summary-box table td {
            padding: 8px;
            border: none;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        
        .footer {
            margin-top: 40px;
            text-align: right;
        }
        
        .footer table {
            float: right;
            width: auto;
        }
        
        .footer td {
            padding: 5px 20px;
            text-align: center;
        }
        
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Button Print -->
    <div class="no-print" style="text-align: right; margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">
            🖨️ Cetak / Print
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; margin-left: 5px;">
            ❌ Tutup
        </button>
    </div>

    <!-- Header -->
    <div class="header">
        <h2>CV. JASA PRINTING</h2>
        <h3>KARTU PIUTANG</h3>
        <p style="margin: 5px 0;">
            Dicetak: <?php echo date('d F Y, H:i'); ?> WIB
        </p>
    </div>

    <!-- Info Piutang -->
    <table class="info-table">
        <tr>
            <td colspan="4" style="background-color: #f0f0f0; padding: 10px; font-weight: bold; border: 1px solid #333;">
                INFORMASI PIUTANG
            </td>
        </tr>
        <tr>
            <td>No. Piutang</td>
            <td>: <strong><?php echo $piutang['no_piutang']; ?></strong></td>
            <td>Tanggal</td>
            <td>: <?php echo format_tanggal($piutang['tanggal']); ?></td>
        </tr>
        <tr>
            <td>Pelanggan</td>
            <td>: <?php echo $piutang['nama_pelanggan']; ?></td>
            <td>Jatuh Tempo</td>
            <td>: <?php echo format_tanggal($piutang['jatuh_tempo']); ?></td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>: <?php echo $piutang['alamat'] ?: '-'; ?></td>
            <td>Status</td>
            <td>: <strong><?php echo $piutang['status']; ?></strong></td>
        </tr>
        <tr>
            <td>Telepon</td>
            <td>: <?php echo $piutang['telepon'] ?: '-'; ?></td>
            <td>Kategori</td>
            <td>: <?php echo $piutang['kategori']; ?></td>
        </tr>
        <tr>
            <td>Jenis Jasa</td>
            <td colspan="3">: <?php echo $piutang['jenis_jasa']; ?></td>
        </tr>
    </table>

    <!-- Summary Keuangan -->
    <table class="data-table" style="width: 70%; margin: 20px 0;">
        <tr>
            <th>Total Piutang</th>
            <th>Sudah Dibayar</th>
            <th>Sisa Piutang</th>
        </tr>
        <tr>
            <td class="text-right fw-bold"><?php echo format_rupiah($piutang['total']); ?></td>
            <td class="text-right fw-bold"><?php echo format_rupiah($piutang['dibayar']); ?></td>
            <td class="text-right fw-bold"><?php echo format_rupiah($piutang['sisa']); ?></td>
        </tr>
    </table>

    <!-- Riwayat Pembayaran -->
    <?php if ($pembayaran->num_rows > 0): ?>
        <h4 style="margin-top: 30px; margin-bottom: 10px;">RIWAYAT PEMBAYARAN</h4>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Tanggal Bayar</th>
                    <th width="15%">Metode</th>
                    <th width="40%">Keterangan</th>
                    <th width="25%">Jumlah Bayar</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $total_bayar = 0;
                while ($bayar = $pembayaran->fetch_assoc()): 
                    $total_bayar += $bayar['jumlah_bayar'];
                ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo format_tanggal($bayar['tanggal']); ?></td>
                        <td><?php echo $bayar['metode_pembayaran']; ?></td>
                        <td><?php echo $bayar['keterangan'] ?: '-'; ?></td>
                        <td class="text-right fw-bold"><?php echo format_rupiah($bayar['jumlah_bayar']); ?></td>
                    </tr>
                <?php endwhile; ?>
                <tr style="background-color: #f0f0f0;">
                    <td colspan="4" class="text-right fw-bold">TOTAL PEMBAYARAN:</td>
                    <td class="text-right fw-bold"><?php echo format_rupiah($total_bayar); ?></td>
                </tr>
            </tbody>
        </table>
    <?php else: ?>
        <div style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0;">
            <strong>⚠️ Belum ada pembayaran untuk piutang ini</strong>
        </div>
    <?php endif; ?>

    <!-- Summary Box -->
    <div class="summary-box">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;"><strong>SISA YANG HARUS DIBAYAR:</strong></td>
                <td class="text-right" style="font-size: 18px; font-weight: bold;">
                    <?php echo format_rupiah($piutang['sisa']); ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer / Tanda Tangan -->
    <div class="footer">
        <table>
            <tr>
                <td>Diperiksa Oleh,</td>
                <td>Disetujui Oleh,</td>
            </tr>
            <tr>
                <td style="height: 60px;"></td>
                <td style="height: 60px;"></td>
            </tr>
            <tr>
                <td>(_________________)</td>
                <td>(_________________)</td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td>Manager</td>
            </tr>
        </table>
    </div>

    <script>
        // Auto print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
<?php $conn->close(); ?>