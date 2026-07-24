<?php
/**
 * Top Bar - SIAKAD PHP 5 Compatible
 */
$page_titles = array(
    'dashboard'  => array('title' => 'Dashboard',       'desc' => 'Ringkasan data akademik'),
    'mahasiswa'  => array('title' => 'Data Mahasiswa',   'desc' => 'Kelola data mahasiswa'),
    'dosen'      => array('title' => 'Data Dosen',       'desc' => 'Kelola data dosen'),
    'matakuliah' => array('title' => 'Mata Kuliah',      'desc' => 'Kelola data mata kuliah'),
    'krs'        => array('title' => 'Kartu Rencana Studi', 'desc' => 'Pendaftaran & manajemen KRS'),
    'nilai'      => array('title' => 'Input Nilai',      'desc' => 'Input nilai mahasiswa'),
    'transkrip'  => array('title' => 'Transkrip Nilai',  'desc' => 'Rekap nilai & IPK'),
    'settings'   => array('title' => 'Feature Flags',    'desc' => 'Pengaturan fitur sistem')
);

$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$pt = isset($page_titles[$current_page]) ? $page_titles[$current_page] : array('title' => 'SIAKAD', 'desc' => '');
?>
<div class="topbar">
    <div class="topbar-title">
        <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
        <h2><?php echo escape_html($pt['title']); ?></h2>
        <p><?php echo escape_html($pt['desc']); ?></p>
    </div>
    <div class="topbar-actions">
        <span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo date('d M Y'); ?></span>
    </div>
</div>
