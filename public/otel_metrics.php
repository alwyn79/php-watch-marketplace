<?php
// DEPRECATED: metrics are now sent via `otel.php` which also includes
// tracing/logging.  This file is kept to avoid fatal includes but it will do
// nothing if the combined helper is loaded first.
if (defined('OTEL_INITIALIZED')) {
    return;
}
define('OTEL_INITIALIZED', true);

require_once __DIR__ . '/../vendor/autoload.php';  // ✅ correct

use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\SDK\Common\Attribute\Attributes;

$otelRequestStart = microtime(true);
$otelEndpoint     = getenv('OTEL_EXPORTER_OTLP_ENDPOINT') ?: 'http://otel-collector:4318';
$otelRoute        = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$otelMethod       = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$transport = (new OtlpHttpTransportFactory())->create(
    $otelEndpoint . '/v1/metrics',
    'application/x-protobuf'
);

$exporter = new MetricExporter($transport);
$reader   = new ExportingReader($exporter);

$resource = ResourceInfo::create(Attributes::create([
    ResourceAttributes::SERVICE_NAME    => getenv('OTEL_SERVICE_NAME') ?: 'php-watch',
    ResourceAttributes::SERVICE_VERSION => '1.0.0',
    'deployment.environment'            => getenv('APP_ENV') ?: 'production',
]));

$GLOBALS['otelMeterProvider'] = MeterProvider::builder()
    ->setResource($resource)
    ->addReader($reader)
    ->build();

$meter = $GLOBALS['otelMeterProvider']->getMeter('php-watch');

// ── HTTP Metrics ──────────────────────────────────────────────────────────────
$GLOBALS['otelRequestCounter'] = $meter->createCounter(
    'http_requests_total', '{requests}', 'Total HTTP requests'
);
$GLOBALS['otelErrorCounter'] = $meter->createCounter(
    'http_errors_total', '{errors}', 'Total HTTP errors'
);
$GLOBALS['otelLatency'] = $meter->createHistogram(
    'http_response_time_seconds', 'seconds', 'Response time per route'
);
$GLOBALS['otelMemory'] = $meter->createObservableGauge(
    'php_memory_usage_bytes', 'bytes', 'PHP memory usage'
);
$GLOBALS['otelMemory']->observe(function ($observer) {
    $observer->observe(memory_get_usage(true),      Attributes::create(['type' => 'current']));
    $observer->observe(memory_get_peak_usage(true), Attributes::create(['type' => 'peak']));
});

// ── FIX 2: Business gauge instruments registered ONCE here ───────────────────
// Values are stored in $GLOBALS['otelBusinessData'] and updated every 60s.
// The closures below always read the latest value at flush time.
$GLOBALS['otelBusinessData'] = [
    'orders_total'          => 0,
    'orders_24h'            => 0,
    'revenue_total'         => 0.0,
    'revenue_24h'           => 0.0,
    'users_total'           => 0,
    'sellers_total'         => 0,
    'sellers_pending'       => 0,
    'products_total'        => 0,
    'products_out_of_stock' => 0,
    'cart_items_active'     => 0,
];

$businessMeter = $GLOBALS['otelMeterProvider']->getMeter('php-watch-business');

$businessMeter->createObservableGauge('business_orders_total', '{orders}', 'Total orders all time')
    ->observe(function ($obs) {
        $obs->observe($GLOBALS['otelBusinessData']['orders_total'], Attributes::create([]));
    });

$businessMeter->createObservableGauge('business_orders_24h', '{orders}', 'Orders in last 24 hours')
    ->observe(function ($obs) {
        $obs->observe($GLOBALS['otelBusinessData']['orders_24h'], Attributes::create([]));
    });

$businessMeter->createObservableGauge('business_revenue_total', '{currency}', 'Total revenue all time')
    ->observe(function ($obs) {
        $obs->observe($GLOBALS['otelBusinessData']['revenue_total'], Attributes::create([]));
    });

$businessMeter->createObservableGauge('business_revenue_24h', '{currency}', 'Revenue in last 24 hours')
    ->observe(function ($obs) {
        $obs->observe($GLOBALS['otelBusinessData']['revenue_24h'], Attributes::create([]));
    });

$businessMeter->createObservableGauge('business_users_total', '{users}', 'Total registered users')
    ->observe(function ($obs) {
        $obs->observe($GLOBALS['otelBusinessData']['users_total'], Attributes::create([]));
    });

$businessMeter->createObservableGauge('business_sellers_total', '{sellers}', 'Total sellers')
    ->observe(function ($obs) {
        $obs->observe($GLOBALS['otelBusinessData']['sellers_total'], Attributes::create([]));
    });

$businessMeter->createObservableGauge('business_sellers_pending', '{sellers}', 'Sellers pending approval')
    ->observe(function ($obs) {
        $obs->observe($GLOBALS['otelBusinessData']['sellers_pending'], Attributes::create([]));
    });

$businessMeter->createObservableGauge('business_products_total', '{products}', 'Total products')
    ->observe(function ($obs) {
        $obs->observe($GLOBALS['otelBusinessData']['products_total'], Attributes::create([]));
    });

$businessMeter->createObservableGauge('business_products_out_of_stock', '{products}', 'Out of stock products')
    ->observe(function ($obs) {
        $obs->observe($GLOBALS['otelBusinessData']['products_out_of_stock'], Attributes::create([]));
    });

$businessMeter->createObservableGauge('business_cart_items_active', '{items}', 'Items currently in carts')
    ->observe(function ($obs) {
        $obs->observe($GLOBALS['otelBusinessData']['cart_items_active'], Attributes::create([]));
    });

// ── 60s DB poll — updates the values the gauges above will read ───────────────
$lockFile = sys_get_temp_dir() . '/otel_business_metrics.lock';
$lastRun  = file_exists($lockFile) ? (int)file_get_contents($lockFile) : 0;

if ((time() - $lastRun) >= 60) {
    file_put_contents($lockFile, time());
    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = get_db_connection();

        $GLOBALS['otelBusinessData']['orders_total']          = (int)   $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $GLOBALS['otelBusinessData']['orders_24h']            = (int)   $pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= NOW() - INTERVAL 24 HOUR")->fetchColumn();
        $GLOBALS['otelBusinessData']['revenue_total']         = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','shipped','completed')")->fetchColumn();
        $GLOBALS['otelBusinessData']['revenue_24h']           = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','shipped','completed') AND created_at >= NOW() - INTERVAL 24 HOUR")->fetchColumn();
        $GLOBALS['otelBusinessData']['users_total']           = (int)   $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $GLOBALS['otelBusinessData']['sellers_total']         = (int)   $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn();
        $GLOBALS['otelBusinessData']['sellers_pending']       = (int)   $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller' AND status = 'pending'")->fetchColumn();
        $GLOBALS['otelBusinessData']['products_total']        = (int)   $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $GLOBALS['otelBusinessData']['products_out_of_stock'] = (int)   $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();
        $GLOBALS['otelBusinessData']['cart_items_active']     = (int)   $pdo->query("SELECT COALESCE(SUM(quantity),0) FROM cart")->fetchColumn();

        error_log("[OTEL] Business data refreshed: orders=" . $GLOBALS['otelBusinessData']['orders_total'] . " users=" . $GLOBALS['otelBusinessData']['users_total']);
    } catch (\Exception $e) {
        error_log("[OTEL] Business metrics failed: " . $e->getMessage());
    }
}

// ── Shutdown: record HTTP metrics then flush everything to collector ───────────
register_shutdown_function(function () use ($otelRequestStart, $otelRoute, $otelMethod) {
    $statusCode  = http_response_code() ?: 200;
    $statusClass = intdiv($statusCode, 100) . 'xx';
    $duration    = microtime(true) - $otelRequestStart;

    $attrs = Attributes::create([
        'route'        => $otelRoute,
        'method'       => $otelMethod,
        'status_code'  => (string) $statusCode,
        'status_class' => $statusClass,
    ]);

    $GLOBALS['otelRequestCounter']->add(1, $attrs);
    $GLOBALS['otelLatency']->record($duration, $attrs);

    if ($statusCode >= 400) {
        $GLOBALS['otelErrorCounter']->add(1, $attrs);
    }

    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR])) {
        $GLOBALS['otelErrorCounter']->add(1, Attributes::create([
            'route'        => $otelRoute,
            'method'       => $otelMethod,
            'status_code'  => '500',
            'status_class' => '5xx',
        ]));
    }

    $GLOBALS['otelMeterProvider']->shutdown();
    error_log("[OTEL] route=$otelRoute status=$statusCode duration=" . round($duration * 1000, 2) . "ms");
});