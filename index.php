<?php
// TUGAS MANDIRI: Class Buku, Anggota, Peminjaman
// Jalankan lewat XAMPP: http://localhost/perpustakaan_web.php
// Data disimpan sementara di SESSION (tanpa database).

// ---------- Class Buku ----------
class Buku {
    private $judul;
    private $penulis;
    private $stok;

    public function __construct($judul, $penulis, $stok) {
        $this->judul   = $judul;
        $this->penulis = $penulis;
        $this->stok    = $stok;
    }

    public function getJudul()   { return $this->judul; }
    public function getPenulis() { return $this->penulis; }
    public function getStok()    { return $this->stok; }

    public function kurangiStok() { $this->stok--; }
    public function tambahStok()  { $this->stok++; }
}

// ---------- Class Anggota ----------
class Anggota {
    private $nama;
    private $nim;

    public function __construct($nama, $nim) {
        $this->nama = $nama;
        $this->nim  = $nim;
    }

    public function getNama() { return $this->nama; }
    public function getNim()  { return $this->nim; }
}

// ---------- Class Peminjaman ----------
class Peminjaman {
    private $anggota;
    private $buku;
    private $status = "Dipinjam";
    private $denda  = 0;

    public function __construct($anggota, $buku) {
        $this->anggota = $anggota;
        $this->buku    = $buku;
    }

    public function getAnggota() { return $this->anggota; }
    public function getBuku()    { return $this->buku; }
    public function getStatus()  { return $this->status; }
    public function getDenda()   { return $this->denda; }

    // Return true kalau berhasil, false kalau stok habis
    public function pinjam() {
        if ($this->buku->getStok() > 0) {
            $this->buku->kurangiStok();
            return true;
        }
        return false;
    }

    // Return value = denda (Rp 1000 per hari terlambat)
    public function kembalikan($hariTerlambat) {
        $this->buku->tambahStok();
        $this->status = "Dikembalikan";
        $this->denda  = $hariTerlambat * 1000;
        return $this->denda;
    }
}

// ---------- Penyimpanan sementara (session) ----------
// session_start() harus setelah class didefinisikan
session_start();
foreach (['buku', 'anggota', 'peminjaman'] as $kunci) {
    if (!isset($_SESSION[$kunci])) {
        $_SESSION[$kunci] = [];
    }
}

// Supaya teks dari input aman ditampilkan
function e($teks) {
    return htmlspecialchars($teks);
}

// ---------- Proses form ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi  = $_POST['aksi'] ?? '';
    $pesan = '';

    if ($aksi == 'tambah_buku') {
        $judul   = trim($_POST['judul'] ?? '');
        $penulis = trim($_POST['penulis'] ?? '');
        $stok    = (int) ($_POST['stok'] ?? 0);

        if ($judul == '' || $penulis == '' || $stok < 1) {
            $pesan = "Gagal: judul, penulis, dan stok (minimal 1) harus diisi.";
        } else {
            $_SESSION['buku'][] = new Buku($judul, $penulis, $stok);
            $pesan = "Buku \"$judul\" berhasil ditambahkan.";
        }

    } elseif ($aksi == 'tambah_anggota') {
        $nama = trim($_POST['nama'] ?? '');
        $nim  = trim($_POST['nim'] ?? '');

        if ($nama == '' || $nim == '') {
            $pesan = "Gagal: nama dan NIM harus diisi.";
        } else {
            $_SESSION['anggota'][] = new Anggota($nama, $nim);
            $pesan = "Anggota $nama berhasil ditambahkan.";
        }

    } elseif ($aksi == 'pinjam') {
        $ia = $_POST['anggota'] ?? '';
        $ib = $_POST['buku'] ?? '';

        if (!isset($_SESSION['anggota'][$ia]) || !isset($_SESSION['buku'][$ib])) {
            $pesan = "Gagal: pilih anggota dan buku terlebih dahulu.";
        } else {
            $p = new Peminjaman($_SESSION['anggota'][$ia], $_SESSION['buku'][$ib]);
            if ($p->pinjam()) {
                $_SESSION['peminjaman'][] = $p;
                $pesan = $p->getAnggota()->getNama() . " berhasil meminjam buku " . $p->getBuku()->getJudul() . ".";
            } else {
                $pesan = "Gagal: stok buku " . $p->getBuku()->getJudul() . " habis.";
            }
        }

    } elseif ($aksi == 'kembalikan') {
        $ip   = $_POST['peminjaman'] ?? '';
        $hari = (int) ($_POST['hari'] ?? 0);

        if (!isset($_SESSION['peminjaman'][$ip]) || $hari < 0) {
            $pesan = "Gagal: pilih peminjaman dan isi hari terlambat (0 jika tepat waktu).";
        } else {
            $p = $_SESSION['peminjaman'][$ip];
            if ($p->getStatus() == "Dikembalikan") {
                $pesan = "Gagal: buku ini sudah dikembalikan.";
            } else {
                $denda = $p->kembalikan($hari);
                $pesan = "Buku " . $p->getBuku()->getJudul() . " dikembalikan. Denda: Rp " . number_format($denda, 0, ',', '.');
            }
        }
    }

    // Redirect supaya form tidak terkirim ulang saat halaman di-refresh
    $_SESSION['pesan'] = $pesan;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$pesan = $_SESSION['pesan'] ?? '';
unset($_SESSION['pesan']);
?>
<h1>Sistem Perpustakaan</h1>

<?php if ($pesan != '') { echo "<p>" . e($pesan) . "</p>"; } ?>

<h3>Tambah Buku</h3>
<form method="post">
    <input type="hidden" name="aksi" value="tambah_buku">
    Judul: <input type="text" name="judul"><br>
    Penulis: <input type="text" name="penulis"><br>
    Stok: <input type="number" name="stok" value="1"><br>
    <button type="submit">Simpan</button>
</form>

<h3>Tambah Anggota</h3>
<form method="post">
    <input type="hidden" name="aksi" value="tambah_anggota">
    Nama: <input type="text" name="nama"><br>
    NIM: <input type="text" name="nim"><br>
    <button type="submit">Simpan</button>
</form>

<h3>Pinjam Buku</h3>
<?php if (count($_SESSION['anggota']) == 0 || count($_SESSION['buku']) == 0): ?>
    Tambahkan minimal satu anggota dan satu buku terlebih dahulu.
<?php else: ?>
<form method="post">
    <input type="hidden" name="aksi" value="pinjam">
    Anggota:
    <select name="anggota">
        <?php foreach ($_SESSION['anggota'] as $i => $a): ?>
            <option value="<?= $i ?>"><?= e($a->getNama()) ?></option>
        <?php endforeach; ?>
    </select><br>
    Buku:
    <select name="buku">
        <?php foreach ($_SESSION['buku'] as $i => $b): ?>
            <option value="<?= $i ?>"><?= e($b->getJudul()) ?> (stok <?= $b->getStok() ?>)</option>
        <?php endforeach; ?>
    </select><br>
    <button type="submit">Pinjam</button>
</form>
<?php endif; ?>

<h3>Kembalikan Buku</h3>
<?php
// Ambil peminjaman yang masih berstatus "Dipinjam"
$aktif = [];
foreach ($_SESSION['peminjaman'] as $i => $p) {
    if ($p->getStatus() == "Dipinjam") {
        $aktif[$i] = $p;
    }
}
?>
<?php if (count($aktif) == 0): ?>
    Tidak ada buku yang sedang dipinjam.
<?php else: ?>
<form method="post">
    <input type="hidden" name="aksi" value="kembalikan">
    Peminjaman:
    <select name="peminjaman">
        <?php foreach ($aktif as $i => $p): ?>
            <option value="<?= $i ?>"><?= e($p->getAnggota()->getNama()) ?> - <?= e($p->getBuku()->getJudul()) ?></option>
        <?php endforeach; ?>
    </select><br>
    Hari terlambat: <input type="number" name="hari" value="0"><br>
    <button type="submit">Kembalikan</button>
</form>
<?php endif; ?>

<hr>

<h3>Daftar Buku</h3>
<?php
foreach ($_SESSION['buku'] as $i => $b) {
    echo ($i + 1) . ". " . e($b->getJudul()) . " | " . e($b->getPenulis()) . " | Stok: " . $b->getStok() . "<br>";
}
if (count($_SESSION['buku']) == 0) {
    echo "Belum ada data.<br>";
}
?>

<h3>Daftar Anggota</h3>
<?php
foreach ($_SESSION['anggota'] as $i => $a) {
    echo ($i + 1) . ". " . e($a->getNama()) . " | NIM: " . e($a->getNim()) . "<br>";
}
if (count($_SESSION['anggota']) == 0) {
    echo "Belum ada data.<br>";
}
?>

<h3>Riwayat Peminjaman</h3>
<?php
foreach ($_SESSION['peminjaman'] as $i => $p) {
    echo ($i + 1) . ". " . e($p->getAnggota()->getNama()) . " | " . e($p->getBuku()->getJudul()) . " | " . $p->getStatus();
    if ($p->getDenda() > 0) {
        echo " | Denda: Rp " . number_format($p->getDenda(), 0, ',', '.');
    }
    echo "<br>";
}
if (count($_SESSION['peminjaman']) == 0) {
    echo "Belum ada data.<br>";
}
?>