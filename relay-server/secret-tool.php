<?php
/**
 * IntelliPresta relay — command line helper for encrypted secrets.
 *
 * Run on the server, over SSH. Never from a browser: this file refuses to run
 * unless PHP is in CLI mode.
 *
 *   php secret-tool.php genkey    generate a master key
 *   php secret-tool.php encrypt   read a secret from stdin, print the blob
 *   php secret-tool.php check     report which configured secrets are encrypted
 *
 * "encrypt" deliberately reads from standard input rather than an argument.
 * A secret passed on the command line is visible in the shell history and in
 * the process list to every other user on a shared host.
 */

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    exit("Forbidden\n");
}

require_once dirname(__FILE__) . '/secrets.php';

$command = isset($argv[1]) ? $argv[1] : '';

switch ($command) {
    case 'genkey':
        $key = openssl_random_pseudo_bytes(32, $strong);
        if ($key === false || !$strong) {
            fwrite(STDERR, "No cryptographically strong random source available.\n");
            exit(1);
        }
        $encoded = base64_encode($key);
        echo "Master key (base64):\n\n    " . $encoded . "\n\n";
        echo "Store it ONE of these two ways, never both, and never in config.php.\n";
        echo "Both keep the key on this server - the point is to keep it out of the\n";
        echo "file that holds the ciphertext, and out of the web root.\n\n";
        echo "  1. RECOMMENDED on shared hosting - key file outside the web root:\n";
        echo "       umask 077 && printf '%s' '" . $encoded . "' > ~/.ipresta-key\n";
        echo "       chmod 600 ~/.ipresta-key\n";
        echo "     then in config.php:  define('IPRESTA_KEY_FILE', '/home/USER/.ipresta-key');\n\n";
        echo "  2. A real environment variable, if the host provides one - a panel\n";
        echo "     setting or a vhost directive that you cannot edit over FTP.\n";
        echo "       IPRESTA_MASTER_KEY=" . $encoded . "\n";
        echo "     Do NOT use 'SetEnv' in an .htaccess inside the web root: that writes\n";
        echo "     the key into a file next to the ciphertext, which is worse than 1.\n\n";
        echo "Keep a copy in your password manager. Losing it means re-encrypting\n";
        echo "the secret from the value in the Solution Provider Portal.\n";
        break;

    case 'encrypt':
        if (ipresta_master_key() === null) {
            fwrite(STDERR, "No master key found. Run genkey first, then make the key\n");
            fwrite(STDERR, "readable to this process (export IPRESTA_MASTER_KEY=... or\n");
            fwrite(STDERR, "define IPRESTA_KEY_FILE and require config.php).\n");
            exit(1);
        }
        fwrite(STDERR, "Paste the secret, then press Enter followed by Ctrl-D:\n");
        $plaintext = stream_get_contents(STDIN);
        $plaintext = rtrim((string) $plaintext, "\r\n");
        if ($plaintext === '') {
            fwrite(STDERR, "Nothing read from stdin.\n");
            exit(1);
        }
        try {
            $blob = ipresta_encrypt_secret($plaintext);
        } catch (Exception $e) {
            fwrite(STDERR, $e->getMessage() . "\n");
            exit(1);
        }
        // Prove it round-trips before anyone edits config.php.
        if (ipresta_decrypt_secret($blob) !== $plaintext) {
            fwrite(STDERR, "Round-trip check failed - refusing to emit this blob.\n");
            exit(1);
        }
        echo "\n" . $blob . "\n\n";
        fwrite(STDERR, "Paste that into config.php in place of the plain value.\n");
        break;

    case 'check':
        $configFile = dirname(__FILE__) . '/config.php';
        if (!is_readable($configFile)) {
            fwrite(STDERR, "config.php not found next to this script.\n");
            exit(1);
        }
        require_once $configFile;

        $names = array(
            'IPRESTA_LWA_CLIENT_SECRET',
            'IPRESTA_LWA_SANDBOX_CLIENT_SECRET',
        );
        $plain = 0;
        echo "Master key: " . (ipresta_master_key() === null ? "NOT CONFIGURED" : "present") . "\n\n";
        foreach ($names as $name) {
            if (!defined($name)) {
                printf("  %-38s not defined\n", $name);
                continue;
            }
            $value = (string) constant($name);
            if (ipresta_secret_is_encrypted($value)) {
                try {
                    ipresta_decrypt_secret($value);
                    printf("  %-38s encrypted, decrypts OK\n", $name);
                } catch (Exception $e) {
                    printf("  %-38s encrypted, FAILS: %s\n", $name, $e->getMessage());
                }
            } else {
                printf("  %-38s PLAIN TEXT\n", $name);
                $plain++;
            }
        }
        echo "\n" . ($plain === 0
            ? "All configured secrets are encrypted at rest.\n"
            : $plain . " secret(s) still stored in plain text.\n");
        exit($plain === 0 ? 0 : 1);

    default:
        fwrite(STDERR, "Usage: php secret-tool.php genkey|encrypt|check\n");
        exit(1);
}
