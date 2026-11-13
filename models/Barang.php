<?php
    class Barang {
        public const KATEGORI = ['Elektronik', 'Furnitur', 'Alat Tulis', 'Kebersihan', 'Lainnya'];
        public const KONDISI  = ['Baik', 'Rusak'];

        public const SORT = [
            'terbaru'     => ['Tanggal terbaru',     'tanggal_masuk DESC, id DESC'],
            'nama_asc'    => ['Nama A–Z',            'nama_barang ASC'],
            'nama_desc'   => ['Nama Z–A',            'nama_barang DESC'],
            'jumlah_desc' => ['Jumlah terbanyak',    'jumlah DESC'],
            'jumlah_asc'  => ['Jumlah tersedikit',   'jumlah ASC'],
        ];

        public function __construct(private mysqli $db) {}

        private function run(string $sql, string $types = '', array $params = []): mysqli_stmt {
            $st = $this->db->prepare($sql);
            if ($params) $st->bind_param($types, ...$params);
            $st->execute();
            return $st;
        }

        public function getAll(array $f = [], string $sort = 'terbaru'): array {
            $sql = "SELECT *,
                    CASE WHEN jumlah = 0 THEN 'Habis'
                        WHEN jumlah < stok_minimum THEN 'Stok Rendah'
                        ELSE 'Aman' END AS status_stok
                    FROM barang WHERE 1=1";
            $types = '';
            $p = [];
            if (($f['q'] ?? '') !== '')        { $sql .= ' AND nama_barang LIKE ?'; $types .= 's'; $p[] = '%' . $f['q'] . '%'; }
            if (($f['kategori'] ?? '') !== '') { $sql .= ' AND kategori = ?';       $types .= 's'; $p[] = $f['kategori']; }
            if (($f['kondisi'] ?? '') !== '')  { $sql .= ' AND kondisi = ?';        $types .= 's'; $p[] = $f['kondisi']; }
            if (($f['dari'] ?? '') !== '')     { $sql .= ' AND tanggal_masuk >= ?'; $types .= 's'; $p[] = $f['dari']; }
            if (($f['sampai'] ?? '') !== '')   { $sql .= ' AND tanggal_masuk <= ?'; $types .= 's'; $p[] = $f['sampai']; }

            $sql .= ' ORDER BY ' . (self::SORT[$sort][1] ?? self::SORT['terbaru'][1]);

            return $this->run($sql, $types, $p)->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        public function find(int $id): ?array {
            $row = $this->run('SELECT * FROM barang WHERE id = ?', 'i', [$id])->get_result()->fetch_assoc();
            return $row ?: null;
        }

        public function kodeExists(string $kode, ?int $exceptId = null): bool {
            if ($exceptId !== null) {
                $st = $this->run('SELECT COUNT(*) FROM barang WHERE kode_barang = ? AND id <> ?', 'si', [$kode, $exceptId]);
            } else {
                $st = $this->run('SELECT COUNT(*) FROM barang WHERE kode_barang = ?', 's', [$kode]);
            }
            return (int) $st->get_result()->fetch_row()[0] > 0;
        }

        public function create(array $d): bool {
            $ket = $d['keterangan'] !== '' ? $d['keterangan'] : null;
            $this->run(
                'INSERT INTO barang (kode_barang, nama_barang, kategori, jumlah, kondisi, tanggal_masuk, keterangan, stok_minimum)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                'sssisssi',
                [$d['kode_barang'], $d['nama_barang'], $d['kategori'], $d['jumlah'], $d['kondisi'], $d['tanggal_masuk'], $ket, $d['stok_minimum']]
            );
            return true;
        }

        public function update(int $id, array $d): bool {
            $ket = $d['keterangan'] !== '' ? $d['keterangan'] : null;
            $this->run(
                'UPDATE barang SET kode_barang=?, nama_barang=?, kategori=?, jumlah=?, kondisi=?, tanggal_masuk=?, keterangan=?, stok_minimum=?
                WHERE id=?',
                'sssisssii',
                [$d['kode_barang'], $d['nama_barang'], $d['kategori'], $d['jumlah'], $d['kondisi'], $d['tanggal_masuk'], $ket, $d['stok_minimum'], $id]
            );
            return true;
        }

        public function delete(int $id): bool {
            return $this->run('DELETE FROM barang WHERE id = ?', 'i', [$id])->affected_rows > 0;
        }

        public function stats(): array {
            return $this->db->query(
                "SELECT COUNT(*) AS jenis,
                        COALESCE(SUM(jumlah), 0) AS total_unit,
                        COALESCE(SUM(CASE WHEN kondisi = 'Baik'  THEN jumlah ELSE 0 END), 0) AS unit_baik,
                        COALESCE(SUM(CASE WHEN kondisi = 'Rusak' THEN jumlah ELSE 0 END), 0) AS unit_rusak
                FROM barang"
            )->fetch_assoc();
        }
    }
?>