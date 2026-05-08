<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
	header('Location: index.php');
	exit;
}

$dbHost = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'pemweb_db';

$email = $_SESSION['email'] ?? 'User';

$error = '';
$success = isset($_GET['success']) ? 'Data program studi berhasil ditambahkan.' : '';

$namaProdiValue = '';

$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

if ($conn->connect_error) {
	$error = 'Gagal terhubung ke database.';
}

$prodiList = [];

if ($error === '') {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$namaProdiValue = trim($_POST['nama_prodi'] ?? '');

		if ($namaProdiValue === '') {
			$error = 'Nama program studi wajib diisi.';
		} else {
			$stmt = $conn->prepare('INSERT INTO prodi_tbl (nama_prodi) VALUES (?)');
			if ($stmt) {
				$stmt->bind_param('s', $namaProdiValue);
				if ($stmt->execute()) {
					$stmt->close();
					$conn->close();
					header('Location: program_studi.php?success=1');
					exit;
				}

				$error = 'Gagal menambahkan data program studi.';
				$stmt->close();
			} else {
				$error = 'Terjadi kesalahan pada proses penyimpanan.';
			}
		}
	}

	$prodiResult = $conn->query('SELECT prodi_id, nama_prodi FROM prodi_tbl ORDER BY prodi_id ASC');
	if ($prodiResult) {
		while ($row = $prodiResult->fetch_assoc()) {
			$prodiList[] = $row;
		}
		$prodiResult->free();
	}

	$conn->close();
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Program Studi</title>
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<style>
		body {
			min-height: 100vh;
			background:
				radial-gradient(circle at top left, rgba(255, 111, 145, 0.35), transparent 28%),
				radial-gradient(circle at 80% 20%, rgba(255, 196, 0, 0.28), transparent 26%),
				radial-gradient(circle at 20% 85%, rgba(76, 175, 255, 0.24), transparent 28%),
				linear-gradient(135deg, #140b2d 0%, #25143f 45%, #0f1832 100%);
			color: #fff;
		}

		.hero-glass {
			backdrop-filter: blur(20px);
			background: rgba(255, 255, 255, 0.12);
			border: 1px solid rgba(255, 255, 255, 0.18);
			color: #fff;
			border-radius: 1.75rem;
			box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
		}

		.table-glass {
			--bs-table-bg: transparent;
			--bs-table-color: #fff;
			--bs-table-border-color: rgba(255, 255, 255, 0.15);
		}

		/* Make tables vertically scrollable with a sticky header */
		.table-responsive {
			max-height: 360px; /* adjust as needed */
			overflow-y: auto;
		}

		.table-responsive table {
			margin-bottom: 0; /* remove extra space under table */
		}

		.table-responsive thead th {
			position: sticky;
			top: 0;
			z-index: 2;
			background: rgba(255, 255, 255, 0.06);
			backdrop-filter: blur(6px);
			color: #fff;
		}

		.form-control {
			background: rgba(255, 255, 255, 0.12);
			border: 1px solid rgba(255, 255, 255, 0.18);
			color: #fff;
		}

		.form-control::placeholder {
			color: rgba(255, 255, 255, 0.55);
		}

		.form-control:focus {
			background: rgba(255, 255, 255, 0.16);
			color: #fff;
			border-color: rgba(255, 255, 255, 0.45);
			box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.1);
		}

		.sidebar {
			min-height: calc(100vh - 96px);
			position: sticky;
			top: 72px;
		}

		.sidebar .nav-link {
			color: rgba(255,255,255,0.9);
		}

		.sidebar .nav-link.active {
			font-weight: 700;
			color: #fff;
			background: rgba(255,255,255,0.03);
			border-radius: 0.5rem;
		}
	</style>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(10, 10, 20, 0.45); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.12);">
		<div class="container">
			<span class="navbar-brand mb-0 h1">Program Studi</span>
			<div class="ms-auto d-flex align-items-center gap-3">
				<span class="text-secondary small">Logged in as <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></span>
				<a href="dashboard.php?logout=1" class="btn btn-outline-light btn-sm">Logout</a>
			</div>
		</div>
	</nav>

	<main class="container-fluid py-4">
		<div class="row g-4">
			<aside class="col-12 col-md-3 mb-4">
				<div class="p-3 hero-glass sidebar">
					<h5 class="mb-3">Menu</h5>
					<ul class="nav flex-column">
						<li class="nav-item mb-2"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
						<li class="nav-item mb-2"><a class="nav-link active" href="program_studi.php">Prodi</a></li>
						<li class="nav-item mb-2"><a class="nav-link" href="user.php">Users</a></li>
						<li class="nav-item mt-3"><a class="nav-link" href="dashboard.php?logout=1">Logout</a></li>
					</ul>
				</div>
			</aside>

			<section class="col-12 col-md-9">
				<div class="row g-4">
					<div class="col-12 col-lg-7">
						<div class="card hero-glass p-4 h-100">
							<div class="d-flex justify-content-between align-items-center mb-3">
								<div>
									<h4 class="mb-1">Data Program Studi</h4>
									<small class="text-secondary">Daftar program studi dari database</small>
								</div>
								<div class="text-secondary small"><?php echo count($prodiList); ?> data</div>
							</div>

							<?php if ($success !== ''): ?>
								<div class="alert alert-success border-0" role="alert">
									<?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
								</div>
							<?php endif; ?>

							<?php if ($error !== ''): ?>
								<div class="alert alert-danger border-0" role="alert">
									<?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
								</div>
							<?php endif; ?>

							<div class="table-responsive">
								<table class="table table-glass align-middle mb-0">
									<thead>
										<tr>
											<th>ID Prodi</th>
											<th>Nama Prodi</th>
										</tr>
									</thead>
									<tbody>
										<?php if (count($prodiList) > 0): ?>
											<?php foreach ($prodiList as $prodi): ?>
												<tr>
													<td><?php echo htmlspecialchars($prodi['prodi_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
													<td><?php echo htmlspecialchars($prodi['nama_prodi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
												</tr>
											<?php endforeach; ?>
										<?php else: ?>
											<tr>
												<td colspan="2" class="text-center text-secondary py-4">Belum ada data program studi.</td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="col-12 col-lg-5">
						<div class="card hero-glass p-4 h-100">
							<h4 class="mb-1">Tambah Program Studi</h4>
							<small class="text-secondary d-block mb-4">Isi nama program studi baru</small>

							<form method="post" action="program_studi.php">
								<div class="mb-4">
									<label for="nama_prodi" class="form-label">Nama Prodi</label>
									<input type="text" class="form-control form-control-lg" id="nama_prodi" name="nama_prodi" value="<?php echo htmlspecialchars($namaProdiValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: Statistika" required>
								</div>

								<div class="d-grid">
									<button type="submit" class="btn btn-light btn-lg">Simpan Prodi</button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</section>
		</div>
	</main>

	<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
