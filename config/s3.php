<?php
// config/s3.php
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

function get_s3_client() {
    $endpoint = getenv('S3_ENDPOINT') ?: 'http://localhost:4566';
    
    // LocalStack configuration
    return new S3Client([
        'version'     => 'latest',
        'region'      => 'us-east-1',
        'endpoint'    => $endpoint, 
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key'    => 'test',
            'secret' => 'test',
        ],
    ]);
}
