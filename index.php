<?php
$dataPath = __DIR__ . '/data/db.json';

function load_db($path){
  if(!file_exists($path)){
    return [];
  }
  $raw = file_get_contents($path);
  $arr = json_decode($raw, true);
  return is_array($arr) ? $arr : [];
}

function save_db($path, $data){
  $json = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  $tmp = $path . '.tmp';
  file_put_contents($tmp, $json, LOCK_EX);
  rename($tmp, $path);
}

$errors = [];
$lastEntry = null;
$savedOk = false;

// Handle POST (biodata)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama    = trim($_POST['nama']    ?? '');
  $nim     = trim($_POST['nim']     ?? '');
  $prodi   = trim($_POST['prodi']   ?? '');
  $gender  = trim($_POST['gender']  ?? '');
  $hobi    = $_POST['hobi'] ?? [];
  $alamat  = trim($_POST['alamat']  ?? '');

  // Basic validation
  if ($nama === '')   $errors[] = 'Nama Lengkap wajib diisi.';
  if ($nim === '')    $errors[] = 'NIM wajib diisi.';
  if ($prodi === '')  $errors[] = 'Program Studi wajib dipilih.';
  if ($gender === '') $errors[] = 'Jenis Kelamin wajib dipilih.';
  if (empty($hobi))   $errors[] = 'Pilih minimal satu hobi.';
  if ($alamat === '') $errors[] = 'Alamat wajib diisi.';

  // If OK, persist to JSON and prepare last entry
  if (empty($errors)){
    $entry = [
      'timestamp' => date('c'),
      'nama'      => $nama,
      'nim'       => $nim,
      'prodi'     => $prodi,
      'gender'    => $gender,
      'hobi'      => array_values((array)$hobi),
      'alamat'    => $alamat
    ];
    $db = load_db($dataPath);
    $db[] = $entry;
    save_db($dataPath, $db);
    $lastEntry = $entry;
    $savedOk = true;
  }
}

// Handle GET (search)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];
if ($q !== '') {
  $all = load_db($dataPath);
  $qLower = mb_strtolower($q, 'UTF-8');
  foreach($all as $row){
    $nama  = mb_strtolower($row['nama']  ?? '', 'UTF-8');
    $nim   = mb_strtolower($row['nim']   ?? '', 'UTF-8');
    $prodi = mb_strtolower($row['prodi'] ?? '', 'UTF-8');
    // Cocokkan substring pada Nama, NIM, atau Prodi (jurusan)
    if (mb_strpos($nama, $qLower) !== false ||
        mb_strpos($nim, $qLower) !== false ||
        mb_strpos($prodi, $qLower) !== false) {
      $results[] = $row;
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Form Biodata & Pencarian</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="logo">PW</div>
      <div>
        <h1 class="h1">Pemrograman Web </h1>
        <div class="sub">selamat datang di web sederhana</div>
      </div>
    </div>

    <div class="grid">
      <!-- Biodata (POST) -->
      <div class="card">
        <p class="section-title">Form Biodata (POST)</p>
        <?php if (!empty($errors)): ?>
          <div class="error">
            <?php foreach($errors as $e){ echo htmlspecialchars($e) . "<br>"; } ?>
          </div>
        <?php elseif ($savedOk): ?>
          <div class="success">Data berhasil disimpan ke <code>data/db.json</code>.</div>
        <?php else: ?>
          <p class="note">Isi biodata Anda lalu submit. Setelah terkirim, data akan tampil sebagai tabel di bawah form ini.</p>
        <?php endif; ?>

        <form method="post" action="" novalidate>
          <div class="row" style="margin-bottom:8px">
            <div style="flex:1;min-width:260px">
              <label for="nama">Nama Lengkap</label>
              <input class="input" type="text" id="nama" name="nama" placeholder="Contoh: Jamaludin" required>
            </div>
            <div style="flex:1;min-width:220px">
              <label for="nim">NIM</label>
              <input class="input" type="text" id="nim" name="nim" placeholder="Contoh: 3337240000" required>
            </div>
          </div>

          <div class="row" style="margin-bottom:8px">
            <div style="flex:1;min-width:240px">
              <label for="prodi">Program Studi</label>
              <select id="prodi" name="prodi" required>
                <option value="">-- Pilih Prodi --</option>
                <option value="Informatika">Informatika</option>
                <option value="Sistem Informasi">Sistem Informasi</option>
                <option value="Teknik Elektro">Teknik Elektro</option>
              </select>
            </div>
            <div style="flex:1;min-width:240px">
              <label>Jenis Kelamin</label>
              <div class="row">
                <label class="chip"><input type="radio" name="gender" value="Laki-laki"> Laki-laki</label>
                <label class="chip"><input type="radio" name="gender" value="Perempuan"> Perempuan</label>
              </div>
            </div>
          </div>

          <div style="margin-bottom:8px">
            <label>Hobi</label>
            <div class="row">
              <label class="chip"><input type="checkbox" name="hobi[]" value="Membaca"> Membaca</label>
              <label class="chip"><input type="checkbox" name="hobi[]" value="Olahraga"> Olahraga</label>
              <label class="chip"><input type="checkbox" name="hobi[]" value="Musik"> Musik</label>
              <label class="chip"><input type="checkbox" name="hobi[]" value="Coding"> Coding</label>
              <label class="chip"><input type="checkbox" name="hobi[]" value="Gaming"> Gaming</label>
            </div>
          </div>

          <div style="margin-bottom:12px">
            <label for="alamat">Alamat</label>
            <textarea id="alamat" name="alamat" placeholder="Tulis alamat lengkap Anda"></textarea>
          </div>

          <button class="btn" type="submit">Kirim Biodata</button>
        </form>

        <?php if ($lastEntry): ?>
          <div class="table-wrap" style="margin-top:16px">
            <table>
              <thead>
                <tr><th colspan="2">Biodata Terkirim</th></tr>
              </thead>
              <tbody>
                <tr><th>Nama Lengkap</th><td><?php echo htmlspecialchars($lastEntry['nama']); ?></td></tr>
                <tr><th>NIM</th><td><?php echo htmlspecialchars($lastEntry['nim']); ?></td></tr>
                <tr><th>Program Studi</th><td><?php echo htmlspecialchars($lastEntry['prodi']); ?></td></tr>
                <tr><th>Jenis Kelamin</th><td><?php echo htmlspecialchars($lastEntry['gender']); ?></td></tr>
                <tr><th>Hobi</th><td><?php echo htmlspecialchars(implode(', ', $lastEntry['hobi'])); ?></td></tr>
                <tr><th>Alamat</th><td><?php echo nl2br(htmlspecialchars($lastEntry['alamat'])); ?></td></tr>
                <tr><th>Waktu Simpan</th><td><?php echo htmlspecialchars($lastEntry['timestamp']); ?></td></tr>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Pencarian (GET) -->
      <div class="card">
        <p class="section-title">Form Pencarian (GET)</p>
        <p class="note">Ketik Nama, NIM, atau Prodi (jurusan) lalu tekan Cari.</p>
        <form method="get" action="">
          <label for="q">Kata kunci</label>
          <input class="input" type="text" id="q" name="q" placeholder="mis. 'Informatika', 'Rin', '231234567'">
          <div style="margin-top:12px">
            <button class="btn" type="submit">Cari</button>
          </div>
        </form>

        <?php if ($q !== ''): ?>
          <p class="search-result" style="margin-top:14px">
            Hasil pencarian untuk: <em><?php echo htmlspecialchars($q); ?></em>
          </p>

          <div class="table-wrap" style="margin-top:8px">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nama</th>
                  <th>NIM</th>
                  <th>Prodi</th>
                  <th>Gender</th>
                  <th>Hobi</th>
                  <th>Waktu</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($results)): ?>
                  <tr><td colspan="7" class="note">Tidak ada data yang cocok.</td></tr>
                <?php else: ?>
                  <?php $i=1; foreach($results as $row): ?>
                    <tr>
                      <td><?php echo $i++; ?></td>
                      <td><?php echo htmlspecialchars($row['nama'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($row['nim'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($row['prodi'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($row['gender'] ?? ''); ?></td>
                      <td><?php
                        $h = isset($row['hobi']) && is_array($row['hobi']) ? implode(', ', $row['hobi']) : '';
                        echo htmlspecialchars($h);
                      ?></td>
                      <td><?php echo htmlspecialchars($row['timestamp'] ?? ''); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card" style="margin-top:16px">
      <p class="section-title">Riwayat Submisi</p>
      <p class="note">Berikut ringkasan semua data yang tersimpan di <code>data/db.json</code>.</p>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Nama</th>
              <th>NIM</th>
              <th>Prodi</th>
              <th>Gender</th>
              <th>Hobi</th>
              <th>Waktu</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $all = load_db($dataPath);
              if (empty($all)){
                echo '<tr><td colspan="7" class="note">Belum ada data.</td></tr>';
              } else {
                $i = 1;
                foreach($all as $row){
                  echo '<tr>';
                  echo '<td>'.($i++).'</td>';
                  echo '<td>'.htmlspecialchars($row['nama'] ?? '').'</td>';
                  echo '<td>'.htmlspecialchars($row['nim'] ?? '').'</td>';
                  echo '<td>'.htmlspecialchars($row['prodi'] ?? '').'</td>';
                  echo '<td>'.htmlspecialchars($row['gender'] ?? '').'</td>';
                  $h = isset($row['hobi']) && is_array($row['hobi']) ? implode(', ', $row['hobi']) : '';
                  echo '<td>'.htmlspecialchars($h).'</td>';
                  echo '<td>'.htmlspecialchars($row['timestamp'] ?? '').'</td>';
                  echo '</tr>';
                }
              }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="footer">© 2025 Pemrograman Web — Contoh aplikasi sederhana.</div>
  </div>
</body>
</html>
