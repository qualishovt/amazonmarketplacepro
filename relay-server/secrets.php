<?php
/**
 * IntelliPresta relay — encryption at rest for configured secrets.
 *
 * Amazon's Data Protection Policy (Encryption at Rest, control 2.4) requires
 * that Amazon-related credentials are not stored in plain text. The only
 * credential this relay holds is the LWA client secret of our own app, so this
 * file exists to keep that value encrypted in config.php.
 *
 * THREAT MODEL - be honest about what this buys.
 *
 * The ciphertext lives in config.php; the key does not. The key comes from an
 * environment variable, or from a file outside the web root. That defends
 * against the ways a config file actually leaks:
 *
 *   - the web server serving .php as plain text after a misconfiguration
 *   - config.php ending up in a backup archive or a git commit
 *   - a directory listing or a path-traversal bug in unrelated code
 *
 * It does NOT defend against an attacker who already has arbitrary file read
 * as the web user on this host - they can read the key file too. Defending
 * against that needs an external KMS, which is out of proportion for a relay
 * that stores no Amazon Information. Say exactly this if asked; overclaiming
 * in a security questionnaire is worse than a modest, accurate answer.
 *
 * CIPHER: AES-256-CBC with encrypt-then-MAC (HMAC-SHA256). Not GCM, because
 * openssl_encrypt only gained GCM support in PHP 7.1 and the relay has to run
 * on whatever the shared host provides. CBC+HMAC is safe when the MAC is
 * verified before decryption, which is what ipresta_decrypt_secret does.
 */

define('IPRESTA_SECRET_PREFIX', 'enc:v1:');

/**
 * The 32-byte master key, or null when none is configured.
 *
 * Looked up in order: the IPRESTA_MASTER_KEY environment variable (base64),
 * then the file named by IPRESTA_KEY_FILE. Environment first, because on hosts
 * that support it the key never touches the disk at all.
 *
 * @return string|null 32 raw bytes
 */
function ipresta_master_key()
{
    static $key = false;
    if ($key !== false) {
        return $key;
    }

    $raw = null;
    $env = getenv('IPRESTA_MASTER_KEY');
    if (is_string($env) && $env !== '') {
        $raw = base64_decode(trim($env), true);
    }

    if ($raw === null && defined('IPRESTA_KEY_FILE') && IPRESTA_KEY_FILE !== '') {
        if (is_readable(IPRESTA_KEY_FILE)) {
            $raw = base64_decode(trim((string) file_get_contents(IPRESTA_KEY_FILE)), true);
        }
    }

    // A key of the wrong length is a configuration error, not something to
    // pad or truncate around.
    $key = (is_string($raw) && strlen($raw) === 32) ? $raw : null;

    return $key;
}

/**
 * Separate keys for confidentiality and authenticity, from the one master key.
 *
 * @param string $master 32 raw bytes
 * @return array array($encKey, $macKey)
 */
function ipresta_derive_keys($master)
{
    return array(
        hash_hmac('sha256', 'ipresta-enc-v1', $master, true),
        hash_hmac('sha256', 'ipresta-mac-v1', $master, true),
    );
}

/**
 * Encrypt a value for storage in config.php.
 *
 * @param string $plaintext
 * @return string the "enc:v1:..." blob
 * @throws Exception when no key is configured
 */
function ipresta_encrypt_secret($plaintext)
{
    $master = ipresta_master_key();
    if ($master === null) {
        throw new Exception('No master key: set IPRESTA_MASTER_KEY or IPRESTA_KEY_FILE.');
    }

    list($encKey, $macKey) = ipresta_derive_keys($master);

    $iv = openssl_random_pseudo_bytes(16, $strong);
    if ($iv === false || !$strong) {
        throw new Exception('No cryptographically strong random source available.');
    }

    $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $encKey, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext === false) {
        throw new Exception('Encryption failed.');
    }

    // Encrypt-then-MAC, over IV and ciphertext both.
    $mac = hash_hmac('sha256', $iv . $ciphertext, $macKey, true);

    return IPRESTA_SECRET_PREFIX . base64_encode($iv . $mac . $ciphertext);
}

/**
 * Decrypt a stored value.
 *
 * Values without the "enc:v1:" prefix are returned unchanged, so a relay can
 * be upgraded without a flag-day: deploy this file, verify, then encrypt the
 * secret. Plain values are meant to be temporary - ipresta_secret_is_encrypted
 * lets the health check report on any that are left.
 *
 * @param string $value
 * @return string
 * @throws Exception when the value is encrypted but cannot be recovered
 */
function ipresta_decrypt_secret($value)
{
    if (strpos($value, IPRESTA_SECRET_PREFIX) !== 0) {
        return $value;
    }

    $master = ipresta_master_key();
    if ($master === null) {
        throw new Exception('Secret is encrypted but no master key is configured.');
    }

    $blob = base64_decode(substr($value, strlen(IPRESTA_SECRET_PREFIX)), true);
    if ($blob === false || strlen($blob) <= 48) {
        throw new Exception('Encrypted secret is malformed.');
    }

    $iv = substr($blob, 0, 16);
    $mac = substr($blob, 16, 32);
    $ciphertext = substr($blob, 48);

    list($encKey, $macKey) = ipresta_derive_keys($master);

    // Verify before decrypting, in constant time.
    $expected = hash_hmac('sha256', $iv . $ciphertext, $macKey, true);
    if (!ipresta_hash_equals($expected, $mac)) {
        throw new Exception('Encrypted secret failed authentication - wrong key or tampering.');
    }

    $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $encKey, OPENSSL_RAW_DATA, $iv);
    if ($plaintext === false) {
        throw new Exception('Decryption failed.');
    }

    return $plaintext;
}

/**
 * @param string $value
 * @return bool whether the value is stored encrypted
 */
function ipresta_secret_is_encrypted($value)
{
    return strpos($value, IPRESTA_SECRET_PREFIX) === 0;
}

/**
 * Read a configured secret by constant name, decrypting when needed.
 *
 * @param string $constant e.g. 'IPRESTA_LWA_CLIENT_SECRET'
 * @return string empty string when the constant is not defined
 */
function ipresta_secret($constant)
{
    if (!defined($constant)) {
        return '';
    }

    return ipresta_decrypt_secret((string) constant($constant));
}

/** hash_equals arrived in PHP 5.6; the relay may run on older. */
function ipresta_hash_equals($known, $given)
{
    if (function_exists('hash_equals')) {
        return hash_equals($known, $given);
    }

    if (strlen($known) !== strlen($given)) {
        return false;
    }

    $diff = 0;
    for ($i = 0, $n = strlen($known); $i < $n; $i++) {
        $diff |= ord($known[$i]) ^ ord($given[$i]);
    }

    return $diff === 0;
}
