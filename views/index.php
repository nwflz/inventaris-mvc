<?php
  $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
  $err = fn($k) => isset($errors[$k]) ? '<small class="err">' . $e($errors[$k]) . '</small>' : '';
  $inv = fn($k) => isset($errors[$k]) ? ' invalid' : '';
  $fInv = fn($k) => isset($filterErrors[$k]) ? ' invalid' : '';
  $isEdit = $form['id'] !== '' && $form['id'] !== null;

  parse_str($queryStr, $qs);
  unset($qs['edit']);
  $kembali = http_build_query($qs);
  $fmt = fn($d) => date('d M Y', strtotime($d));
  $pill = ['Habis' => 'red', 'Stok Rendah' => 'amber', 'Aman' => 'green'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inventaris Kelas</title>
  <style>
    :root {
      --bg:#f5f7fa; --panel:#ffffff; --line:#d9dee5; --text:#1b2330; --muted:#667085;
      --accent:#2563eb; --green:#15803d; --red:#dc2626; --amber:#b45309; --r:12px;
    }
    * { box-sizing:border-box; }
    body { margin:0; background:var(--bg); color:var(--text); color-scheme:light;
          font:14px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; }
    .wrap { max-width:1180px; margin:0 auto; padding:28px 16px 56px; }
    h1 { font-size:20px; font-weight:600; margin:0 0 20px; letter-spacing:-.01em; }
    h2 { font-size:13px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; margin:0 0 16px; }

    .panel { background:var(--panel); border:1px solid var(--line); border-radius:var(--r); padding:20px; }
    .grid { display:grid; grid-template-columns:320px 1fr; gap:20px; align-items:start; }
    @media (max-width:900px){ .grid{ grid-template-columns:1fr; } }

    .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
    @media (max-width:700px){ .stats{ grid-template-columns:repeat(2,1fr); } }
    .stat { background:var(--panel); border:1px solid var(--line); border-radius:var(--r); padding:14px 16px; }
    .stat span { color:var(--muted); font-size:12px; }
    .stat b { display:block; font-size:26px; font-weight:600; margin-top:2px; }
    .stat.ok b { color:var(--green); } .stat.bad b { color:var(--red); }

    label { display:block; font-size:12px; color:var(--muted); margin:0 0 5px; }
    input[type=text], input[type=number], input[type=date], select, textarea {
      width:100%; background:#ffffff; color:var(--text); border:1px solid var(--line);
      border-radius:8px; padding:9px 11px; font:inherit; outline:none; transition:border-color .15s;
    }
    input:focus, select:focus, textarea:focus { border-color:var(--accent); }
    .invalid { border-color:var(--red) !important; }
    .err { display:block; color:var(--red); font-size:12px; margin-top:4px; }
    .field { margin-bottom:14px; }
    .row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .radios { display:flex; gap:18px; }
    .radios label { display:flex; align-items:center; gap:6px; color:var(--text); font-size:14px; margin:0; cursor:pointer; }
    input[type=radio] { accent-color:var(--accent); }

    .btn { border:1px solid var(--line); background:transparent; color:var(--text); padding:8px 14px;
          border-radius:8px; font:inherit; cursor:pointer; text-decoration:none; display:inline-block; transition:.15s; }
    .btn:hover { border-color:var(--muted); }
    .btn.primary { background:var(--accent); border-color:var(--accent); color:#ffffff; font-weight:600; }
    .btn.primary:hover { filter:brightness(1.1); }
    .btn.sm { padding:4px 10px; font-size:12px; }
    .btn.danger:hover { border-color:var(--red); color:var(--red); }
    .actions { display:flex; gap:8px; margin-top:6px; }

    .filters { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:18px; align-items:end; }
    .filters .field { margin:0; }
    .filters .wide { grid-column:span 2; }
    .filters .ferr { grid-column:1 / -1; margin:0; }
    .filters .btns { display:flex; gap:8px; }
    .filters .btns .btn { flex:1; text-align:center; white-space:nowrap; padding:9px 8px; }
    @media (max-width:700px){ .filters{ grid-template-columns:1fr 1fr; } }

    .toast { padding:11px 14px; border-radius:10px; margin-bottom:18px; border:1px solid; display:flex; justify-content:space-between; }
    .toast.success { border-color:#86efac; background:#f0fdf4; color:var(--green); }
    .toast.danger  { border-color:#fca5a5; background:#fef2f2; color:var(--red); }
    .toast.warning { border-color:#fcd34d; background:#fffbeb; color:var(--amber); }
    .toast button { background:none; border:0; color:inherit; cursor:pointer; font-size:16px; }

    .tablewrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; }
    th { text-align:left; font-size:12px; font-weight:500; color:var(--muted); padding:8px 10px; border-bottom:1px solid var(--line); white-space:nowrap; }
    td { padding:11px 10px; border-bottom:1px solid var(--line); vertical-align:middle; }
    tr:last-child td { border-bottom:0; }
    tbody tr:hover { background:#f1f5f9; }
    .num { text-align:right; font-variant-numeric:tabular-nums; }
    .code { font-family:ui-monospace,Menlo,Consolas,monospace; color:var(--accent); font-size:13px; }
    .sub { color:var(--muted); font-size:12px; }
    .pill { font-size:11px; padding:2px 9px; border-radius:99px; border:1px solid; white-space:nowrap; }
    .pill.green { color:var(--green); border-color:#86efac; } .pill.red { color:var(--red); border-color:#fca5a5; }
    .pill.amber { color:var(--amber); border-color:#fcd34d; }
    .empty { text-align:center; color:var(--muted); padding:36px 0 !important; }
    .head { display:flex; justify-content:space-between; align-items:baseline; }
    .head .sub { margin-bottom:16px; }
    form.inline { display:inline; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Inventaris Kelas</h1>

    <?php if ($flash): ?>
      <div class="toast <?= $e($flash[0]) ?>"><span><?= $e($flash[1]) ?></span><button type="button" onclick="this.parentNode.remove()" aria-label="Tutup">×</button></div>
    <?php endif; ?>

    <div class="stats">
      <div class="stat"><span>Jenis barang</span><b><?= number_format((int) $stats['jenis'], 0, ',', '.') ?></b></div>
      <div class="stat"><span>Total unit</span><b><?= number_format((int) $stats['total_unit'], 0, ',', '.') ?></b></div>
      <div class="stat ok"><span>Unit baik</span><b><?= number_format((int) $stats['unit_baik'], 0, ',', '.') ?></b></div>
      <div class="stat bad"><span>Unit rusak</span><b><?= number_format((int) $stats['unit_rusak'], 0, ',', '.') ?></b></div>
    </div>

    <div class="grid">
      <div class="panel">
        <h2><?= $isEdit ? 'Edit barang' : 'Tambah barang' ?></h2>
        <form method="post" action="index.php" id="formBarang" novalidate>
          <input type="hidden" name="aksi" value="simpan">
          <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
          <input type="hidden" name="kembali" value="<?= $e($kembali) ?>">
          <input type="hidden" name="id" value="<?= $e($form['id']) ?>">

          <div class="field">
            <label for="kode_barang">Kode barang</label>
            <input type="text" class="<?= $inv('kode_barang') ?>" id="kode_barang" name="kode_barang" maxlength="10" value="<?= $e($form['kode_barang']) ?>">
            <?= $err('kode_barang') ?>
          </div>
          <div class="field">
            <label for="nama_barang">Nama barang</label>
            <input type="text" class="<?= $inv('nama_barang') ?>" id="nama_barang" name="nama_barang" maxlength="100" value="<?= $e($form['nama_barang']) ?>">
            <?= $err('nama_barang') ?>
          </div>
          <div class="field">
            <label for="kategori">Kategori</label>
            <select class="<?= $inv('kategori') ?>" id="kategori" name="kategori">
              <option value="">Pilih kategori</option>
              <?php foreach (Barang::KATEGORI as $k): ?>
                <option value="<?= $e($k) ?>" <?= $form['kategori'] === $k ? 'selected' : '' ?>><?= $e($k) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $err('kategori') ?>
          </div>
          <div class="row2 field">
            <div>
              <label for="jumlah">Jumlah</label>
              <input type="number" class="<?= $inv('jumlah') ?>" id="jumlah" name="jumlah" min="0" step="1" value="<?= $e($form['jumlah']) ?>">
              <?= $err('jumlah') ?>
            </div>
            <div>
              <label for="stok_minimum">Stok minimum</label>
              <input type="number" class="<?= $inv('stok_minimum') ?>" id="stok_minimum" name="stok_minimum" min="1" step="1" value="<?= $e($form['stok_minimum']) ?>">
              <?= $err('stok_minimum') ?>
            </div>
          </div>
          <div class="field">
            <label>Kondisi</label>
            <div class="radios">
              <?php foreach (Barang::KONDISI as $k): ?>
                <label><input type="radio" name="kondisi" value="<?= $e($k) ?>" <?= $form['kondisi'] === $k ? 'checked' : '' ?>><?= $e($k) ?></label>
              <?php endforeach; ?>
            </div>
            <?= $err('kondisi') ?>
          </div>
          <div class="field">
            <label for="tanggal_masuk">Tanggal masuk</label>
            <input type="date" class="<?= $inv('tanggal_masuk') ?>" id="tanggal_masuk" name="tanggal_masuk" max="<?= $e($today) ?>" value="<?= $e($form['tanggal_masuk']) ?>">
            <?= $err('tanggal_masuk') ?>
          </div>
          <div class="field">
            <label for="keterangan">Keterangan</label>
            <textarea id="keterangan" name="keterangan" rows="3"><?= $e($form['keterangan']) ?></textarea>
          </div>
          <div class="actions">
            <button type="submit" class="btn primary"><?= $isEdit ? 'Simpan' : 'Submit' ?></button>
            <button type="button" class="btn" id="btnReset">Reset</button>
            <?php if ($isEdit): ?><a class="btn" href="index.php<?= $kembali ? '?' . $e($kembali) : '' ?>">Batal</a><?php endif; ?>
          </div>
        </form>
      </div>

      <div class="panel">
        <div class="head"><h2>Daftar inventaris</h2><span class="sub"><?= count($rows) ?> data</span></div>

        <form method="get" action="index.php" class="filters">
          <div class="field wide"><label>Cari nama</label><input type="text" name="q" placeholder="Cari…" value="<?= $e($filter['q']) ?>"></div>
          <div class="field"><label>Kategori</label>
            <select name="kategori"><option value="">Semua</option>
              <?php foreach (Barang::KATEGORI as $k): ?><option value="<?= $e($k) ?>" <?= $filter['kategori'] === $k ? 'selected' : '' ?>><?= $e($k) ?></option><?php endforeach; ?>
            </select></div>
          <div class="field"><label>Kondisi</label>
            <select name="kondisi"><option value="">Semua</option>
              <?php foreach (Barang::KONDISI as $k): ?><option value="<?= $e($k) ?>" <?= $filter['kondisi'] === $k ? 'selected' : '' ?>><?= $e($k) ?></option><?php endforeach; ?>
            </select></div>
          <div class="field"><label>Dari</label><input type="date" name="dari" class="<?= trim($fInv('dari')) ?>" max="<?= $e($today) ?>" value="<?= $e($filter['dari']) ?>"></div>
          <div class="field"><label>Sampai</label><input type="date" name="sampai" class="<?= trim($fInv('sampai')) ?>" max="<?= $e($today) ?>" value="<?= $e($filter['sampai']) ?>"></div>
          <div class="field"><label>Urutkan</label>
            <select name="sort">
              <?php foreach (Barang::SORT as $key => [$label]): ?><option value="<?= $e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= $e($label) ?></option><?php endforeach; ?>
            </select></div>
          <div class="btns"><button class="btn primary">Terapkan</button><a href="index.php" class="btn">Reset Filter</a></div>
          <?php foreach ($filterErrors as $msg): ?><small class="err ferr"><?= $e($msg) ?></small><?php endforeach; ?>
        </form>

        <div class="tablewrap">
          <table>
            <thead>
              <tr><th>Kode</th><th>Nama</th><th>Kategori</th><th class="num">Jumlah</th><th>Stok</th><th>Kondisi</th><th>Masuk</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="8" class="empty">Tidak ada data yang cocok.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td class="code"><?= $e($r['kode_barang']) ?></td>
                <td><?= $e($r['nama_barang']) ?><?php if ($r['keterangan']): ?><div class="sub"><?= $e($r['keterangan']) ?></div><?php endif; ?></td>
                <td><?= $e($r['kategori']) ?></td>
                <td class="num"><?= (int) $r['jumlah'] ?></td>
                <td><span class="pill <?= $pill[$r['status_stok']] ?>"><?= $e($r['status_stok']) ?></span></td>
                <td><span class="pill <?= $r['kondisi'] === 'Baik' ? 'green' : 'red' ?>"><?= $e($r['kondisi']) ?></span></td>
                <td class="sub"><?= $fmt($r['tanggal_masuk']) ?></td>
                <td style="white-space:nowrap;text-align:right">
                  <a class="btn sm" href="index.php?<?= $e(http_build_query($qs + ['edit' => $r['id']])) ?>">Edit</a>
                  <form method="post" action="index.php" class="inline form-hapus" data-nama="<?= $e($r['nama_barang']) ?>">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                    <input type="hidden" name="kembali" value="<?= $e($kembali) ?>">
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <button class="btn sm danger">Hapus</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
  document.querySelectorAll('.form-hapus').forEach(f => f.addEventListener('submit', ev => {
    if (!confirm('Hapus barang "' + f.dataset.nama + '"? Tindakan ini tidak dapat dibatalkan.')) ev.preventDefault();
  }));
  document.getElementById('btnReset').addEventListener('click', () => {
    const f = document.getElementById('formBarang');
    f.querySelectorAll('input[type=text],input[type=number],textarea').forEach(i => i.value = '');
    f.querySelectorAll('input[type=radio]').forEach(i => i.checked = false);
    f.kategori.value = '';
    f.tanggal_masuk.value = '<?= $e($today) ?>';
    f.stok_minimum.value = 5;
    f.querySelectorAll('.invalid').forEach(i => i.classList.remove('invalid'));
    f.querySelectorAll('.err').forEach(i => i.remove());
    f.id.value = '';
  });
  </script>
</body>
</html>