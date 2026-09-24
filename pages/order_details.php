<?php
declare(strict_types=1);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
header('Location: order_view.php' . ($id > 0 ? ('?id=' . $id) : ''));
exit;

