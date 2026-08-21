<?php
$current_admin_section = strtolower((string) ($this->uri->segment(2) ?: 'dashboard'));
$sidebar_items = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'url' => base_url('index.php/admin/dashboard')],
    ['key' => 'barang', 'label' => 'Master Data', 'icon' => 'bi-boxes', 'url' => base_url('index.php/admin/barang')],
    ['key' => 'peminjaman', 'label' => 'Peminjaman', 'icon' => 'bi-clipboard-data', 'url' => base_url('index.php/admin/peminjaman')],
    ['key' => 'pengembalian', 'label' => 'Pengembalian', 'icon' => 'bi-arrow-counterclockwise', 'url' => base_url('index.php/admin/pengembalian')],
    ['key' => 'dokumen', 'label' => 'Dokumen', 'icon' => 'bi-file-earmark-arrow-up', 'url' => base_url('index.php/admin/dokumen')],
    ['key' => 'ruangan', 'label' => 'Ruangan', 'icon' => 'bi-door-open', 'url' => base_url('index.php/admin/ruangan')],
    ['key' => 'maintenance', 'label' => 'Maintenance Barang', 'icon' => 'bi-tools', 'url' => base_url('index.php/admin/maintenance')],
    ['key' => 'distribusi', 'label' => 'Distribusi Barang', 'icon' => 'bi-truck', 'url' => base_url('index.php/admin/distribusi')],
    ['key' => 'blokir', 'label' => 'Blokir Pengguna', 'icon' => 'bi-shield-lock', 'url' => base_url('index.php/admin/blokir')],
    ['key' => 'pengaturan', 'label' => 'Pengaturan Peminjaman', 'icon' => 'bi-gear', 'url' => base_url('index.php/admin/pengaturan')],
];
?>
<?php include APPPATH . 'views/shared/theme_assets.php'; ?>
<aside class="scm-admin-sidebar" aria-label="Navigasi Laboran">
    <a class="scm-admin-brand" href="<?= base_url('index.php/admin/dashboard') ?>">
        <span class="scm-admin-brand-mark"><i class="bi bi-person-workspace" aria-hidden="true"></i></span>
        <span><strong>SCM FIK</strong><small>Panel Laboran</small></span>
    </a>
    <div class="scm-admin-sidebar-label">Operasional</div>
    <nav class="scm-admin-sidebar-nav">
        <?php foreach ($sidebar_items as $item): ?>
            <a class="scm-admin-sidebar-link <?= $current_admin_section === $item['key'] ? 'is-active' : '' ?>" href="<?= $item['url'] ?>" <?= $current_admin_section === $item['key'] ? 'aria-current="page"' : '' ?>><i class="bi <?= html_escape($item['icon']) ?>" aria-hidden="true"></i><span><?= html_escape($item['label']) ?></span></a>
        <?php endforeach; ?>
    </nav>
    <div class="scm-admin-sidebar-status"><span></span> System operational</div>
</aside>
<style>
    body.scm-admin-shell { padding-left: 236px; background: #f5f6f8; color: #202124; font-family: 'Poppins', sans-serif; }
    .scm-admin-sidebar { position: fixed; inset: 0 auto 0 0; z-index: 1040; width: 236px; overflow-y: auto; padding: 22px 14px 16px; border-right: 1px solid #e3e6e8; background: #fff; }
    .scm-admin-brand { display: flex; align-items: center; gap: 11px; padding: 0 8px 24px; color: #17202a; text-decoration: none; }
    .scm-admin-brand-mark { display: inline-flex; width: 38px; height: 38px; flex: 0 0 38px; align-items: center; justify-content: center; border-radius: 10px; color: #ff7900; background: rgba(255,121,0,.12); font-size: 1.18rem; }
    .scm-admin-brand strong, .scm-admin-brand small { display: block; line-height: 1.15; }
    .scm-admin-brand strong { font-size: .98rem; }
    .scm-admin-brand small { margin-top: 3px; color: #68727b; font-size: .7rem; }
    .scm-admin-sidebar-label { margin: 0; padding: 0 10px 8px; color: #68727b; font-size: .7rem; font-weight: 500; letter-spacing: .12em; text-transform: uppercase; }
    .scm-admin-sidebar-nav { display: grid; gap: 4px; }
    .scm-admin-sidebar-link { display: flex; align-items: center; gap: 11px; min-height: 40px; padding: 10px 11px; border: 1px solid transparent; border-radius: 8px; color: #59636b; font-size: .82rem; font-weight: 500; text-decoration: none; transition: color .18s ease, background .18s ease, border-color .18s ease; }
    .scm-admin-sidebar-link i { width: 18px; flex: 0 0 18px; color: #7e878e; text-align: center; font-size: .95rem; }
    .scm-admin-sidebar-link:hover, .scm-admin-sidebar-link.is-active { color: #fff; background: #ff7900; border-color: #ff7900; }
    .scm-admin-sidebar-link:hover i, .scm-admin-sidebar-link.is-active i { color: #fff; }
    .scm-admin-sidebar-status { display: flex; align-items: center; gap: 8px; margin: 28px 8px 0; padding-top: 16px; border-top: 1px solid #e3e6e8; color: #68727b; font-size: .72rem; }
    .scm-admin-sidebar-status span { width: 7px; height: 7px; border-radius: 50%; background: #43d17a; box-shadow: 0 0 0 4px rgba(67,209,122,.1); }
    body.scm-admin-shell > .topbar, body.scm-admin-shell > .admin-navbar { min-width: 0; }
    body.scm-admin-shell > main { min-width: 0; }
    @media (max-width: 767.98px) {
        body.scm-admin-shell { padding-left: 68px; }
        .scm-admin-sidebar { width: 68px; padding: 16px 8px; }
        .scm-admin-brand { justify-content: center; padding: 0 0 20px; }
        .scm-admin-brand > span:last-child, .scm-admin-sidebar-label, .scm-admin-sidebar-link span, .scm-admin-sidebar-status { display: none; }
        .scm-admin-sidebar-link { justify-content: center; padding: 10px 0; }
        .scm-admin-sidebar-link i { width: auto; flex-basis: auto; }
    }
</style>
<?php if (in_array($current_admin_section, ['barang', 'peminjaman', 'pengembalian', 'approval', 'dokumen', 'ruangan', 'maintenance', 'distribusi', 'blokir', 'pengaturan'], true)): ?>
<style id="scm-laboran-light-header">
    /* Keep the Laboran feature headers aligned with the light Kaur/Kaprodi shell. */
    body.scm-admin-shell > .topbar,
    body.scm-admin-shell > .admin-navbar {
        background: #fff !important;
        color: #17202a !important;
        border-bottom: 1px solid #e5e7eb !important;
        box-shadow: 0 5px 18px rgba(23, 32, 42, .06) !important;
    }

    body.scm-admin-shell > .topbar .fw-bold,
    body.scm-admin-shell > .topbar .navbar-brand,
    body.scm-admin-shell > .admin-navbar .navbar-brand {
        color: #17202a !important;
    }

    body.scm-admin-shell > .topbar .text-white-50,
    body.scm-admin-shell > .topbar .text-white,
    body.scm-admin-shell > .admin-navbar .text-white-50,
    body.scm-admin-shell > .admin-navbar .text-white {
        color: #667085 !important;
    }

    body.scm-admin-shell > .topbar .btn-outline-light,
    body.scm-admin-shell > .admin-navbar .btn-outline-light {
        color: #475467 !important;
        background: #fff !important;
        border-color: #cbd5e1 !important;
    }

    body.scm-admin-shell > .topbar .btn-outline-light:hover,
    body.scm-admin-shell > .admin-navbar .btn-outline-light:hover {
        color: #17202a !important;
        background: #f8fafc !important;
        border-color: #98a2b3 !important;
    }

    body.scm-admin-shell > .topbar .btn-danger,
    body.scm-admin-shell > .topbar .btn-fik,
    body.scm-admin-shell > .admin-navbar .btn-danger,
    body.scm-admin-shell > .admin-navbar .btn-fik {
        color: #fff !important;
        background: #ff7900 !important;
        border-color: #ff7900 !important;
    }

    body.scm-admin-shell > .topbar .btn-danger:hover,
    body.scm-admin-shell > .topbar .btn-fik:hover,
    body.scm-admin-shell > .admin-navbar .btn-danger:hover,
    body.scm-admin-shell > .admin-navbar .btn-fik:hover {
        color: #fff !important;
        background: #e96d00 !important;
        border-color: #e96d00 !important;
    }

    body.scm-admin-shell > .admin-navbar .text-fik-orange,
    body.scm-admin-shell > .topbar .text-fik-orange {
        color: #ff7900 !important;
    }

    body.scm-admin-shell .admin-logout-button {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        min-width: 104px;
        min-height: 36px;
        padding: .45rem .9rem !important;
        border-radius: 999px !important;
        line-height: 1;
        white-space: nowrap;
        font-weight: 600;
    }

    body.scm-admin-shell .admin-logout-button i {
        font-size: .9rem;
        line-height: 1;
    }

    @media (max-width: 575.98px) {
        body.scm-admin-shell .admin-logout-button {
            min-width: 96px;
            min-height: 34px;
            padding-inline: .72rem !important;
        }
    }
</style>
<?php endif; ?>
