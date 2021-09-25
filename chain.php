<?php

require __DIR__.'/CertBuilder.php';

define('ISRG_ROOT_X1', 'ca_isrg_root_x1.crt');
define('ISRG_ROOT_X1_CROSS', 'ca_isrg_root_x1_cross.crt');
define('DST_ROOT_CA_X3', 'ca_dst_root_x3.crt');
define('INTERMEDIATE_R3', 'ca_intermediate_r3.crt');
define('SERVER_CERT', 'server.crt');
define('SERVER_KEY', 'server.key');

$builder = new CertBuilder();

// Create root ca certificates
$config = ['config' =>  __DIR__.'/openssl-ca.cnf'];

$dn = $builder->makeDn('Mock ISRG Root X1', 'Not Internet Security Research Group');
list($isrgCrs, $isrgKey) = $builder->createCsr($dn, $config);
$isrgCert = $builder->signCsr($isrgCrs, null, $isrgKey, 3650, 1001, $config);
$builder->saveCert($isrgCert, ISRG_ROOT_X1);

$dn = $builder->makeDn('Mock DST Root CA X3', 'Not Digital Signature Trust Co');
list($dstCrs, $dstKey) = $builder->createCsr($dn, $config);
$dstCert = $builder->signCsr($dstCrs, null, $dstKey, 0, 10001, $config);
$builder->saveCert($dstCert, DST_ROOT_CA_X3);

// Create cross-signed ISRG_ROOT_X1
$isrgCrossCert = $builder->signCsr($isrgCrs, $dstCert, $dstKey, 1100, 1002, $config);
$builder->saveCert($isrgCrossCert, ISRG_ROOT_X1_CROSS);

// Create intermediate R3 ca certificate
$config = ['config' => __DIR__.'/openssl-ica.cnf'];

$dn = $builder->makeDn('Mock R3', "Not Let's Encrypt");
list($r3Crs, $r3Key) = $builder->createCsr($dn, $config);
$r3Cert = $builder->signCsr($r3Crs, $isrgCert, $isrgKey, 365, 2001, $config);
$builder->saveCert($r3Cert, INTERMEDIATE_R3);

// Create server certificate
$config = ['config' => __DIR__.'/openssl-server.cnf'];

$dn = $builder->makeDn('dstroot.testing.org', 'DstRoot Testing Org');
list($serverCrs, $serverKey) = $builder->createCsr($dn, $config);

$serverCert = $builder->signCsr($serverCrs, $r3Cert, $r3Key, 90, 3001, $config);
$builder->saveCert($serverCert, SERVER_CERT);
$builder->saveKey($serverKey, SERVER_KEY, $config);

// Create server bundles
$serverChain = [SERVER_CERT, INTERMEDIATE_R3];
$builder->createBundle($serverChain, 'server_alt_chain.crt');

$crossChain = array_merge($serverChain, [ISRG_ROOT_X1_CROSS]);
$builder->createBundle($crossChain, 'server_def_chain.crt');

// Create client bundles
$rootClient = [ISRG_ROOT_X1];
$builder->createBundle($rootClient, 'bundle_root.crt');

$crossClient = array_merge($rootClient, [DST_ROOT_CA_X3]);
$builder->createBundle($crossClient, 'bundle_both.crt');

echo 'Certificates and bundles created. Restart your webserver to use them.', PHP_EOL;
