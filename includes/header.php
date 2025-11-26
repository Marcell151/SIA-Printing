<?php
if (!is_logged_in()) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.4/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="navbar-brand">
                <i class="bi bi-calculator-fill"></i>
                <span><?php echo APP_NAME; ?></span>
            </a>
            
            <div class="navbar-menu">
                <div class="navbar-user" onclick="toggleDropdown()">
                    <i class="bi bi-person-circle navbar-user-icon"></i>
                    <div class="navbar-user-info">
                        <div class="navbar-user-name"><?php echo $_SESSION['nama']; ?></div>
                        <div class="navbar-user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                    </div>
                    <i class="bi bi-chevron-down"></i>
                    
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="dashboard.php" class="dropdown-item">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item" onclick="return confirm('Apakah Anda yakin ingin logout?')">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a class="sidebar-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>" 
                       href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <?php if ($_SESSION['user_role'] == 'kasir'): ?>
                    <li class="sidebar-heading">MASTER DATA</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'master-pelanggan') ? 'active' : ''; ?>" 
                           href="master-pelanggan.php">
                            <i class="bi bi-people"></i>
                            <span>Master Pelanggan</span>
                        </a>
                    </li>
                    <li class="sidebar-heading">MASTER JASA</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'master-jasa') ? 'active' : ''; ?>" 
                           href="master-jasa.php">
                            <i class="bi bi-people"></i>
                            <span>Master Jasa</span>
                        </a>
                    </li>
                    <li class="sidebar-heading">TRANSAKSI</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'pendapatan-tunai') ? 'active' : ''; ?>" 
                           href="pendapatan-tunai.php">
                            <i class="bi bi-cash-coin"></i>
                            <span>Pendapatan Tunai</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'pendapatan-kredit') ? 'active' : ''; ?>" 
                           href="pendapatan-kredit.php">
                            <i class="bi bi-credit-card"></i>
                            <span>Pendapatan Kredit</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'penerimaan-piutang') ? 'active' : ''; ?>" 
                           href="penerimaan-piutang.php">
                            <i class="bi bi-receipt"></i>
                            <span>Penerimaan Piutang</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'pendapatan-lainnya') ? 'active' : ''; ?>" 
                           href="pendapatan-lainnya.php">
                            <i class="bi bi-wallet2"></i>
                            <span>Pendapatan Lainnya</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-heading">PIUTANG</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'kartu-piutang') ? 'active' : ''; ?>" 
                           href="kartu-piutang.php">
                            <i class="bi bi-card-text"></i>
                            <span>Kartu Piutang</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- <?php if ($_SESSION['user_role'] == 'akuntan'): ?>
                    <li class="sidebar-heading">MASTER DATA</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'master-akun') ? 'active' : ''; ?>" 
                           href="master-akun.php">
                            <i class="bi bi-list-ul"></i>
                            <span>Master Akun</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-heading">TRANSAKSI</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'transaksi-umum') ? 'active' : ''; ?>" 
                           href="transaksi-umum.php">
                            <i class="bi bi-journal-plus"></i>
                            <span>Transaksi Umum</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-heading">PIUTANG</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'kartu-piutang') ? 'active' : ''; ?>" 
                           href="kartu-piutang.php">
                            <i class="bi bi-card-text"></i>
                            <span>Kartu Piutang</span>
                        </a>
                    </li>
                    <li class="sidebar-heading">AKUNTANSI</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'jurnal-umum') ? 'active' : ''; ?>" 
                           href="jurnal-umum.php">
                            <i class="bi bi-journal-text"></i>
                            <span>Jurnal Umum</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'buku-besar') ? 'active' : ''; ?>" 
                           href="buku-besar.php">
                            <i class="bi bi-book"></i>
                            <span>Buku Besar</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'neraca-saldo') ? 'active' : ''; ?>" 
                           href="neraca-saldo.php">
                            <i class="bi bi-calculator"></i>
                            <span>Neraca Saldo</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'jurnal-penyesuaian') ? 'active' : ''; ?>" 
                           href="jurnal-penyesuaian.php">
                            <i class="bi bi-pencil-square"></i>
                            <span>Jurnal Penyesuaian</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'buku-besar-penyesuaian') ? 'active' : ''; ?>" 
                           href="buku-besar-penyesuaian.php">
                            <i class="bi bi-book-half"></i>
                            <span>Buku Besar Penyesuaian</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'neraca-penyesuaian') ? 'active' : ''; ?>" 
                           href="neraca-penyesuaian.php">
                            <i class="bi bi-calculator-fill"></i>
                            <span>Neraca Penyesuaian</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-heading">LAPORAN</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'laba-rugi') ? 'active' : ''; ?>" 
                           href="laba-rugi.php">
                            <i class="bi bi-graph-up"></i>
                            <span>Laba Rugi</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'perubahan-modal') ? 'active' : ''; ?>" 
                           href="perubahan-modal.php">
                            <i class="bi bi-currency-dollar"></i>
                            <span>Perubahan Modal</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'neraca-akhir') ? 'active' : ''; ?>" 
                           href="neraca-akhir.php">
                            <i class="bi bi-table"></i>
                            <span>Neraca Akhir</span>
                        </a>
                    </li>
                <?php endif; ?> -->
                
                <?php if ($_SESSION['user_role'] == 'owner'): ?>
                    <li class="sidebar-heading">MASTER DATA</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'master-akun') ? 'active' : ''; ?>" 
                           href="master-akun.php">
                            <i class="bi bi-list-ul"></i>
                            <span>Master Akun</span>
                        </a>
                    </li>
                    <li class="sidebar-heading">PIUTANG</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'kartu-piutang') ? 'active' : ''; ?>" 
                           href="kartu-piutang.php">
                            <i class="bi bi-card-text"></i>
                            <span>Kartu Piutang</span>
                        </a>
                    </li>
                    <li class="sidebar-heading">AKUNTANSI</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'jurnal-umum') ? 'active' : ''; ?>" 
                           href="jurnal-umum.php">
                            <i class="bi bi-journal-text"></i>
                            <span>Jurnal Umum</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'buku-besar') ? 'active' : ''; ?>" 
                           href="buku-besar.php">
                            <i class="bi bi-book"></i>
                            <span>Buku Besar</span>
                        </a>
                    </li>
                    <!-- <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'neraca-saldo') ? 'active' : ''; ?>" 
                           href="neraca-saldo.php">
                            <i class="bi bi-calculator"></i>
                            <span>Neraca Saldo</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'jurnal-penyesuaian') ? 'active' : ''; ?>" 
                           href="jurnal-penyesuaian.php">
                            <i class="bi bi-pencil-square"></i>
                            <span>Jurnal Penyesuaian</span>
                        </a>
                    </li>            
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'buku-besar-penyesuaian') ? 'active' : ''; ?>" 
                           href="buku-besar-penyesuaian.php">
                            <i class="bi bi-book-half"></i>
                            <span>Buku Besar Penyesuaian</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'neraca-penyesuaian') ? 'active' : ''; ?>" 
                           href="neraca-penyesuaian.php">
                            <i class="bi bi-calculator-fill"></i>
                            <span>Neraca Penyesuaian</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-heading">LAPORAN</li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'laba-rugi') ? 'active' : ''; ?>" 
                           href="laba-rugi.php">
                            <i class="bi bi-graph-up"></i>
                            <span>Laba Rugi</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'perubahan-modal') ? 'active' : ''; ?>" 
                           href="perubahan-modal.php">
                            <i class="bi bi-currency-dollar"></i>
                            <span>Perubahan Modal</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo ($current_page == 'neraca-akhir') ? 'active' : ''; ?>" 
                           href="neraca-akhir.php">
                            <i class="bi bi-table"></i>
                            <span>Neraca Akhir</span>
                        </a>
                    </li> -->
                <?php endif; ?>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <?php show_alert(); ?>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const userMenu = event.target.closest('.navbar-user');
    
    if (!userMenu && dropdown.classList.contains('show')) {
        dropdown.classList.remove('show');
    }
});
</script>