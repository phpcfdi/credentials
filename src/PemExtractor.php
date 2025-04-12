<?php

declare(strict_types=1);

namespace PhpCfdi\Credentials;

class PemExtractor
{
    public function __construct(private readonly string $contents)
    {
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function extractCertificate(): string
    {
        return $this->extractBase64('CERTIFICATE');
    }

    public function extractPublicKey(): string
    {
        return $this->extractBase64('PUBLIC KEY');
    }

    public function extractPrivateKey(): string
    {
        // see https://github.com/kjur/jsrsasign/wiki/Tutorial-for-PKCS5-and-PKCS8-PEM-private-key-formats-differences
        // PKCS#8 plain private key
        if ('' !== $extracted = $this->extractBase64('PRIVATE KEY')) {
            return $extracted;
        }
        // PKCS#5 plain private key
        if ('' !== $extracted = $this->extractBase64('RSA PRIVATE KEY')) {
            return $extracted;
        }
        // PKCS#5 encrypted private key
        if ('' !== $extracted = $this->extractRsaProtected()) {
            return $extracted;
        }
        // PKCS#8 encrypted private key
        return $this->extractBase64('ENCRYPTED PRIVATE KEY');
    }

    protected function extractBase64(string $type): string
    {
        $matches = [];
        $type = preg_quote($type, '/');
        $pattern = '/^'
            . '-----BEGIN ' . $type . '-----\r?\n'
            . '([A-Za-z0-9+\/=]+\r?\n)+'
            . '-----END ' . $type . '-----\r?\n?'
            . '$/m';
        preg_match($pattern, $this->getContents(), $matches);
        return $this->normalizeLineEndings(strval($matches[0] ?? ''));
    }

    protected function extractRsaProtected(): string
    {
        $matches = [];
        $pattern = '/^'
            . '-----BEGIN RSA PRIVATE KEY-----\r?\n'
            . 'Proc-Type: .+\r?\n'
            . 'DEK-Info: .+\r?\n\r?\n'
            . '([A-Za-z0-9+\/=]+\r?\n)+'
            . '-----END RSA PRIVATE KEY-----\r?\n?'
            . '$/m';
        preg_match($pattern, $this->getContents(), $matches);
        return $this->normalizeLineEndings(strval($matches[0] ?? ''));
    }

    /**
     * Changes EOL CRLF or LF to PHP_EOL.
     * This won't alter CR that are not at EOL.
     * This won't alter LF+CR used in old Mac style
     *
     * @internal
     */
    protected function normalizeLineEndings(string $content): string
    {
        // move '\r\n' or '\n' to PHP_EOL
        // first substitution '\r\n' -> '\n'
        // second substitution '\n' -> PHP_EOL
        // remove any EOL at the EOF
        return rtrim(str_replace(["\r\n", "\n"], ["\n", PHP_EOL], $content), PHP_EOL);
    }
}
