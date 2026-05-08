<?php
session_start();

if (isset($_GET['logout'])) {
	$_SESSION = [];

	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params['path'], $params['domain'],
			$params['secure'], $params['httponly']
		);
	}

	session_destroy();
	header('Location: index.php');
	exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
	header('Location: index.php');
	exit;
}

$email = $_SESSION['email'] ?? 'User';

// Database stats: total prodi, total users, and users per prodi
$dbHost = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'pemweb_db';

$prodiCount = 0;
$userCount = 0;
$prodiLabels = [];
$prodiData = [];

$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);
if (!$conn->connect_error) {
	$res = $conn->query('SELECT COUNT(*) AS cnt FROM prodi_tbl');
	if ($res) {
		$row = $res->fetch_assoc();
		$prodiCount = (int)($row['cnt'] ?? 0);
		$res->free();
	}

	$res = $conn->query('SELECT COUNT(*) AS cnt FROM user_tbl');
	if ($res) {
		$row = $res->fetch_assoc();
		$userCount = (int)($row['cnt'] ?? 0);
		$res->free();
	}

	$sql = 'SELECT p.nama_prodi, COUNT(u.email) AS cnt FROM prodi_tbl p LEFT JOIN user_tbl u ON u.prodi_id = p.prodi_id GROUP BY p.prodi_id ORDER BY cnt DESC';
	$res = $conn->query($sql);
	if ($res) {
		while ($r = $res->fetch_assoc()) {
			$prodiLabels[] = $r['nama_prodi'];
			$prodiData[] = (int)($r['cnt'] ?? 0);
		}
		$res->free();
	}

	$conn->close();
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Dashboard</title>
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<style>
		body {
			min-height: 100vh;
			background:
				radial-gradient(circle at top left, rgba(255, 111, 145, 0.35), transparent 28%),
				radial-gradient(circle at 80% 20%, rgba(255, 196, 0, 0.28), transparent 26%),
				radial-gradient(circle at 20% 85%, rgba(76, 175, 255, 0.24), transparent 28%),
				linear-gradient(135deg, #140b2d 0%, #25143f 45%, #0f1832 100%);
		}

		.hero-glass {
			backdrop-filter: blur(20px);
			background: rgba(255, 255, 255, 0.12);
			border: 1px solid rgba(255, 255, 255, 0.18);
			color: #fff;
			border-radius: 1.75rem;
			box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
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
			<span class="navbar-brand mb-0 h1">Dashboard</span>
			<div class="ms-auto d-flex align-items-center gap-3">
				<span class="text-secondary small">Logged in as <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></span>
				<a href="profile.php" class="btn btn-outline-light btn-sm">Profile</a>
				<a href="dashboard.php?logout=1" class="btn btn-outline-light btn-sm">Logout</a>
			</div>
		</div>
	</nav>

	<main class="container-fluid py-4">
		<div class="row">
			<aside class="col-12 col-md-3 mb-4">
				<div class="p-3 hero-glass sidebar">
					<h5 class="mb-3">Menu</h5>
					<ul class="nav flex-column">
						<li class="nav-item mb-2"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
						<li class="nav-item mb-2"><a class="nav-link" href="program_studi.php">Program Studi</a></li>
						<li class="nav-item mb-2"><a class="nav-link" href="user.php">Users</a></li>

					</ul>
				</div>
			</aside>

			<section class="col-12 col-md-9">
				<div class="row g-4">
					<div class="col-12 col-md-6">
						<div class="card hero-glass text-center p-4">
							<h6 class="mb-2">Total Prodi</h6>
							<div class="display-6 fw-bold"><?php echo htmlspecialchars($prodiCount, ENT_QUOTES, 'UTF-8'); ?></div>
						</div>
					</div>

					<div class="col-12 col-md-6">
						<div class="card hero-glass text-center p-4">
							<h6 class="mb-2">Total Users</h6>
							<div class="display-6 fw-bold"><?php echo htmlspecialchars($userCount, ENT_QUOTES, 'UTF-8'); ?></div>
						</div>
					</div>

					<div class="col-12">
						<div class="card hero-glass p-4">
							<div class="d-flex justify-content-between align-items-center mb-3">
								<div>
									<h5 class="mb-0">Users per Prodi</h5>
									<small class="text-secondary">Distribusi jumlah user berdasarkan program studi</small>
								</div>
							</div>
							<div style="position:relative; height:320px;">
								<canvas id="usersPerProdiChart"></canvas>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
	</main>

	<script src="js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script>
		(function(){
			const labels = <?php echo json_encode($prodiLabels, JSON_HEX_TAG); ?>;
			const data = <?php echo json_encode($prodiData, JSON_HEX_TAG); ?>;

			const ctx = document.getElementById('usersPerProdiChart').getContext('2d');
			new Chart(ctx, {
				type: 'pie',
				data: {
					labels: labels,
					datasets: [{
						data: data,
						backgroundColor: labels.map((_,i) => `rgba(${(60+i*30)%255}, ${(120+i*50)%255}, ${(200+i*20)%255}, 0.85)`),
						borderColor: labels.map((_,i) => `rgba(${(60+i*30)%255}, ${(120+i*50)%255}, ${(200+i*20)%255}, 1)`),
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'right',
							labels: { color: '#ffffff' }
						},
						tooltip: {
							callbacks: {
								label: function(context) {
									const label = context.label || '';
									const value = context.parsed || 0;
									return label + ': ' + value;
								}
							}
						}
					}
				}
			});
		})();
	</script>
</body>
</html>
