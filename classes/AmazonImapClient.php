<?php
/**
 * Amazon Marketplace Pro
 *
 * NOTICE OF LICENCE
 *
 * This software is commercial and licensed, not sold. The licence is
 * bundled with this package in the file LICENSE.txt. Redistribution,
 * resale and publication of the source are prohibited.
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 */

/*
 * The few IMAP commands the buyer-reply reader needs (RFC 3501), spoken over
 * a PHP stream.
 *
 * PHP's IMAP extension is not bundled with PHP 8.4 and many hosts never
 * enabled it on older versions, so the module does not depend on it. Messages
 * are read with BODY.PEEK, which leaves them unread: only a message filed
 * into Customer Service is marked as read, by markSeen().
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonImapClient
{
    /** Longest literal accepted from the server, in bytes. */
    public static $MAX_LITERAL = 20971520;

    /** @var resource|null */
    private $stream;

    /** @var int */
    private $tag = 0;

    /** @var string */
    private $lastError = '';

    /** @return string the server's or the connection's last error */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @param string $host
     * @param int $port
     * @param bool $ssl implicit TLS, as on port 993
     * @param int $timeout seconds, for the connection and for each read
     *
     * @return bool
     */
    public function connect($host, $port, $ssl, $timeout = 30)
    {
        $context = stream_context_create(['ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ]]);
        $errno = 0;
        $errstr = '';
        $address = ($ssl ? 'ssl://' : 'tcp://') . $host . ':' . (int) $port;
        $stream = @stream_socket_client($address, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$stream) {
            $this->lastError = trim((string) $errstr) !== '' ? trim((string) $errstr) : 'cannot connect to ' . $host . ':' . (int) $port;

            return false;
        }
        stream_set_timeout($stream, (int) $timeout);
        $this->stream = $stream;

        $greeting = $this->readLine();
        if ($greeting === false || (strpos($greeting, '* OK') !== 0 && strpos($greeting, '* PREAUTH') !== 0)) {
            if ($greeting !== false) {
                $this->lastError = trim($greeting);
            }
            $this->close();

            return false;
        }

        return true;
    }

    /**
     * @param string $user
     * @param string $password
     *
     * @return bool
     */
    public function login($user, $password)
    {
        return $this->command(['LOGIN ', $this->astring($user), ' ', $this->astring($password)]) !== false;
    }

    /**
     * @param string $folder e.g. INBOX
     *
     * @return bool
     */
    public function select($folder)
    {
        return $this->command(['SELECT ', $this->astring($folder)]) !== false;
    }

    /** @return int[]|false UIDs of the unread messages, oldest first */
    public function searchUnseen()
    {
        $response = $this->command(['UID SEARCH UNSEEN']);
        if ($response === false) {
            return false;
        }
        $uids = [];
        foreach ($response as $untagged) {
            if (preg_match('/^\* SEARCH\b(.*)$/i', trim($untagged['text']), $m)) {
                foreach (preg_split('/\s+/', trim($m[1])) as $uid) {
                    if (ctype_digit($uid)) {
                        $uids[] = (int) $uid;
                    }
                }
            }
        }
        sort($uids);

        return $uids;
    }

    /**
     * The raw message, without marking it read.
     *
     * @param int $uid
     * @param int $maxBytes the message is cut after this many bytes
     *
     * @return string|false
     */
    public function fetchMessage($uid, $maxBytes)
    {
        $response = $this->command(['UID FETCH ' . (int) $uid . ' (BODY.PEEK[]<0.' . (int) $maxBytes . '>)']);
        if ($response === false) {
            return false;
        }
        foreach ($response as $untagged) {
            if (!preg_match('/^\* \d+ FETCH\b/i', $untagged['text'])) {
                continue;
            }
            if ($untagged['literals']) {
                return $untagged['literals'][0];
            }
            // A server may send a short body as a quoted string.
            if (preg_match('/BODY\[\](?:<\d+>)? "((?:[^"\\\\]|\\\\.)*)"/i', $untagged['text'], $m)) {
                return preg_replace('/\\\\(["\\\\])/', '$1', $m[1]);
            }
        }
        $this->lastError = 'message ' . (int) $uid . ' not found';

        return false;
    }

    /**
     * @param int $uid
     *
     * @return bool
     */
    public function markSeen($uid)
    {
        return $this->command(['UID STORE ' . (int) $uid . ' +FLAGS.SILENT (\\Seen)']) !== false;
    }

    /** Say goodbye and close the connection. Safe to call at any time. */
    public function logout()
    {
        if ($this->stream) {
            $this->command(['LOGOUT']);
        }
        $this->close();
    }

    public function __destruct()
    {
        $this->close();
    }

    private function close()
    {
        if ($this->stream) {
            @fclose($this->stream);
        }
        $this->stream = null;
    }

    /**
     * A string argument: quoted when it is plain ASCII, otherwise sent as a
     * literal, which carries any byte (non-ASCII passwords, for instance).
     *
     * @param string $value
     *
     * @return string|array
     */
    private function astring($value)
    {
        $value = (string) $value;
        if (preg_match('/^[\x20-\x7e]*$/', $value)) {
            return '"' . addcslashes($value, '"\\') . '"';
        }

        return ['literal' => $value];
    }

    /**
     * Send one command and read its response.
     *
     * @param array $parts strings sent as they are, and ['literal' => string]
     *                     sent as IMAP literals
     *
     * @return array|false the untagged responses, each ['text' => string,
     *                     'literals' => string[]], or false when the server
     *                     did not answer OK
     */
    private function command(array $parts)
    {
        if (!$this->stream) {
            if ($this->lastError === '') {
                $this->lastError = 'not connected';
            }

            return false;
        }
        $tag = 'A' . (++$this->tag);
        $line = $tag . ' ';
        foreach ($parts as $part) {
            if (!is_array($part)) {
                $line .= $part;
                continue;
            }
            if (!$this->write($line . '{' . strlen($part['literal']) . "}\r\n")) {
                return false;
            }
            $reply = $this->readLine();
            if ($reply === false || strpos($reply, '+') !== 0) {
                if ($reply !== false) {
                    $this->lastError = trim($reply);
                }

                return false;
            }
            $line = $part['literal'];
        }
        if (!$this->write($line . "\r\n")) {
            return false;
        }

        return $this->readResponse($tag);
    }

    /**
     * @param string $tag
     *
     * @return array|false
     */
    private function readResponse($tag)
    {
        $untagged = [];
        while (true) {
            $line = $this->readLine();
            if ($line === false) {
                return false;
            }
            if (strpos($line, $tag . ' ') === 0) {
                $status = trim(substr($line, strlen($tag) + 1));
                if (stripos($status, 'OK') === 0) {
                    return $untagged;
                }
                $this->lastError = $status;

                return false;
            }
            if (strpos($line, '+') === 0) {
                continue;
            }
            $response = ['text' => '', 'literals' => []];
            // A line ending in {n} is followed by n bytes of data, then the
            // rest of the same response.
            while (preg_match('/\{(\d+)\}\r?\n$/', $line, $m)) {
                $response['text'] .= substr($line, 0, -strlen($m[0]));
                $length = (int) $m[1];
                if ($length > self::$MAX_LITERAL) {
                    $this->lastError = 'server response too large';
                    $this->close();

                    return false;
                }
                $data = $this->readBytes($length);
                if ($data === false) {
                    return false;
                }
                $response['literals'][] = $data;
                $line = $this->readLine();
                if ($line === false) {
                    return false;
                }
            }
            $response['text'] .= rtrim($line, "\r\n");
            $untagged[] = $response;
        }
    }

    /** @return string|false one line, with its line ending */
    private function readLine()
    {
        $line = '';
        while (substr($line, -1) !== "\n") {
            $chunk = fgets($this->stream, 8192);
            if ($chunk === false) {
                return $this->readFailed();
            }
            $line .= $chunk;
        }

        return $line;
    }

    /**
     * @param int $length
     *
     * @return string|false
     */
    private function readBytes($length)
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->stream, min(65536, $length - strlen($data)));
            if ($chunk === false || $chunk === '') {
                return $this->readFailed();
            }
            $data .= $chunk;
        }

        return $data;
    }

    /**
     * @param string $data
     *
     * @return bool
     */
    private function write($data)
    {
        $written = 0;
        while ($written < strlen($data)) {
            $bytes = @fwrite($this->stream, substr($data, $written));
            if (!$bytes) {
                $this->lastError = 'connection lost';
                $this->close();

                return false;
            }
            $written += $bytes;
        }

        return true;
    }

    /** @return false */
    private function readFailed()
    {
        $meta = $this->stream ? stream_get_meta_data($this->stream) : [];
        $this->lastError = !empty($meta['timed_out']) ? 'the mail server did not answer in time' : 'connection lost';
        $this->close();

        return false;
    }
}
