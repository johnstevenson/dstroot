<?php

// error handler
error_reporting(-1);
$errors = [];
set_error_handler(function ($errno, $errstr) use (&$errors) {
    if (($pos = strpos($errstr, '14090086')) !== false) {
        $errstr = 'Error:'.substr($errstr, $pos);
    }
    $errors[] = $errstr;
});

echo 'PHP Version: ', PHP_VERSION, PHP_EOL;
echo 'OpenSSL version: ';

if (extension_loaded('openssl')) {
    $version = OPENSSL_VERSION_TEXT;
} else {
    $version = 'Missing';
}

echo $version, PHP_EOL;

echo 'cURL version: ';

if (extension_loaded('curl')) {
    $info = curl_version();
    $version = $info['version'];
    $version .= ', ssl '.(isset($info['ssl_version']) ? $info['ssl_version'] : 'missing');
} else {
    $version = 'Missing';
}

echo $version, PHP_EOL;

$targetUrl = 'https://certchain.testing.org';
$certs = [__DIR__.'/certs/bundle_both.crt', __DIR__.'/certs/bundle_root.crt'];

foreach ($certs as $bundle) {
    // streams
    printf("\nstreams (%s)\n", basename($bundle));

    if (extension_loaded('openssl')) {
        // reset errors
        $errors = [];
        $options['ssl']['cafile'] = $bundle;
        $ctx = stream_context_create($options);
        $content = @file_get_contents($targetUrl, false, $ctx);
    } else {
        $errors = ['openssl extension is missing'];
    }

    if ($errors) {
        if ($filtered = preg_grep('/14090086/', $errors)) {
            echo $filtered[0], PHP_EOL;
        } else {
            echo implode(PHP_EOL, $errors), PHP_EOL;
        }
        echo 'FAILED', PHP_EOL;
    } else {
        echo $content, PHP_EOL;
        echo 'SUCCESS', PHP_EOL;
    }

    // curl
    printf("\ncurl (%s)\n", basename($bundle));

    if (extension_loaded('curl')) {
        $error = '';
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $targetUrl);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_CAINFO, $bundle);
        $content = curl_exec($curlHandle);

        if($errno = curl_errno($curlHandle)) {
            $error = curl_strerror($errno);
        }

        curl_close($curlHandle);
    } else {
        $error = 'curl extension is missing';
    }

    if ($error) {
        echo $error, PHP_EOL;
        echo 'FAILED', PHP_EOL;
    } else {
        echo $content, PHP_EOL;
        echo 'SUCCESS', PHP_EOL;
    }
}
