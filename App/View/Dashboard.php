<?php

session_start();

if (empty($_SESSION['user_id'])) {
    $app = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')));
    $app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');
    $login = ($app !== '' ? $app : '') . '/index.php';
    header('Location: ' . $login);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$shopTitle = 'Your shop';
$email = '';

require_once __DIR__ . '/../Model/Config.php';
$conn = Config::getConnection();
if ($conn) {
    $stmt = $conn->prepare('SELECT shop_title, email FROM users WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $shopTitle = $row['shop_title'] ?: $shopTitle;
            $email = (string) ($row['email'] ?? '');
        }
        $stmt->close();
    }
    $conn->close();
}

$app = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')));
$app = $app === '/' || $app === '\\' ? '' : rtrim($app, '/');
$logoutUrl = ($app !== '' ? $app : '') . '/App/Controller/Logout.php';
$dashboardJs = ($app !== '' ? $app : '') . '/Assets/Js/dashboard.js';
$pagerJs = ($app !== '' ? $app : '') . '/Assets/Js/pagination.js';
$productsJs = ($app !== '' ? $app : '') . '/Assets/Js/products.js';
$timeSlotsJs = ($app !== '' ? $app : '') . '/Assets/Js/timeSlots.js';
$qrManageJs = ($app !== '' ? $app : '') . '/Assets/Js/qrManage.js';
$ordersJs = ($app !== '' ? $app : '') . '/Assets/Js/orders.js';
$dashboardHomeJs = ($app !== '' ? $app : '') . '/Assets/Js/dashboardHome.js';
$navBase = ($app !== '' ? $app : '') . '/App/View/navFiles';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($shopTitle, ENT_QUOTES, 'UTF-8'); ?> — Dashboard</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(($app !== '' ? $app : '') . '/Assets/Css/main.css', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo">Food<span>Shop</span></div>

    <nav class="nav">
      <a href="#" class="nav-item active" data-title="Dashboard" data-page="<?php echo htmlspecialchars($navBase . '/DashboardHome.php', ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a>
      <a href="#" class="nav-item" data-title="Orders" data-page="<?php echo htmlspecialchars($navBase . '/Orders.php', ENT_QUOTES, 'UTF-8'); ?>">Orders</a>
      <a href="#" class="nav-item" data-title="Products" data-page="<?php echo htmlspecialchars($navBase . '/Products.php', ENT_QUOTES, 'UTF-8'); ?>">Products</a>
      <a href="#" class="nav-item" data-title="Time Slots" data-page="<?php echo htmlspecialchars($navBase . '/TimeSlots.php', ENT_QUOTES, 'UTF-8'); ?>">Time Slots</a>
      <a href="#" class="nav-item" data-title="QR Manage" data-page="<?php echo htmlspecialchars($navBase . '/QRManage.php', ENT_QUOTES, 'UTF-8'); ?>">QR Manage</a>
    </nav>

    <a href="<?php echo htmlspecialchars($logoutUrl); ?>" class="logout-btn">Logout</a>
  </aside>

  <!-- Main Content -->
  <main class="main">

    <!-- Top Bar -->
    <header class="topbar">
      <h2 id="pageTitle">Dashboard</h2>
      <div class="user-info">
        <span><?php echo htmlspecialchars($shopTitle); ?></span>
      </div>
    </header>

    <!-- Content Area -->
    <section class="content">
      <div id="tabContent" class="tab-content" aria-live="polite">
        <?php require __DIR__ . '/navFiles/DashboardHome.php'; ?>
      </div>

    </section>

  </main>

</div>
<script>
  window.__APP_BASE__ = <?php echo json_encode($app, JSON_UNESCAPED_SLASHES); ?>;
  window.__DASH_USER__ = <?php echo json_encode(['shopTitle' => $shopTitle, 'email' => $email], JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo htmlspecialchars($dashboardJs, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars($pagerJs, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars($productsJs, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars($timeSlotsJs, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars($qrManageJs, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars($ordersJs, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars($dashboardHomeJs, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
