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

$emailValue = $email;
$passwordValue = '';
$prodiName = '-';
$selectedProdiId = '';

$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

if ($conn->connect_error) {
	$error = 'Gagal terhubung ke database.';
}

$prodiList = [];

if ($error === '') {
	// Fetch prodi list
	$prodiResult = $conn->query('SELECT prodi_id, nama_prodi FROM prodi_tbl ORDER BY nama_prodi ASC');
	if ($prodiResult) {
		while ($row = $prodiResult->fetch_assoc()) {
			$prodiList[] = $row;
		}
		$prodiResult->free();
	}

	// Fetch current user profile
	$stmt = $conn->prepare('SELECT email, password, prodi_id FROM user_tbl WHERE email = ?');
	if ($stmt) {
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($row = $result->fetch_assoc()) {
			$emailValue = $row['email'];
			$passwordValue = $row['password'];
			$selectedProdiId = $row['prodi_id'];

			// Get prodi name
			foreach ($prodiList as $prodi) {
				if ($prodi['prodi_id'] == $selectedProdiId) {
					$prodiName = $prodi['nama_prodi'];
					break;
				}
			}
		}
		$stmt->close();
	}

	// Handle profile update
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$newEmail = trim($_POST['email'] ?? '');
		$newPassword = $_POST['password'] ?? '';
		$newProdiId = trim($_POST['prodi_id'] ?? '');

		// Validate inputs
		if ($newEmail === '') {
			$error = 'Email wajib diisi.';
		} elseif ($newEmail !== $email) {
			// Check if new email already exists
			$checkStmt = $conn->prepare('SELECT email FROM user_tbl WHERE email = ? AND email != ?');
			if ($checkStmt) {
				$checkStmt->bind_param('ss', $newEmail, $email);
				$checkStmt->execute();
				$checkResult = $checkStmt->get_result();
				if ($checkResult->num_rows > 0) {
					$error = 'Email sudah terdaftar. Gunakan email lain.';
				}
				$checkStmt->close();
			}
		}

		// Update profile if no errors
		if ($error === '') {
			// Determine what to update
			if ($newPassword !== '') {
				// Update email and password
				$updateStmt = $conn->prepare('UPDATE user_tbl SET email = ?, password = ?, prodi_id = ? WHERE email = ?');
				if ($updateStmt) {
					$updateStmt->bind_param('ssis', $newEmail, $newPassword, $newProdiId, $email);
					if ($updateStmt->execute()) {
						// Update session if email changed
						if ($newEmail !== $email) {
							$_SESSION['email'] = $newEmail;
						}
						$success = 'Profil berhasil diperbarui.';
						$email = $newEmail;
						$emailValue = $newEmail;
					} else {
						$error = 'Gagal memperbarui profil.';
					}
					$updateStmt->close();
				}
			} else {
				// Update only email and prodi (keep password unchanged)
				$updateStmt = $conn->prepare('UPDATE user_tbl SET email = ?, prodi_id = ? WHERE email = ?');
				if ($updateStmt) {
					$updateStmt->bind_param('sis', $newEmail, $newProdiId, $email);
					if ($updateStmt->execute()) {
						// Update session if email changed
						if ($newEmail !== $email) {
							$_SESSION['email'] = $newEmail;
						}
						$success = 'Profil berhasil diperbarui.';
						$email = $newEmail;
						$emailValue = $newEmail;
					} else {
						$error = 'Gagal memperbarui profil.';
					}
					$updateStmt->close();
				}
			}
		}
	}

	$conn->close();
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Profile</title>
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

		.form-label {
			color: rgba(255, 255, 255, 0.9);
			font-weight: 500;
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

		.profile-header {
			background: rgba(255, 255, 255, 0.08);
			border-radius: 1rem;
			padding: 2rem;
			margin-bottom: 2rem;
			border: 1px solid rgba(255, 255, 255, 0.12);
		}

		.profile-avatar {
			width: 80px;
			height: 80px;
			border-radius: 50%;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 2rem;
			font-weight: bold;
			margin-bottom: 1rem;
		}

		.btn-primary-custom {
			background: #667eea;
			border-color: #667eea;
		}

		.btn-primary-custom:hover {
			background: #5568d3;
			border-color: #5568d3;
		}
	</style>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(10, 10, 20, 0.45); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.12);">
		<div class="container">
			<span class="navbar-brand mb-0 h1">Profile</span>
			<div class="ms-auto d-flex align-items-center gap-3">
				<span class="text-secondary small">Logged in as <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></span>
				<a href="profile.php" class="btn btn-light btn-sm">Profile</a>
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
						<li class="nav-item mb-2"><a class="nav-link" href="user.php">Users</a></li>

					</ul>
				</div>
			</aside>

			<section class="col-12 col-md-9">
				<div class="row g-4">
					<div class="col-12">
						<div class="hero-glass profile-header">
							<div class="d-flex align-items-start">
								<div>
									<div class="profile-avatar">
										<?php echo strtoupper(substr($email, 0, 1)); ?>
									</div>
									<h3 class="mb-1"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></h3>
									<p class="text-secondary mb-0">Program Studi: <strong><?php echo htmlspecialchars($prodiName, ENT_QUOTES, 'UTF-8'); ?></strong></p>
								</div>
							</div>
						</div>
					</div>

					<div class="col-12 col-lg-8">
						<div class="hero-glass p-4">
							<h4 class="mb-1">Edit Profile</h4>
							<small class="text-secondary d-block mb-4">Perbarui informasi akun Anda</small>

							<?php if ($success !== ''): ?>
								<div class="alert alert-success border-0 mb-4" role="alert">
									<?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
								</div>
							<?php endif; ?>

							<?php if ($error !== ''): ?>
								<div class="alert alert-danger border-0 mb-4" role="alert">
									<?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
								</div>
							<?php endif; ?>

							<form method="post" action="profile.php">
								<div class="mb-4">
									<label for="email" class="form-label">Email</label>
									<input type="email" class="form-control form-control-lg" id="email" name="email" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="name@example.com" required>
									<small class="text-secondary">Gunakan email unik untuk akun Anda</small>
								</div>

								<div class="mb-4">
									<label for="password" class="form-label">Password</label>
									<input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Masukkan password baru">
									<small class="text-secondary">Masukkan password baru untuk mengubah password saat ini</small>
								</div>

								<div class="mb-4">
									<label for="prodi_id" class="form-label">Program Studi</label>
									<select class="form-select form-select-lg" id="prodi_id" name="prodi_id" required>
										<option value="">Pilih prodi</option>
										<?php foreach ($prodiList as $prodi): ?>
											<option value="<?php echo htmlspecialchars($prodi['prodi_id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string)$selectedProdiId === (string)$prodi['prodi_id']) ? 'selected' : ''; ?>>
												<?php echo htmlspecialchars($prodi['nama_prodi'], ENT_QUOTES, 'UTF-8'); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="d-flex gap-2">
									<button type="submit" class="btn btn-light btn-lg flex-grow-1">Simpan Perubahan</button>
									<a href="dashboard.php" class="btn btn-outline-light btn-lg">Batal</a>
								</div>
							</form>
						</div>
					</div>

					<div class="col-12 col-lg-4">
						<div class="hero-glass p-4">
							<h5 class="mb-3">Info Akun</h5>
							<div class="mb-4">
								<small class="text-secondary d-block">Email Saat Ini</small>
								<p class="mb-0 font-monospace"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
							</div>
							<div class="mb-4">
								<small class="text-secondary d-block">Program Studi</small>
								<p class="mb-0"><?php echo htmlspecialchars($prodiName, ENT_QUOTES, 'UTF-8'); ?></p>
							</div>
							<hr style="border-color: rgba(255,255,255,0.1);">
							<small class="text-secondary">
								Untuk keamanan, gunakan password yang kuat dan unik. Jangan bagikan akun Anda kepada orang lain.
							</small>
						</div>
					</div>
				</div>
			</section>
		</div>
	</main>

	<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>