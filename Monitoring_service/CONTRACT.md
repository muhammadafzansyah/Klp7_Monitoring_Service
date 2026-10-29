# Monitoring Service Mini-LMS

## 1. Deskripsi

Monitoring Service digunakan untuk memeriksa kondisi layanan pada sistem Mini-LMS. Service ini dapat memeriksa dirinya sendiri, memeriksa satu service berdasarkan nama, dan memeriksa seluruh service yang terdaftar pada Gateway.

## 2. Struktur File

```text
Monitoring_service/
├── api/
│   ├── health.php
│   └── health_all.php
├── health_functions.php
└── README.md
```

## 3. Daftar Service

| Nama Service | Base URL                     | Port |
| ------------ | ---------------------------- | ---: |
| Auth         | `http://10.194.169.154:8102` | 8102 |
| Course       | `http://10.194.169.45:8103`  | 8103 |
| Assignment   | `http://10.22.14.154:8104`   | 8104 |
| Submission   | `http://10.194.169.208:8105` | 8105 |
| Notification | `http://10.22.14.154:8106`   | 8106 |

Daftar ini mengikuti data `service_routes` dari Gateway yang tersedia. Jika ada service lain, tambahkan sesuai konfigurasi Gateway.

## 4. Endpoint dan Detail Respons

### A. Memeriksa Monitoring Service

**Request**

```http
GET http://localhost/Monitoring_service/api/health.php
```

**Status HTTP:** `200 OK`

**Contoh respons**

```json
{
  "success": true,
  "service": "Monitoring_service",
  "status": "UP",
  "message": "Health Checker berjalan dengan baik",
  "checked_at": "2026-10-29T06:00:00+00:00"
}
```

**Keterangan:**

- `success`: Menunjukkan permintaan berhasil diproses.
- `service`: Nama service yang diperiksa.
- `status`: Kondisi Monitoring Service, yaitu `UP`.
- `message`: Informasi bahwa service berjalan dengan baik.
- `checked_at`: Waktu pemeriksaan dilakukan.

Nilai waktu di atas hanya contoh. Respons sebenarnya mengikuti waktu saat permintaan dijalankan.

### B. Memeriksa Satu Service

**Request**

```http
GET http://localhost/Monitoring_service/api/health.php?name=auth
```

**Status HTTP:** `200 OK` jika permintaan berhasil diproses dan nama service ditemukan.

**Contoh respons ketika service UP**

```json
{
  "success": true,
  "checked_at": "2026-10-29T06:00:00+00:00",
  "service": {
    "name": "auth",
    "base_url": "http://10.194.169.154:8102",
    "health_url": "http://10.194.169.154:8102/health",
    "method": "GET",
    "path": "/health",
    "status": "UP",
    "http_code": 200,
    "error": null,
    "endpoints": [
      {
        "method": "POST",
        "path": "/login"
      },
      {
        "method": "POST",
        "path": "/register"
      },
      {
        "method": "DELETE",
        "path": "/logout"
      },
      {
        "method": "GET",
        "path": "/me"
      },
      {
        "method": "GET",
        "path": "/health"
      },
      {
        "method": "GET",
        "path": "/validate"
      }
    ],
    "health_response": {
      "status": "UP"
    }
  }
}
```

Respons di atas merupakan contoh struktur. Nilai `http_code`, `status`, `error`, dan `health_response` akan mengikuti respons aktual dari Auth Service.

**Keterangan field:**

- `success`: Menunjukkan permintaan Monitoring Service berhasil diproses.
- `checked_at`: Waktu pemeriksaan dilakukan.
- `name`: Nama service yang diperiksa.
- `base_url`: Alamat dasar service.
- `health_url`: Alamat endpoint yang diperiksa.
- `method`: Metode HTTP yang digunakan.
- `path`: Jalur endpoint health.
- `status`: Kondisi service, yaitu `UP` atau `DOWN`.
- `http_code`: Kode status HTTP yang diterima.
- `error`: Informasi kesalahan koneksi jika ada.
- `endpoints`: Daftar endpoint yang tersedia pada data Gateway.
- `health_response`: Isi respons yang dikirim oleh service tujuan.

### C. Memeriksa Semua Service

**Request**

```http
GET http://localhost/Monitoring_service/api/health_all.php
```

**Status HTTP:** `200 OK` jika pemeriksaan berhasil dijalankan. Status masing-masing service tetap dilihat dari hasil pemeriksaannya.

**Contoh respons**

```json
{
  "success": true,
  "checked_at": "2026-10-29T06:00:00+00:00",
  "total_services": 5,
  "services_up": 4,
  "services_down": 1,
  "overall_status": "DOWN",
  "services": [
    {
      "name": "auth",
      "base_url": "http://10.194.169.154:8102",
      "health_url": "http://10.194.169.154:8102/health",
      "status": "UP",
      "http_code": 200
    },
    {
      "name": "course",
      "base_url": "http://10.194.169.45:8103",
      "health_url": "http://10.194.169.45:8103/api/health",
      "status": "UP",
      "http_code": 200
    },
    {
      "name": "assignment",
      "base_url": "http://10.22.14.154:8104",
      "health_url": "http://10.22.14.154:8104/health",
      "status": "UP",
      "http_code": 200
    },
    {
      "name": "submission",
      "base_url": "http://10.194.169.208:8105",
      "health_url": "http://10.194.169.208:8105/health",
      "status": "DOWN",
      "http_code": 0
    },
    {
      "name": "notification",
      "base_url": "http://10.22.14.154:8106",
      "health_url": "http://10.22.14.154:8106/health",
      "status": "UP",
      "http_code": 200
    }
  ]
}
```

Contoh ini menggambarkan empat service aktif dan satu service bermasalah. Angka dan hasilnya bukan hasil pengujian aktual.

**Keterangan field:**

- `total_services`: Jumlah seluruh service yang diperiksa.
- `services_up`: Jumlah service dengan status `UP`.
- `services_down`: Jumlah service dengan status `DOWN`.
- `overall_status`: Kondisi keseluruhan. Pada contoh ini `DOWN` karena ada service yang bermasalah.
- `services`: Daftar hasil pemeriksaan setiap service.

### D. Menguji Nama Service yang Tidak Ditemukan

**Request**

```http
GET http://localhost/Monitoring_service/api/health.php?name=contoh
```

**Status HTTP:** `404 Not Found`

**Contoh respons**

```json
{
  "success": false,
  "message": "Service tidak ditemukan",
  "requested_name": "contoh"
}
```

**Keterangan:**

- `success`: Bernilai `false` karena nama service tidak ditemukan.
- `message`: Penjelasan kesalahan.
- `requested_name`: Nama service yang diminta.

## 5. Cara Kerja Monitoring Service

1. Monitoring Service mengambil daftar service dari Gateway.
2. Sistem membaca nama service, `base_url`, dan daftar endpoint.
3. Sistem mencari endpoint dengan metode `GET` yang berakhiran `/health`.
4. Sistem mengirim permintaan HTTP ke endpoint health tersebut.
5. Respons digunakan untuk menentukan status service.
6. Hasil pemeriksaan dikirim dalam format JSON.

## 6. Pengujian Menggunakan Postman

Semua pengujian menggunakan metode `GET`. Masukkan URL pada Postman, lalu klik **Send**.

| Pengujian                | URL                                                                    |
| ------------------------ | ---------------------------------------------------------------------- |
| Cek Monitoring Service   | `http://localhost/Monitoring_service/api/health.php`                   |
| Cek Auth                 | `http://localhost/Monitoring_service/api/health.php?name=auth`         |
| Cek Course               | `http://localhost/Monitoring_service/api/health.php?name=course`       |
| Cek Assignment           | `http://localhost/Monitoring_service/api/health.php?name=assignment`   |
| Cek Submission           | `http://localhost/Monitoring_service/api/health.php?name=submission`   |
| Cek Notification         | `http://localhost/Monitoring_service/api/health.php?name=notification` |
| Cek nama tidak ditemukan | `http://localhost/Monitoring_service/api/health.php?name=contoh`       |
| Cek semua service        | `http://localhost/Monitoring_service/api/health_all.php`               |

## 7. Keterangan Status

- `UP`: Endpoint health memberikan respons HTTP 2xx.
- `DOWN`: Permintaan health tidak menghasilkan respons HTTP 2xx.
- `UNKNOWN`: Endpoint `GET /health` tidak ditemukan dalam daftar endpoint Gateway.
- `404 Not Found`: Nama service yang diminta tidak ditemukan dalam daftar service.

## 8. Catatan Konfigurasi

1. Pastikan `REGISTRY_URL` pada `health_functions.php` mengarah ke URL Gateway yang benar.
2. Pastikan Monitoring Service dapat terhubung ke setiap alamat service.
3. Pastikan endpoint health sesuai dengan endpoint yang tersedia pada Gateway.
4. Daftar service di atas baru memuat lima service berdasarkan data Gateway yang diberikan. Jika tugas mewajibkan tujuh service, dua service lainnya perlu ditambahkan berdasarkan konfigurasi sebenarnya.
5. Contoh respons dalam dokumentasi harus disesuaikan jika struktur JSON yang dihasilkan oleh kode berbeda.
