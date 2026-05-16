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
$success = '';
if (isset($_GET['success'])) {
	$success = 'Data program studi berhasil ditambahkan.';
} elseif (isset($_GET['update'])) {
	$success = 'Data program studi berhasil diperbarui.';
} elseif (isset($_GET['deleted'])) {
	$success = 'Data program studi berhasil dihapus.';
}

$namaProdiValue = '';

$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

if ($conn->connect_error) {
	$error = 'Gagal terhubung ke database.';
}

$prodiList = [];

if ($error === '') {
	if (isset($_GET['delete'])) {
		$deleteId = intval($_GET['delete']);
		$checkUsageStmt = $conn->prepare('SELECT user_id FROM user_tbl WHERE prodi_id = ? LIMIT 1');
		if ($checkUsageStmt) {
			$checkUsageStmt->bind_param('i', $deleteId);
			$checkUsageStmt->execute();
			$usageResult = $checkUsageStmt->get_result();

			if ($usageResult && $usageResult->num_rows > 0) {
				$error = 'Program studi tidak dapat dihapus karena masih digunakan oleh data user.';
			} else {
				$stmt = $conn->prepare('DELETE FROM prodi_tbl WHERE prodi_id = ?');
				if ($stmt) {
					$stmt->bind_param('i', $deleteId);
					$stmt->execute();
					$stmt->close();
					$conn->close();
					header('Location: program_studi.php?deleted=1');
					exit;
				}
			}

			$checkUsageStmt->close();
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$namaProdiValue = trim($_POST['nama_prodi'] ?? '');
		$isEditMode = isset($_POST['editing_prodi_id']) && $_POST['editing_prodi_id'] !== '';
		$editingProdiId = $isEditMode ? intval($_POST['editing_prodi_id']) : 0;

		if ($namaProdiValue === '') {
			$error = 'Nama program studi wajib diisi.';
		} else {
			// Check if nama_prodi already exists
			$checkProdi = $conn->prepare('SELECT prodi_id FROM prodi_tbl WHERE LOWER(nama_prodi) = LOWER(?)');
			if ($checkProdi) {
				$checkProdi->bind_param('s', $namaProdiValue);
				$checkProdi->execute();
				$prodiCheckResult = $checkProdi->get_result();
				
				if ($isEditMode) {
					// For edit mode, check if nama_prodi exists elsewhere (not current prodi)
					if ($prodiCheckResult->num_rows > 0) {
						$existingProdi = $prodiCheckResult->fetch_assoc();
						if ($existingProdi['prodi_id'] != $editingProdiId) {
							$error = 'Nama program studi sudah ada. Gunakan nama lain.';
						}
					}
				} else {
					// For insert mode, check if nama_prodi exists at all
					if ($prodiCheckResult->num_rows > 0) {
						$error = 'Nama program studi sudah ada. Gunakan nama lain.';
					}
				}
				$checkProdi->close();
			}
			
			if ($error === '') {
				if ($isEditMode) {
					$stmt = $conn->prepare('UPDATE prodi_tbl SET nama_prodi = ? WHERE prodi_id = ?');
					if ($stmt) {
						$stmt->bind_param('si', $namaProdiValue, $editingProdiId);
						if ($stmt->execute()) {
							$stmt->close();
							$conn->close();
							header('Location: program_studi.php?update=1');
							exit;
						}

						$error = 'Gagal memperbarui data program studi.';
						$stmt->close();
					} else {
						$error = 'Terjadi kesalahan pada proses penyimpanan.';
					}
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
		}
	}

	$prodiResult = $conn->query('SELECT p.prodi_id, p.nama_prodi, COUNT(u.user_id) AS user_count FROM prodi_tbl p LEFT JOIN user_tbl u ON u.prodi_id = p.prodi_id GROUP BY p.prodi_id, p.nama_prodi ORDER BY p.prodi_id ASC');
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

		.modal-content.hero-glass {
			background: rgba(18, 14, 36, 0.92);
		}
	</style>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(10, 10, 20, 0.45); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.12);">
		<div class="container">
			<span class="navbar-brand mb-0 h1">Program Studi</span>
			<div class="ms-auto d-flex align-items-center gap-3">
				<span class="text-secondary small">Logged in as <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></span>
				<a href="profile.php" class="btn btn-outline-light btn-sm">Profile</a>
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
						<li class="nav-item mb-2"><a class="nav-link active" href="program_studi.php">Program Studi</a></li>
						<li class="nav-item mb-2"><a class="nav-link" href="user.php">Users</a></li>

					</ul>
				</div>
			</aside>

			<section class="col-12 col-md-9">
				<div class="row g-4">
					<div class="col-12">
						<div class="card hero-glass p-4 h-100">
							<div class="d-flex justify-content-between align-items-center mb-3">
								<div>
									<h4 class="mb-1">Data Program Studi</h4>
									<small class="text-secondary">Daftar program studi dari database</small>
								</div>
								<div class="d-flex align-items-center gap-2">
									<div class="text-secondary small"><?php echo count($prodiList); ?> data</div>
									<button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#tambahProdiModal">Tambah Prodi</button>
								</div>
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
											<th class="text-center">Jumlah User</th>
											<th class="text-center">Aksi</th>
										</tr>
									</thead>
									<tbody>
										<?php if (count($prodiList) > 0): ?>
											<?php foreach ($prodiList as $prodi): ?>
												<tr>
													<td><?php echo htmlspecialchars($prodi['prodi_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
													<td>
														<button type="button" class="btn btn-link text-light text-decoration-none p-0" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#editProdiModal<?php echo htmlspecialchars($prodi['prodi_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
															<?php echo htmlspecialchars($prodi['nama_prodi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
														</button>
													</td>
													<td class="text-center"><?php echo htmlspecialchars((string)($prodi['user_count'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?></td>
													<td class="text-center">
														<button type="button" class="btn btn-sm btn-outline-warning me-2" data-bs-toggle="modal" data-bs-target="#editProdiModal<?php echo htmlspecialchars($prodi['prodi_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">Edit</button>
														<?php if ((int)($prodi['user_count'] ?? 0) > 0): ?>
															<button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Program studi ini masih digunakan oleh data user">Tidak Bisa Dihapus</button>
														<?php else: ?>
															<a href="program_studi.php?delete=<?php echo htmlspecialchars($prodi['prodi_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus program studi ini?');">Hapus</a>
														<?php endif; ?>
													</td>
												</tr>
											<?php endforeach; ?>
										<?php else: ?>
											<tr>
												<td colspan="4" class="text-center text-secondary py-4">Belum ada data program studi.</td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
	</main>

	<div class="modal fade" id="tambahProdiModal" tabindex="-1" aria-labelledby="tambahProdiModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content hero-glass">
				<div class="modal-header border-0">
					<h5 class="modal-title" id="tambahProdiModalLabel">Tambah Program Studi</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form method="post" action="program_studi.php">
					<div class="modal-body">
						<input type="hidden" name="editing_prodi_id" value="">
						<div class="mb-3">
							<label for="tambah_nama_prodi" class="form-label">Nama Prodi</label>
							<input type="text" class="form-control form-control-lg" id="tambah_nama_prodi" name="nama_prodi" placeholder="Contoh: Statistika" required>
						</div>
					</div>
					<div class="modal-footer border-0">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
						<button type="submit" class="btn btn-outline-light">Simpan Prodi</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<?php foreach ($prodiList as $prodi): ?>
		<div class="modal fade" id="editProdiModal<?php echo htmlspecialchars($prodi['prodi_id'], ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1" aria-labelledby="editProdiModalLabel<?php echo htmlspecialchars($prodi['prodi_id'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content hero-glass">
					<div class="modal-header border-0">
						<h5 class="modal-title" id="editProdiModalLabel<?php echo htmlspecialchars($prodi['prodi_id'], ENT_QUOTES, 'UTF-8'); ?>">Edit Program Studi</h5>
						<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<form method="post" action="program_studi.php">
						<div class="modal-body">
							<input type="hidden" name="editing_prodi_id" value="<?php echo htmlspecialchars($prodi['prodi_id'], ENT_QUOTES, 'UTF-8'); ?>">
							<div class="mb-3">
								<label for="edit_nama_prodi_<?php echo htmlspecialchars($prodi['prodi_id'], ENT_QUOTES, 'UTF-8'); ?>" class="form-label">Nama Prodi</label>
								<input type="text" class="form-control form-control-lg" id="edit_nama_prodi_<?php echo htmlspecialchars($prodi['prodi_id'], ENT_QUOTES, 'UTF-8'); ?>" name="nama_prodi" value="<?php echo htmlspecialchars($prodi['nama_prodi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
							</div>
						</div>
						<div class="modal-footer border-0">
							<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
							<button type="submit" class="btn btn-outline-light">Perbarui Prodi</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	<?php endforeach; ?>

	<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
