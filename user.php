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
if (isset($_GET['success'])) {
	$success = 'Data user berhasil ditambahkan.';
} elseif (isset($_GET['update'])) {
	$success = 'Data user berhasil diperbarui.';
} elseif (isset($_GET['deleted'])) {
	$success = 'Data user berhasil dihapus.';
} else {
	$success = '';
}

$emailValue = '';
$passwordValue = '';
$selectedProdiId = '';

$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

if ($conn->connect_error) {
	$error = 'Gagal terhubung ke database.';
}

$prodiList = [];
$users = [];

if ($error === '') {
	$prodiResult = $conn->query('SELECT prodi_id, nama_prodi FROM prodi_tbl ORDER BY nama_prodi ASC');
	if ($prodiResult) {
		while ($row = $prodiResult->fetch_assoc()) {
			$prodiList[] = $row;
		}
		$prodiResult->free();
	}

	if (isset($_GET['delete'])) {
		$deleteId = intval($_GET['delete']);
		$stmt = $conn->prepare('DELETE FROM user_tbl WHERE user_id = ?');
		if ($stmt) {
			$stmt->bind_param('i', $deleteId);
			$stmt->execute();
			$stmt->close();
			$conn->close();
			header('Location: user.php?deleted=1');
			exit;
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$emailValue = trim($_POST['email'] ?? '');
		$passwordValue = $_POST['password'] ?? '';
		$confirmPasswordValue = $_POST['confirm_password'] ?? '';
		$selectedProdiId = trim($_POST['prodi_id'] ?? '');
		$isEditMode = isset($_POST['editing_user_id']) && $_POST['editing_user_id'] !== '';
		$editingUserId = $isEditMode ? intval($_POST['editing_user_id']) : 0;

		if ($emailValue === '' || $passwordValue === '' || $confirmPasswordValue === '' || $selectedProdiId === '') {
			$error = 'Email, password, dan program studi wajib diisi.';
		} elseif ($passwordValue !== $confirmPasswordValue) {
			$error = 'Password dan ulangi password harus sama.';
		} else {
			// Check if email already exists
			$checkEmail = $conn->prepare('SELECT user_id FROM user_tbl WHERE LOWER(email) = LOWER(?)');
			if ($checkEmail) {
				$checkEmail->bind_param('s', $emailValue);
				$checkEmail->execute();
				$emailCheckResult = $checkEmail->get_result();
				
				if ($isEditMode) {
					// For edit mode, check if email exists elsewhere (not current user)
					if ($emailCheckResult->num_rows > 0) {
						$existingUser = $emailCheckResult->fetch_assoc();
						if ($existingUser['user_id'] != $editingUserId) {
							$error = 'Email sudah terdaftar. Gunakan email lain.';
						}
					}
				} else {
					// For insert mode, check if email exists at all
					if ($emailCheckResult->num_rows > 0) {
						$error = 'Email sudah terdaftar. Gunakan email lain.';
					}
				}
				$checkEmail->close();
			}
			
			if ($error === '') {
				if ($isEditMode) {
				$stmt = $conn->prepare('UPDATE user_tbl SET email = ?, password = ?, prodi_id = ? WHERE user_id = ?');
				if ($stmt) {
					$stmt->bind_param('ssii', $emailValue, $passwordValue, $selectedProdiId, $editingUserId);
					if ($stmt->execute()) {
						$stmt->close();
						$conn->close();
						header('Location: user.php?update=1');
						exit;
					}
					$error = 'Gagal memperbarui data user.';
					$stmt->close();
				} else {
					$error = 'Terjadi kesalahan pada proses penyimpanan.';
				}
			} else {
				$stmt = $conn->prepare('INSERT INTO user_tbl (email, password, prodi_id) VALUES (?, ?, ?)');
				if ($stmt) {
					$stmt->bind_param('ssi', $emailValue, $passwordValue, $selectedProdiId);
					if ($stmt->execute()) {
						$stmt->close();
						$conn->close();
						header('Location: user.php?success=1');
						exit;
					}
					$error = 'Gagal menambahkan data user.';
					$stmt->close();
				} else {
					$error = 'Terjadi kesalahan pada proses penyimpanan.';
				}
			}
			}
		}
	}

	$userResult = $conn->query('SELECT u.user_id, u.email, u.password, u.prodi_id, p.nama_prodi FROM user_tbl u LEFT JOIN prodi_tbl p ON p.prodi_id = u.prodi_id ORDER BY u.email ASC');
	if ($userResult) {
		while ($row = $userResult->fetch_assoc()) {
			$users[] = $row;
		}
		$userResult->free();
	}

	$conn->close();
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>User Management</title>
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

		.form-control,
		.form-select {
			background: rgba(255, 255, 255, 0.12);
			border: 1px solid rgba(255, 255, 255, 0.18);
			color: #fff;
		}

		.form-control::placeholder {
			color: rgba(255, 255, 255, 0.55);
		}

		.form-control:focus,
		.form-select:focus {
			background: rgba(255, 255, 255, 0.16);
			color: #fff;
			border-color: rgba(255, 255, 255, 0.45);
			box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.1);
		}

		.form-select option {
			color: #111;
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
			<span class="navbar-brand mb-0 h1">User Management</span>
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
						<li class="nav-item mb-2"><a class="nav-link" href="program_studi.php">Program Studi</a></li>
						<li class="nav-item mb-2"><a class="nav-link active" href="user.php">Users</a></li>

					</ul>
				</div>
			</aside>

			<section class="col-12 col-md-9">
				<div class="row g-4">
					<div class="col-12">
						<div class="card hero-glass p-4 h-100">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<div>
							<h4 class="mb-1">Data User</h4>
							<small class="text-secondary">Daftar user dari database</small>
						</div>
						<div class="d-flex align-items-center gap-2">
							<div class="text-secondary small"><?php echo count($users); ?> data</div>
							<button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">Tambah User</button>
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
									<th>Email</th>
									<th>Password</th>
									<th>Prodi</th>
									<th class="text-center">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php if (count($users) > 0): ?>
									<?php foreach ($users as $user): ?>
										<tr>
											<td>
												<button type="button" class="btn btn-link text-light text-decoration-none p-0" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
													<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
												</button>
											</td>
											<td><?php echo htmlspecialchars($user['password'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
											<td><?php echo htmlspecialchars($user['nama_prodi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
											<td class="text-center">
												<button type="button" class="btn btn-sm btn-outline-warning me-2" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">Edit</button>
												<a href="user.php?delete=<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?');">Hapus</a>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else: ?>
									<tr>
										<td colspan="4" class="text-center text-secondary py-4">Belum ada data user.</td>
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

	<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content hero-glass">
				<div class="modal-header border-0">
					<h5 class="modal-title" id="userModalLabel">Tambah User</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form method="post" action="user.php">
					<div class="modal-body">
						<input type="hidden" name="editing_user_id" value="">

						<div class="mb-3">
							<label for="tambah_email" class="form-label">Email</label>
							<input type="email" class="form-control form-control-lg" id="tambah_email" name="email" placeholder="name@example.com" required>
						</div>

						<div class="mb-3">
							<label for="tambah_password" class="form-label">Password</label>
							<div class="input-group input-group-lg">
								<input type="password" class="form-control" id="tambah_password" name="password" placeholder="Password" required>
								<button class="btn btn-outline-light toggle-password" type="button" data-target="tambah_password">Lihat</button>
							</div>
						</div>

						<div class="mb-3">
							<label for="tambah_confirm_password" class="form-label">Ulangi Password</label>
							<div class="input-group input-group-lg">
								<input type="password" class="form-control" id="tambah_confirm_password" name="confirm_password" placeholder="Ulangi Password" required>
								<button class="btn btn-outline-light toggle-password" type="button" data-target="tambah_confirm_password">Lihat</button>
							</div>
						</div>

						<div class="mb-3">
							<label for="tambah_prodi_id" class="form-label">Program Studi</label>
							<select class="form-select form-select-lg" id="tambah_prodi_id" name="prodi_id" required>
								<option value="">Pilih prodi</option>
								<?php foreach ($prodiList as $prodi): ?>
									<option value="<?php echo htmlspecialchars($prodi['prodi_id'], ENT_QUOTES, 'UTF-8'); ?>">
										<?php echo htmlspecialchars($prodi['nama_prodi'], ENT_QUOTES, 'UTF-8'); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="modal-footer border-0">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
						<button type="submit" class="btn btn-outline-light">Simpan User</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<?php foreach ($users as $user): ?>
		<div class="modal fade" id="editUserModal<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content hero-glass">
					<div class="modal-header border-0">
						<h5 class="modal-title" id="editUserModalLabel<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">Edit User</h5>
						<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<form method="post" action="user.php">
						<div class="modal-body">
							<input type="hidden" name="editing_user_id" value="<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">

							<div class="mb-3">
								<label for="edit_email_<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" class="form-label">Email</label>
								<input type="email" class="form-control form-control-lg" id="edit_email_<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" name="email" value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
							</div>

							<div class="mb-3">
								<label for="edit_password_<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" class="form-label">Password</label>
								<div class="input-group input-group-lg">
									<input type="password" class="form-control" id="edit_password_<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" name="password" value="<?php echo htmlspecialchars($user['password'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
									<button class="btn btn-outline-light toggle-password" type="button" data-target="edit_password_<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">Lihat</button>
								</div>
							</div>

							<div class="mb-3">
								<label for="edit_confirm_password_<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" class="form-label">Ulangi Password</label>
								<div class="input-group input-group-lg">
									<input type="password" class="form-control" id="edit_confirm_password_<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" name="confirm_password" value="<?php echo htmlspecialchars($user['password'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
									<button class="btn btn-outline-light toggle-password" type="button" data-target="edit_confirm_password_<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">Lihat</button>
								</div>
							</div>

							<div class="mb-3">
								<label for="edit_prodi_id_<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" class="form-label">Program Studi</label>
								<select class="form-select form-select-lg" id="edit_prodi_id_<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" name="prodi_id" required>
									<option value="">Pilih prodi</option>
									<?php foreach ($prodiList as $prodi): ?>
										<option value="<?php echo htmlspecialchars($prodi['prodi_id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string)($user['prodi_id'] ?? '') === (string)$prodi['prodi_id']) ? 'selected' : ''; ?>>
											<?php echo htmlspecialchars($prodi['nama_prodi'], ENT_QUOTES, 'UTF-8'); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="modal-footer border-0">
							<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
							<button type="submit" class="btn btn-outline-light">Perbarui User</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	<?php endforeach; ?>

	<script src="js/bootstrap.bundle.min.js"></script>
	<script>
		document.querySelectorAll('.toggle-password').forEach(function (button) {
			button.addEventListener('click', function () {
				var target = document.getElementById(button.getAttribute('data-target'));
				if (!target) {
					return;
				}
				if (target.type === 'password') {
					target.type = 'text';
					button.textContent = 'Sembunyikan';
				} else {
					target.type = 'password';
					button.textContent = 'Lihat';
				}
			});
		});
	</script>
</body>
</html>
