CREATE DATABASE IF NOT EXISTS si_sonya;
USE si_sonya;

-- Tabel Users (Admin, Guru, Siswa)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'guru', 'siswa') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Laporan Bullying
CREATE TABLE bullying_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelapor_id INT NOT NULL,
    terlapor_nama VARCHAR(100) NOT NULL,
    tanggal_kejadian DATE NOT NULL,
    lokasi VARCHAR(100) NOT NULL,
    deskripsi TEXT NOT NULL,
    bukti_foto VARCHAR(255),
    status ENUM('pending', 'proses', 'selesai') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelapor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Mood Tracking
CREATE TABLE mood_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood_score INT NOT NULL, -- 1: Sangat Sedih, 2: Sedih, 3: Biasa, 4: Senang, 5: Sangat Senang
    catatan TEXT,
    tanggal DATE NOT NULL DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Feedback / Saran
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    isi_feedback TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Laporan Fasilitas
CREATE TABLE facility_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelapor_id INT NOT NULL,
    nama_fasilitas VARCHAR(100) NOT NULL,
    deskripsi_kerusakan TEXT NOT NULL,
    foto_fasilitas VARCHAR(255),
    status ENUM('pending', 'proses', 'selesai') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelapor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Default Admin
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator SI-SONYA', 'admin'),
('guru1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ibu Guru Sonya', 'guru'),
('siswa1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siswa Ceria', 'siswa');

-- Default Password for all: password
