<?php

class CertBuilder
{
    private $certDir;
    private $certOptions;
    private $keyOptions;

    public function __construct()
    {
        $this->certDir = __DIR__.DIRECTORY_SEPARATOR.'certs';
        $this->certOptions = [
            'digest_alg' => 'sha256',
        ];

        $this->keyOptions = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        ];
    }

    public function createCsr(array $dn, array $config, $key = null)
    {
        if (!$key) {
            $options = $this->keyOptions + $config;
            if (!$key = openssl_pkey_new($options)) {
                throw new \RuntimeException('Cannot create private key');
            }
        }

        $options = $this->certOptions + $config;
        if (!$csr = openssl_csr_new($dn, $key, $options)) {
            throw new \RuntimeException('Cannot create crs');
        }

        return [$csr, $key];
    }

    public function signCsr($csr, $cert, $key, $days, $serial, array $config)
    {
        $options = $this->certOptions + $config;

        if (!$cert = openssl_csr_sign($csr, $cert, $key, $days, $options, $serial)) {
            throw new \RuntimeException('Cannot sign crs');
        }

        return $cert;
    }

    public function createBundle(array $certNames, $name)
    {
        $certs = [];

        foreach ($certNames as $filename) {
            $path = $this->certDir.DIRECTORY_SEPARATOR.$filename;

            if (!$content = @file_get_contents($path)) {
                throw new \RuntimeException('Unable to open cert file '.$path);
            }

            $path = 'file://'.$path;

            if ((!$values = openssl_x509_parse($path)) || !isset($values['subject']['CN'])) {
                throw new \RuntimeException('Unable to parse cert file '.$path);
            }

            $title = $values['subject']['CN'];
            $prefix = [$title, str_repeat('=', strlen($title))];

            $certs[] = sprintf("%s\n%s\n", implode("\n", $prefix), trim($content));
        }

        $bundle = implode("\n", $certs);
        $filename = $this->certDir.DIRECTORY_SEPARATOR.$name;

        if (!@file_put_contents($filename, $bundle)) {
            throw new \RuntimeException('Unable to write bundle file '.$filename);
        }
    }

    public function makeDn($commonName, $orgName)
    {
        return [
            'countryName' => 'US',
            'organizationName' => $orgName,
            'commonName' => $commonName,
        ];
    }

    public function saveCert($cert, $filename)
    {
        $filename = $this->certDir.DIRECTORY_SEPARATOR.$filename;

        if (!openssl_x509_export_to_file($cert, $filename)) {
            throw new \RuntimeException('Cannot export certificate to: '.$filename);
        }
    }

    public function saveKey($key, $filename, $config)
    {
        $filename = $this->certDir.DIRECTORY_SEPARATOR.$filename;

        if (!openssl_pkey_export_to_file($key, $filename, null, $config)) {
            throw new \RuntimeException('Cannot export key to: '.$filename);
        }
    }
}
