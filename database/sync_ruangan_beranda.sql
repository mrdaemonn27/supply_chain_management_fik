-- Sinkronisasi idempoten ruang showcase beranda dengan aset WebP di
-- assets/uploads/barang. Aman dijalankan ulang karena pencocokan memakai nama.

DROP TEMPORARY TABLE IF EXISTS room_showcase_seed;
CREATE TEMPORARY TABLE room_showcase_seed (
    nama_ruangan VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    warna VARCHAR(20) NOT NULL,
    foto VARCHAR(255) NOT NULL,
    deskripsi TEXT NOT NULL
);

INSERT INTO room_showcase_seed (nama_ruangan, icon, warna, foto, deskripsi) VALUES
('Aula FIK', 'building', '#F97316', 'aula.webp', 'Ruang serbaguna untuk seminar, pameran, presentasi, dan kegiatan akademik Fakultas Industri Kreatif.'),
('Lab Audio', 'soundwave', '#8B5CF6', 'LAB AUDIO.webp', 'Laboratorium produksi audio untuk rekaman, penyuntingan suara, dan pengembangan karya audiovisual.'),
('Lab Batik', 'palette-fill', '#B45309', 'lab batik.blend.webp', 'Ruang eksplorasi batik untuk proses desain motif, pencantingan, pewarnaan, dan produksi tekstil.'),
('Lab CGI', 'badge-3d-fill', '#2563EB', 'lab cgi.webp', 'Laboratorium computer-generated imagery untuk pemodelan, animasi, rendering, dan produksi visual digital.'),
('Lab Finishing', 'brush-fill', '#059669', 'Iab finishingblend.webp', 'Laboratorium penyelesaian karya untuk proses perakitan, penghalusan, pewarnaan, dan kontrol kualitas.'),
('Lab Green Screen', 'camera-reels-fill', '#16A34A', 'lab greenscreen..webp', 'Studio green screen untuk produksi video, compositing, virtual set, dan eksperimen sinematografi.'),
('Lab Idealoka', 'lightbulb-fill', '#DB2777', 'lab idealoka.webp', 'Ruang kolaborasi ide dan pengembangan konsep kreatif lintas disiplin.'),
('Lab Incubator', 'rocket-takeoff-fill', '#EA580C', 'lab incubator.webp', 'Ruang inkubasi untuk pengembangan prototipe, bisnis kreatif, dan kolaborasi proyek mahasiswa.'),
('Lab Lukis', 'easel2-fill', '#DC2626', 'lab lukis.webp', 'Studio seni lukis untuk eksplorasi medium, warna, komposisi, dan praktik seni rupa.'),
('Lab Mac', 'apple', '#475569', 'lab Mac.webp', 'Laboratorium komputer Mac untuk desain, multimedia, penyuntingan, dan produksi konten digital.'),
('Lab Multimedia', 'display', '#8B5CF6', 'lab multimedia.webp', 'Laboratorium Multimedia untuk praktikum desain grafis, editing video, dan animasi.'),
('Lab Pola dan Jahit', 'scissors', '#7C3AED', 'lab pola dan jahit.webp', 'Laboratorium busana untuk pembuatan pola, pemotongan bahan, penjahitan, dan penyelesaian produk fesyen.'),
('Lab Sablon', 'layers-fill', '#0891B2', 'lab sablon.webp', 'Laboratorium cetak saring untuk persiapan desain, afdruk, pencampuran tinta, dan produksi sablon.'),
('Lab Tab Cintiq', 'tablet-landscape', '#4F46E5', 'lab tab cintiq.webp', 'Laboratorium ilustrasi digital dengan perangkat pen display untuk menggambar, desain, dan animasi.');

UPDATE ruangan target
JOIN room_showcase_seed seed
  ON LOWER(TRIM(target.nama_ruangan)) = LOWER(TRIM(seed.nama_ruangan))
SET target.icon = seed.icon,
    target.warna = seed.warna,
    target.foto = seed.foto,
    target.deskripsi = seed.deskripsi;

INSERT INTO ruangan (nama_ruangan, icon, warna, foto, deskripsi)
SELECT seed.nama_ruangan, seed.icon, seed.warna, seed.foto, seed.deskripsi
FROM room_showcase_seed seed
WHERE NOT EXISTS (
    SELECT 1
    FROM ruangan existing
    WHERE LOWER(TRIM(existing.nama_ruangan)) = LOWER(TRIM(seed.nama_ruangan))
);

DROP TEMPORARY TABLE room_showcase_seed;
