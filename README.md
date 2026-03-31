# SI-SONYA (Sistem Informasi Sekolah Aman dan Nyaman)

Aplikasi web untuk menciptakan lingkungan sekolah yang bebas bullying, peduli terhadap kesehatan mental, dan mendukung pemeliharaan fasilitas sekolah yang baik.

## Fitur Utama
1. **Lapor Bullying**: Melaporkan tindakan penindasan secara aman dan terjaga kerahasiaannya.
2. **Mood Tracker**: Memantau kesehatan mental harian siswa dan guru melalui emoji.
3. **Lapor Fasilitas**: Membantu sekolah dalam menjaga kualitas sarana prasarana melalui pelaporan kerusakan secara cepat.
4. **Feedback / Saran**: Menyuarakan aspirasi untuk perkembangan sekolah yang lebih baik.
5. **Dashboard Admin**: Pengelolaan data pengguna (Siswa, Guru, Admin) dan penanganan laporan masuk.

## Teknologi yang Digunakan
- **PHP Native**: Logika aplikasi dan manipulasi data.
- **MySQL (PDO)**: Basis data relasional.
- **Tailwind CSS**: Desain UI yang responsif, modern, dan menarik (Fun, Formal, Mewah).
- **Google Fonts (Poppins)**: Tipografi yang modern dan nyaman dibaca.

## Instalasi
1. Clone repositori ini.
2. Buat database bernama `si_sonya` di MySQL.
3. Impor file `database.sql` ke dalam database tersebut.
4. Sesuaikan konfigurasi database di `config/database.php`.
5. Jalankan aplikasi di server lokal (misalnya XAMPP atau PHP built-in server).

## Login Default
- **Admin**: `admin` / `password`
- **Guru**: `guru1` / `password`
- **Siswa**: `siswa1` / `password`
