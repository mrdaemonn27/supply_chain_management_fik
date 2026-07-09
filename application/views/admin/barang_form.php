<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($aset) ? 'Edit' : 'Tambah' ?> Master Data - Admin SCM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-dark text-white p-4 rounded-top-4">
                        <h5 class="m-0 fw-bold text-warning">
                            <i class="bi <?= isset($aset) ? 'bi-pencil-square' : 'bi-database-add' ?> me-2"></i> 
                            Form <?= isset($aset) ? 'Edit' : 'Input' ?> Master Data Aset
                        </h5>
                        <small class="opacity-75">Pastikan data yang diinput sesuai dengan kode inventaris fisik laboratorium.</small>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        
                        <form action="<?= base_url('index.php/admin/barang/simpan') ?>" method="POST" enctype="multipart/form-data">
                            
                            <!-- KUNCI PERBAIKAN: Input hidden ID Aset memastikan Controller melakukan Update, bukan Insert baru -->
                            <input type="hidden" name="id_aset" value="<?= isset($aset) ? $aset->id_aset : '' ?>">

                            <div class="row mb-3 g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Kode Barang / Aset <span class="text-danger">*</span></label>
                                    <input type="text" name="kode_aset" class="form-control bg-light font-monospace" value="<?= isset($aset) ? $aset->kode_aset : '' ?>" placeholder="Contoh: MTL-001" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Nama Lengkap Barang <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_aset" class="form-control" value="<?= isset($aset) ? $aset->nama_aset : '' ?>" placeholder="Air Compressor Orange..." required>
                                </div>
                            </div>

                            <!-- TAMBAHAN: Field Deskripsi disesuaikan dengan skema Database -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Deskripsi / Spesifikasi Aset</label>
                                <textarea name="deskripsi" class="form-control" rows="3" placeholder="Masukkan spesifikasi atau keterangan lengkap barang..."><?= isset($aset) ? $aset->deskripsi : '' ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Lokasi Ruangan / Laboratorium <span class="text-danger">*</span></label>
                                <select name="id_ruangan" class="form-select" required>
                                    <option value="">-- Pilih Penempatan Laboratorium --</option>
                                    <?php foreach($ruangan as $r): ?>
                                        <?php $selected = (isset($aset) && $aset->id_ruangan == $r->id_ruangan) ? 'selected' : ''; ?>
                                        <option value="<?= $r->id_ruangan ?>" <?= $selected ?>><?= $r->nama_ruangan ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Foto / Gambar Aset</label>
                                <?php if(isset($aset) && !empty($aset->gambar)): ?>
                                    <div class="mb-2">
                                        <img src="<?= base_url($aset->gambar) ?>" alt="Preview Aset" class="img-thumbnail shadow-sm" style="max-height: 120px; border-radius: 8px;">
                                    </div>
                                    <small class="text-muted d-block mb-2"><i class="bi bi-info-circle"></i> Biarkan kosong jika tidak ingin mengubah gambar saat ini.</small>
                                <?php endif; ?>
                                <input type="file" name="gambar" class="form-control" accept="image/jpeg, image/png, image/jpg, image/webp">
                                <small class="form-text text-muted">Format yang didukung: JPG, JPEG, PNG, WEBP. Maksimal 2MB.</small>
                            </div>

                            <div class="row mb-4 p-4 bg-light rounded-3 border g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Total Unit Tersedia <span class="text-danger">*</span></label>
                                    <!-- Field diset readonly saat Edit agar stok tidak hancur berantakan -->
                                    <input type="number" name="jumlah_total" class="form-control" min="1" value="<?= isset($aset) ? $aset->jumlah_total : '1' ?>" <?= isset($aset) ? 'readonly title="Tidak bisa mengubah stok total melalui form edit."' : 'required' ?>>
                                    <?php if(isset($aset)): ?>
                                        <small class="text-danger d-block mt-1" style="font-size: 0.75rem;">Stok awal fisik tidak bisa diubah via form edit dasar.</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Kondisi Fisik <span class="text-danger">*</span></label>
                                    <select name="kondisi" class="form-select border-warning" required>
                                        <option value="Baik" <?= (isset($aset) && $aset->kondisi == 'Baik') ? 'selected' : '' ?>>Baik & Berfungsi</option>
                                        <option value="Rusak Ringan" <?= (isset($aset) && $aset->kondisi == 'Rusak Ringan') ? 'selected' : '' ?>>Rusak Ringan (Butuh Servis)</option>
                                        <option value="Rusak Berat" <?= (isset($aset) && $aset->kondisi == 'Rusak Berat') ? 'selected' : '' ?>>Rusak Berat / Afkir</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between pt-4 border-top">
                                <a href="<?= base_url('index.php/admin/barang') ?>" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
                                <button type="submit" class="btn btn-primary fw-bold rounded-pill px-5"><i class="bi bi-save me-1"></i> Simpan ke Database</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>