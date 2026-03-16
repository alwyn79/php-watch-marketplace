<?php
// DEPRECATED: instrumentation is now merged into `otel.php`.
// Include that file instead; this stub remains for backward compatibility.
if (defined('OTEL_INITIALIZED')) {
    return;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$startTime = microtime(true);
$traceId = bin2hex(random_bytes(16));
$spanId = bin2hex(random_bytes(8));
$startNs = (int)($startTime * 1e9);
$ep = getenv('OTEL_EXPORTER_OTLP_ENDPOINT') ?: 'http://otel-collector.watch-app.svc.cluster.local:4318';

register_shutdown_function(function() use ($ep,$traceId,$spanId,$path,$method,$startTime,$startNs) {
    $sc = http_response_code() ?: 200;
    $dm = round((microtime(true)-$startTime)*1000,2);
    $dn = (int)($dm*1e6);

    $tp = json_encode(["resourceSpans"=>[["resource"=>["attributes"=>[["key"=>"service.name","value"=>["stringValue"=>"php-watch"]],["key"=>"k8s.namespace.name","value"=>["stringValue"=>"watch-app"]]]],"scopeSpans"=>[["scope"=>["name"=>"php-watch"],"spans"=>[["traceId"=>$traceId,"spanId"=>$spanId,"name"=>$method." ".$path,"kind"=>2,"startTimeUnixNano"=>(string)$startNs,"endTimeUnixNano"=>(string)($startNs+$dn),"attributes"=>[["key"=>"http.method","value"=>["stringValue"=>$method]],["key"=>"http.route","value"=>["stringValue"=>$path]],["key"=>"http.status_code","value"=>["intValue"=>$sc]]],"status"=>["code"=>$sc>=500?2:1]]]]]]]]);

    $lp = json_encode(["resourceLogs"=>[["resource"=>["attributes"=>[["key"=>"service.name","value"=>["stringValue"=>"php-watch"]],["key"=>"k8s.namespace.name","value"=>["stringValue"=>"watch-app"]]]],"scopeLogs"=>[["scope"=>["name"=>"php-watch"],"logRecords"=>[["timeUnixNano"=>(string)(int)(microtime(true)*1e9),"severityText"=>$sc>=500?"ERROR":($sc>=400?"WARN":"INFO"),"severityNumber"=>$sc>=500?17:($sc>=400?13:9),"body"=>["stringValue"=>$method." ".$path." ".$sc." ".$dm."ms"],"traceId"=>$traceId,"spanId"=>$spanId,"attributes"=>[["key"=>"http.method","value"=>["stringValue"=>$method]],["key"=>"http.path","value"=>["stringValue"=>$path]],["key"=>"http.status_code","value"=>["intValue"=>$sc]]]]]]]]]]);

    foreach([["traces",$tp],["logs",$lp]] as $item) {
        $ctx = stream_context_create(["http"=>["method"=>"POST","header"=>"Content-Type: application/json","content"=>$item[1],"timeout"=>2,"ignore_errors"=>true]]);
        @file_get_contents($ep."/v1/".$item[0],false,$ctx);
    }
});