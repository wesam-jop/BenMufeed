<?php

namespace App\Services;

use RuntimeException;

class QzSigningService
{
    protected string $directory;

    public function __construct()
    {
        $this->directory = storage_path('app/qz');
    }

    public function ensureKeys(): void
    {
        if ($this->hasKeys()) {
            return;
        }

        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }

        $bundledPrivateKey = resource_path('qz/private-key.pem');
        $bundledCertificate = resource_path('qz/digital-certificate.txt');

        if (is_file($bundledPrivateKey) && is_file($bundledCertificate)) {
            copy($bundledPrivateKey, $this->privateKeyPath());
            copy($bundledCertificate, $this->certificatePath());

            return;
        }

        $opensslConfig = resource_path('qz/openssl.cnf');

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        if (is_file($opensslConfig)) {
            $config['config'] = $opensslConfig;
        }

        $privateKey = openssl_pkey_new($config);

        if ($privateKey === false) {
            throw new RuntimeException('Unable to generate QZ signing key.');
        }

        openssl_pkey_export($privateKey, $privateKeyPem, null, $config);
        file_put_contents($this->privateKeyPath(), $privateKeyPem);

        $dn = [
            'commonName' => config('app.name', 'BenMufeed').' POS',
        ];

        $csr = openssl_csr_new($dn, $privateKey, array_merge($config, ['digest_alg' => 'sha256']));

        if ($csr === false) {
            throw new RuntimeException('Unable to generate QZ certificate signing request.');
        }

        $certificate = openssl_csr_sign($csr, null, $privateKey, 3650, array_merge($config, ['digest_alg' => 'sha256']));

        if ($certificate === false) {
            throw new RuntimeException('Unable to generate QZ certificate.');
        }

        openssl_x509_export($certificate, $certificatePem);
        file_put_contents($this->certificatePath(), $certificatePem);
    }

    public function hasKeys(): bool
    {
        return is_file($this->certificatePath()) && is_file($this->privateKeyPath());
    }

    public function certificate(): string
    {
        $this->ensureKeys();

        return (string) file_get_contents($this->certificatePath());
    }

    public function sign(string $request): string
    {
        $this->ensureKeys();

        $privateKey = openssl_pkey_get_private((string) file_get_contents($this->privateKeyPath()));

        if ($privateKey === false) {
            throw new RuntimeException('Unable to load QZ private key.');
        }

        $signature = '';

        if (! openssl_sign($request, $signature, $privateKey, OPENSSL_ALGO_SHA512)) {
            throw new RuntimeException('Unable to sign QZ request.');
        }

        return base64_encode($signature);
    }

    protected function certificatePath(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'digital-certificate.txt';
    }

    protected function privateKeyPath(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'private-key.pem';
    }
}
