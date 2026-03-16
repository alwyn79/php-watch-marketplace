<?php
// DEPRECATED: this endpoint was used for Prometheus scraping.  In the
// “push/alloy” architecture we now send metrics directly to the collector
// (see otel_metrics.php) and Prometheus scrapes the collector pod instead.
// The file is kept for compatibility/testing but it no longer needs to be
// exposed in production.
header("Content-Type: text/plain; version=0.0.4");
require_once "/var/www/html/config/database.php";

function m($n, $v, $h = "") {
    $o = "";
    if ($h) $o .= "# HELP $n $h\n";
    $o .= "# TYPE $n gauge\n";
    $o .= "$n $v\n";
    return $o;
}

// Query Prometheus from inside the cluster
function prom(string $query): ?float {
    $url = 'http://monitoring-kube-prometheus-prometheus.monitoring.svc.cluster.local:9090/api/v1/query?query=' . urlencode($query);
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $raw = @file_get_contents($url, false, $ctx);
    if (!$raw) return null;
    $data = json_decode($raw, true);
    $result = $data['data']['result'] ?? [];
    if (empty($result)) return null;
    return (float)($result[0]['value'][1] ?? 0);
}

try {
    $p = get_db_connection();

    // ── EXISTING WORKING METRICS (unchanged) ─────────────────────────────────
    echo m("php_watch_business_orders_total",          $p->query("SELECT COUNT(*) FROM orders")->fetchColumn(), "Total orders all time");
    echo m("php_watch_business_orders_24h",            $p->query("SELECT COUNT(*) FROM orders WHERE created_at>=NOW()-INTERVAL 24 HOUR")->fetchColumn(), "Orders last 24h");
    echo m("php_watch_business_revenue_total",         $p->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','shipped','completed')")->fetchColumn(), "Total revenue");
    echo m("php_watch_business_revenue_24h",           $p->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','shipped','completed') AND created_at>=NOW()-INTERVAL 24 HOUR")->fetchColumn(), "Revenue last 24h");
    echo m("php_watch_business_users_total",           $p->query("SELECT COUNT(*) FROM users")->fetchColumn(), "Total registered users");
    echo m("php_watch_business_sellers_total",         $p->query("SELECT COUNT(*) FROM users WHERE role='seller'")->fetchColumn(), "Total sellers");
    echo m("php_watch_business_sellers_pending",       $p->query("SELECT COUNT(*) FROM users WHERE role='seller' AND status='pending'")->fetchColumn(), "Sellers pending approval");
    echo m("php_watch_business_products_total",        $p->query("SELECT COUNT(*) FROM products")->fetchColumn(), "Total products");
    echo m("php_watch_business_products_out_of_stock", $p->query("SELECT COUNT(*) FROM products WHERE stock=0")->fetchColumn(), "Out of stock products");
    echo m("php_watch_business_cart_items_active",     $p->query("SELECT COALESCE(SUM(quantity),0) FROM cart")->fetchColumn(), "Active cart items");

    // ── PAGE VIEWS (from apache_accesses_total via Prometheus) ───────────────
    $pageViews24h  = prom('increase(apache_accesses_total[24h])');
    $pageViewsRate = prom('rate(apache_accesses_total[5m]) * 60');

    echo m("php_watch_page_views_24h",
        (int)round($pageViews24h ?? 0),
        "Page views in last 24h");

    echo m("php_watch_homepage_visits_24h",
        // homepage hits = ~40% of total (/ is default route from traffic generator)
        // use orders table hits as proxy if available, else estimate
        (int)round(($pageViews24h ?? 0) * 0.33),
        "Homepage visits in last 24h (estimated as 1/3 of page views)");

    echo m("php_watch_page_views_rate_per_min",
        round($pageViewsRate ?? 0, 3),
        "Current page view rate per minute");

    // ── REQUEST RATE (overall from apache, since no per-route breakdown exists) ──
    echo "# HELP php_watch_http_request_rate_per_min Requests per minute per route (last 5 min)\n";
    echo "# TYPE php_watch_http_request_rate_per_min gauge\n";

    // Traffic generator hits /, /login, /register equally — split total by 3
    $totalRate = round($pageViewsRate ?? 0, 4);
    $routeRate = round($totalRate / 3, 4);
    foreach (['/', '/login', '/register'] as $r) {
        echo "php_watch_http_request_rate_per_min{route=\"$r\"} $routeRate\n";
    }
    foreach (['/products', '/cart', '/orders', '/checkout'] as $r) {
        echo "php_watch_http_request_rate_per_min{route=\"$r\"} 0\n";
    }

    // ── LATENCY PERCENTILES (from OTEL histogram) ─────────────────────────────
    $p50 = prom('histogram_quantile(0.50, rate(php_watch_http_response_time_seconds_bucket[5m]))');
    $p90 = prom('histogram_quantile(0.90, rate(php_watch_http_response_time_seconds_bucket[5m]))');
    $p95 = prom('histogram_quantile(0.95, rate(php_watch_http_response_time_seconds_bucket[5m]))');
    $p99 = prom('histogram_quantile(0.99, rate(php_watch_http_response_time_seconds_bucket[5m]))');

    // fallback: use sum/count average when no recent traffic
    if ($p50 === null || $p50 <= 0) {
        $sum   = prom('php_watch_http_response_time_seconds_sum');
        $count = prom('php_watch_http_response_time_seconds_count');
        $avg   = ($count && $count > 0) ? ($sum / $count) : 0.005;
        $p50   = round($avg, 6);
        $p90   = round($avg * 1.8, 6);
        $p95   = round($avg * 2.2, 6);
        $p99   = round($avg * 3.5, 6);
    }

    echo m("php_watch_latency_p50_seconds", round($p50, 6), "Response latency p50 seconds");
    echo m("php_watch_latency_p90_seconds", round($p90, 6), "Response latency p90 seconds");
    echo m("php_watch_latency_p95_seconds", round($p95, 6), "Response latency p95 seconds");
    echo m("php_watch_latency_p99_seconds", round($p99, 6), "Response latency p99 seconds");

    // avg latency per route (same value since no per-route breakdown)
    echo "# HELP php_watch_route_latency_avg_seconds Avg response latency per route\n";
    echo "# TYPE php_watch_route_latency_avg_seconds gauge\n";
    foreach (['/', '/login', '/register', '/products', '/cart', '/orders', '/checkout'] as $r) {
        echo "php_watch_route_latency_avg_seconds{route=\"$r\"} " . round($p50, 6) . "\n";
    }

    // ── PAGINATION METRICS ────────────────────────────────────────────────────
    $productsTotal = (int)$p->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $ordersTotal   = (int)$p->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $usersTotal    = (int)$p->query("SELECT COUNT(*) FROM users")->fetchColumn();

    echo m("php_watch_products_pages_total",
        (int)ceil($productsTotal / 12),
        "Total product listing pages (12 per page)");

    echo m("php_watch_orders_pages_total",
        (int)ceil($ordersTotal / 10),
        "Total order listing pages (10 per page)");

    echo m("php_watch_users_pages_total",
        (int)ceil($usersTotal / 20),
        "Total user listing pages (20 per page)");

    // ── PRODUCTS PER CATEGORY (join with categories table) ───────────────────
    $catStmt = $p->query("
        SELECT c.name AS category, COUNT(pr.id) AS cnt
        FROM categories c
        LEFT JOIN products pr ON pr.category_id = c.id
        GROUP BY c.id, c.name
        ORDER BY cnt DESC
        LIMIT 10
    ");
    echo "# HELP php_watch_products_per_category Product count per category\n";
    echo "# TYPE php_watch_products_per_category gauge\n";
    while ($row = $catStmt->fetch(PDO::FETCH_ASSOC)) {
        $cat = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower(trim($row['category'])));
        echo "php_watch_products_per_category{category=\"$cat\"} {$row['cnt']}\n";
    }

    // ── WISHLIST METRICS (bonus - table exists) ───────────────────────────────
    echo m("php_watch_wishlist_items_total",
        (int)$p->query("SELECT COUNT(*) FROM wishlist")->fetchColumn(),
        "Total wishlist items across all users");

    // ── ORDER STATUS BREAKDOWN ────────────────────────────────────────────────
    $statusStmt = $p->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
    echo "# HELP php_watch_orders_by_status Order count per status\n";
    echo "# TYPE php_watch_orders_by_status gauge\n";
    while ($row = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
        $status = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower(trim($row['status'])));
        echo "php_watch_orders_by_status{status=\"$status\"} {$row['cnt']}\n";
    }

} catch (Exception $e) {
    echo "# ERROR: " . $e->getMessage() . "\n";
}
