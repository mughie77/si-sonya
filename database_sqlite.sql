-- SQLite Schema for SI-SONYA

-- Tabel Users (Admin, Guru, Siswa)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role TEXT CHECK(role IN ('admin', 'guru', 'siswa')) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Laporan Bullying
CREATE TABLE IF NOT EXISTS bullying_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pelapor_id INTEGER NOT NULL,
    terlapor_nama VARCHAR(100) NOT NULL,
    tanggal_kejadian DATE NOT NULL,
    lokasi VARCHAR(100) NOT NULL,
    deskripsi TEXT NOT NULL,
    bukti_foto VARCHAR(255),
    status TEXT CHECK(status IN ('pending', 'proses', 'selesai')) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelapor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Mood Tracking
CREATE TABLE IF NOT EXISTS mood_tracking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    mood_score INTEGER NOT NULL, -- 1: Sangat Sedih, 2: Sedih, 3: Biasa, 4: Senang, 5: Sangat Senang
    catatan TEXT,
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Feedback / Saran
CREATE TABLE IF NOT EXISTS feedback (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    isi_feedback TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Laporan Fasilitas
CREATE TABLE IF NOT EXISTS facility_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pelapor_id INTEGER NOT NULL,
    nama_fasilitas VARCHAR(100) NOT NULL,
    deskripsi_kerusakan TEXT NOT NULL,
    foto_fasilitas VARCHAR(255),
    status TEXT CHECK(status IN ('pending', 'proses', 'selesai')) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelapor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Default Admin (Password: password)
INSERT OR IGNORE INTO users (username, password, nama_lengkap, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator SI-SONYA', 'admin'),
('guru1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ibu Guru Sonya', 'guru'),
('siswa1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siswa Ceria', 'siswa');
