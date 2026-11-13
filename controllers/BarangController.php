<?php
    require_once __DIR__ . '/../models/Barang.php';

    class BarangController {
        private Barang $model;

        public function __construct(mysqli $db) {
            $this->model = new Barang($db);
        }

        public function handle(): void {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $aksi = $_POST['aksi'] ?? '';
                if (!$this->csrfValid()) {
                    $this->flash('danger', 'Sesi tidak valid. Silakan ulangi.');
                    $this->redirect();
                }
                if ($aksi === 'hapus') $this->hapus();
                if ($aksi === 'simpan') { $this->simpan(); return; }
                $this->redirect();
            }
            $this->tampil();
        }

        private function tampil(array $errors = [], ?array $old = null): void {
            $filter = [
                'q'        => trim($_GET['q'] ?? ''),
                'kategori' => in_array($_GET['kategori'] ?? '', Barang::KATEGORI, true) ? $_GET['kategori'] : '',
                'kondisi'  => in_array($_GET['kondisi'] ?? '', Barang::KONDISI, true) ? $_GET['kondisi'] : '',
                'dari'     => $this->validDate($_GET['dari'] ?? '') ? $_GET['dari'] : '',
                'sampai'   => $this->validDate($_GET['sampai'] ?? '') ? $_GET['sampai'] : '',
            ];
            $today = date('Y-m-d');
            $filterErrors = [];
            if ($filter['sampai'] !== '' && $filter['sampai'] > $today) {
                $filterErrors['sampai'] = 'Tanggal "Sampai" tidak boleh melebihi hari ini.';
            }
            if ($filter['dari'] !== '' && $filter['dari'] > $today) {
                $filterErrors['dari'] = 'Tanggal "Dari" tidak boleh melebihi hari ini.';
            } elseif ($filter['dari'] !== '' && $filter['sampai'] !== '' && $filter['dari'] > $filter['sampai']) {
                $filterErrors['dari'] = 'Tanggal "Dari" tidak boleh melebihi tanggal "Sampai".';
            }
            $sort = array_key_exists($_GET['sort'] ?? '', Barang::SORT) ? $_GET['sort'] : 'terbaru';

            $editId = (int) ($_GET['edit'] ?? 0);
            $form = $old;
            if ($form === null) {
                $row = $editId ? $this->model->find($editId) : null;
                if ($editId && !$row) $this->flash('warning', 'Data yang akan diedit tidak ditemukan.');
                $form = $row ?? [
                    'id' => '', 'kode_barang' => '', 'nama_barang' => '', 'kategori' => '', 'jumlah' => '',
                    'kondisi' => '', 'tanggal_masuk' => date('Y-m-d'), 'keterangan' => '', 'stok_minimum' => 5,
                ];
            }

            $data = [
                'rows'     => $filterErrors ? [] : $this->model->getAll($filter, $sort),
                'stats'    => $this->model->stats(),
                'filter'   => $filter,
                'filterErrors' => $filterErrors,
                'sort'     => $sort,
                'form'     => $form,
                'errors'   => $errors,
                'flash'    => $this->takeFlash(),
                'csrf'     => $this->csrfToken(),
                'today'    => date('Y-m-d'),
                'queryStr' => $_SERVER['QUERY_STRING'] ?? '',
            ];
            extract($data);
            require __DIR__ . '/../views/index.php';
        }

        private function simpan(): void {
            $id = (int) ($_POST['id'] ?? 0);
            $d = [
                'kode_barang'   => trim($_POST['kode_barang'] ?? ''),
                'nama_barang'   => trim($_POST['nama_barang'] ?? ''),
                'kategori'      => $_POST['kategori'] ?? '',
                'jumlah'        => trim($_POST['jumlah'] ?? ''),
                'kondisi'       => $_POST['kondisi'] ?? '',
                'tanggal_masuk' => $_POST['tanggal_masuk'] ?? '',
                'keterangan'    => trim($_POST['keterangan'] ?? ''),
                'stok_minimum'  => trim($_POST['stok_minimum'] ?? ''),
            ];
            $errors = $this->validasi($d, $id ?: null);

            if ($errors) {
                $d['id'] = $id ?: '';
                $this->flash('danger', 'Data gagal disimpan. Periksa kembali isian form.');
                $this->tampil($errors, $d);
                return;
            }

            $d['jumlah'] = (int) $d['jumlah'];
            $d['stok_minimum'] = (int) $d['stok_minimum'];
            try {
                if ($id) {
                    $this->model->update($id, $d);
                    $this->flash('success', 'Data barang berhasil diperbarui.');
                } else {
                    $this->model->create($d);
                    $this->flash('success', 'Data barang berhasil ditambahkan.');
                }
            } catch (mysqli_sql_exception $e) {
                $this->flash('danger', 'Terjadi kesalahan saat menyimpan data.');
            }
            $this->redirect();
        }

        private function hapus(): void {
            $id = (int) ($_POST['id'] ?? 0);
            try {
                $ok = $id && $this->model->delete($id);
                $this->flash($ok ? 'success' : 'warning', $ok ? 'Data barang berhasil dihapus.' : 'Data tidak ditemukan.');
            } catch (mysqli_sql_exception $e) {
                $this->flash('danger', 'Data gagal dihapus.');
            }
            $this->redirect();
        }

        private function validasi(array $d, ?int $id): array {
            $e = [];
            if ($d['kode_barang'] === '') $e['kode_barang'] = 'Kode barang wajib diisi.';
            elseif (mb_strlen($d['kode_barang']) > 10) $e['kode_barang'] = 'Kode barang maksimal 10 karakter.';
            elseif ($this->model->kodeExists($d['kode_barang'], $id)) $e['kode_barang'] = 'Kode barang sudah digunakan.';

            if ($d['nama_barang'] === '') $e['nama_barang'] = 'Nama barang wajib diisi.';
            elseif (mb_strlen($d['nama_barang']) > 100) $e['nama_barang'] = 'Nama barang maksimal 100 karakter.';

            if (!in_array($d['kategori'], Barang::KATEGORI, true)) $e['kategori'] = 'Kategori wajib dipilih.';

            if ($d['jumlah'] === '') $e['jumlah'] = 'Jumlah wajib diisi.';
            elseif (!ctype_digit($d['jumlah'])) $e['jumlah'] = 'Jumlah harus bilangan bulat ≥ 0.';
            elseif (strlen($d['jumlah']) > 9) $e['jumlah'] = 'Jumlah terlalu besar.';

            if (!in_array($d['kondisi'], Barang::KONDISI, true)) $e['kondisi'] = 'Kondisi wajib dipilih.';

            if ($d['tanggal_masuk'] === '') $e['tanggal_masuk'] = 'Tanggal masuk wajib diisi.';
            elseif (!$this->validDate($d['tanggal_masuk'])) $e['tanggal_masuk'] = 'Format tanggal tidak valid.';
            elseif ($d['tanggal_masuk'] > date('Y-m-d')) $e['tanggal_masuk'] = 'Tanggal masuk tidak boleh di masa depan.';

            if ($d['stok_minimum'] === '' || !ctype_digit($d['stok_minimum']) || (int) $d['stok_minimum'] < 1 || strlen($d['stok_minimum']) > 9)
                $e['stok_minimum'] = 'Stok minimum harus bilangan bulat > 0.';

            return $e;
        }

        private function validDate(string $s): bool {
            $dt = DateTime::createFromFormat('Y-m-d', $s);
            return $dt && $dt->format('Y-m-d') === $s;
        }
        private function flash(string $type, string $msg): void {
            $_SESSION['flash'] = [$type, $msg];
        }
        private function takeFlash(): ?array {
            $f = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
            return $f;
        }
        private function csrfToken(): string {
            return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
        }
        private function csrfValid(): bool {
            return isset($_SESSION['csrf'], $_POST['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
        }
        private function redirect(): void {
            header('Location: index.php' . (!empty($_POST['kembali']) ? '?' . ltrim($_POST['kembali'], '?') : ''));
            exit;
        }
    }
?>