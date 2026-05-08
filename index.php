<?php
session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
	header('Location: dashboard.php');
	exit;
}

$error = '';
$email = '';

$dbHost = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'pemweb_db';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';

	$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

	if ($conn->connect_error) {
		$error = 'Gagal terhubung ke database.';
	} elseif ($email !== '' && $password !== '') {
		$stmt = $conn->prepare('SELECT email FROM user_tbl WHERE email = ? AND password = ? LIMIT 1');

		if ($stmt) {
			$stmt->bind_param('ss', $email, $password);
			$stmt->execute();
			$result = $stmt->get_result();

			if ($result && $result->num_rows === 1) {
				session_regenerate_id(true);
				$_SESSION['logged_in'] = true;
				$_SESSION['email'] = $email;

				$stmt->close();
				$conn->close();

				header('Location: dashboard.php');
				exit;
			}

			$stmt->close();
		} else {
			$error = 'Terjadi kesalahan pada proses login.';
		}

		if ($error === '') {
			$error = 'Email atau password salah.';
		}
	} else {
		$error = 'Email dan password wajib diisi.';
	}

	$conn->close();
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Login</title>
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<style>
		body {
			min-height: 100vh;
			background:
				radial-gradient(circle at top left, rgba(255, 111, 145, 0.9), transparent 34%),
				radial-gradient(circle at 85% 20%, rgba(255, 196, 0, 0.8), transparent 28%),
				radial-gradient(circle at 20% 85%, rgba(76, 175, 255, 0.78), transparent 30%),
				linear-gradient(135deg, #120a2a 0%, #1c133f 45%, #0e1830 100%);
			position: relative;
			overflow: hidden;
		}

		body::before {
			content: '';
			position: fixed;
			inset: 0;
			background: rgba(255, 255, 255, 0.04);
			backdrop-filter: blur(2px);
			pointer-events: none;
		}

		body::after {
			content: '';
			position: fixed;
			inset: 0;
			background-image: linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
			background-size: 36px 36px;
			mask-image: radial-gradient(circle at center, black, transparent 80%);
			pointer-events: none;
		}

		.login-card {
			backdrop-filter: blur(22px);
			background: rgba(255, 255, 255, 0.12);
			border: 1px solid rgba(255, 255, 255, 0.22);
			border-radius: 1.75rem;
			box-shadow: 0 24px 80px rgba(0, 0, 0, 0.38);
			color: #fff;
		}

		.brand-badge {
			width: 72px;
			height: 72px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			border-radius: 1.3rem;
			background: linear-gradient(135deg, #ff5f6d 0%, #ffc371 52%, #7c4dff 100%);
			color: #fff;
			font-weight: 800;
			letter-spacing: 0.05em;
			box-shadow: 0 12px 30px rgba(255, 95, 109, 0.35);
		}


		.brand-row {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 0.75rem;
		}

		.brand-text h1 {
			margin: 0;
			color: #fff;
			font-size: 1.5rem;
			font-weight: 800;
		}
		.login-card .form-label,
		.login-card .text-muted,
		.login-card .small {
			color: rgba(255, 255, 255, 0.8) !important;
		}

		.login-card .form-control {
			background: rgba(255, 255, 255, 0.12);
			border: 1px solid rgba(255, 255, 255, 0.22);
			color: #fff;
			border-radius: 1rem;
			padding-top: 0.95rem;
			padding-bottom: 0.95rem;
		}

		.login-card .form-control::placeholder {
			color: rgba(255, 255, 255, 0.55);
		}

		.login-card .form-control:focus {
			background: rgba(255, 255, 255, 0.16);
			border-color: rgba(255, 255, 255, 0.5);
			box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.1);
			color: #fff;
		}

		.login-card .btn-primary {
			border: 0;
			border-radius: 1rem;
			padding-top: 0.95rem;
			padding-bottom: 0.95rem;
			font-weight: 700;
			background: linear-gradient(135deg, #ff5f6d 0%, #ffc371 50%, #7c4dff 100%);
			box-shadow: 0 16px 35px rgba(124, 77, 255, 0.28);
		}

		.login-card .btn-primary:hover {
			filter: brightness(1.04);
		}
	</style>
</head>
<body class="d-flex align-items-center">
	<main class="container py-5">
		<div class="row justify-content-center">
			<div class="col-12 col-sm-11 col-md-9 col-lg-5 col-xl-4">
				<div class="card login-card shadow-lg">
					<div class="card-body p-4 p-md-5">
						<div class="text-center mb-4">
							<div class="brand-row mb-2">
								<div class="brand-badge">Kel 7</div>
								<div class="brand-text">
									<h1>Kelompok 7</h1>
								</div>
							</div>
						</div>

						<?php if ($error !== ''): ?>
							<div class="alert alert-danger border-0" role="alert">
								<?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
							</div>
						<?php endif; ?>

						<form method="post" action="index.php" novalidate>
							<div class="mb-3">
								<label for="email" class="form-label">Email</label>
								<input
									type="email"
									class="form-control form-control-lg"
									id="email"
									name="email"
									value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
									placeholder="name@example.com"
									required
									autofocus>
							</div>

							<div class="mb-3">
								<label for="password" class="form-label">Password</label>
								<input
									type="password"
									class="form-control form-control-lg"
									id="password"
									name="password"
									placeholder="Enter password"
									required>
							</div>

							<div class="d-grid">
								<button type="submit" class="btn btn-primary btn-lg">Login</button>
							</div>
						</form>

						<div class="text-center mt-4 small text-muted">
							Masuk dengan akun terdaftar.
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>

	<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
