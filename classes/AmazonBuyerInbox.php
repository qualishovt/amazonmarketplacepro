<?php
/**
 * Amazon Marketplace Pro
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 *
 * Inbound buyer messages.
 *
 * SP-API's Messaging API is send-only: there is no endpoint that returns what
 * a buyer wrote back. Amazon delivers replies by e-mail to the seller's
 * registered address instead, so reading them means reading a mailbox. That
 * is what this does — it connects over IMAP, finds messages carrying an
 * Amazon order id, and files them into PrestaShop's own Customer Service so
 * the merchant answers buyers where they answer everyone else.
 *
 * Requires the PHP IMAP extension. Without it the feature reports itself
 * unavailable rather than failing at run time.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

/**
 * Each shop reads its own mailbox (settings per shop) and files only the
 * messages about orders it imported. Two shops may share one mailbox: a
 * message for the other shop's order stays unread until that shop's run
 * files it.
 */
class AmazonBuyerInbox
{
    /** Amazon order ids look like 123-1234567-1234567. */
    const ORDER_ID_PATTERN = '/\b(\d{3}-\d{7}-\d{7})\b/';

    private $lastError = null;
    private $notices = array();

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getNotices()
    {
        return $this->notices;
    }

    /** @return bool The PHP IMAP extension is compiled in */
    public static function isAvailable()
    {
        return function_exists('imap_open');
    }

    /**
     * @param int|null $idShop default: the current shop
     * @return bool
     */
    public static function isEnabled($idShop = null)
    {
        return (bool) AmzproShop::get('AMZPRO_IMAP_ENABLED', $idShop);
    }

    /**
     * The IMAP mailbox string, e.g. {imap.gmail.com:993/imap/ssl}INBOX
     *
     * @param int|null $idShop default: the current shop
     * @return string '' when the settings are incomplete
     */
    public static function mailboxString($idShop = null)
    {
        $host = trim((string) AmzproShop::get('AMZPRO_IMAP_HOST', $idShop));
        if ($host === '') {
            return '';
        }
        $port = (int) AmzproShop::get('AMZPRO_IMAP_PORT', $idShop);
        if (!$port) {
            $port = 993;
        }
        $folder = trim((string) AmzproShop::get('AMZPRO_IMAP_FOLDER', $idShop));
        if ($folder === '') {
            $folder = 'INBOX';
        }
        $flags = AmzproShop::get('AMZPRO_IMAP_SSL', $idShop) ? '/imap/ssl' : '/imap/notls';

        return '{' . $host . ':' . $port . $flags . '}' . $folder;
    }

    /**
     * Read unseen mail and file anything that names an Amazon order.
     *
     * @param int $limit Messages to process in one run
     * @return array|false Summary, or false when the mailbox cannot be opened
     */
    public function fetchNewMessages($limit = 50)
    {
        $summary = array('scanned' => 0, 'matched' => 0, 'filed' => 0, 'skipped' => 0);

        if (!self::isAvailable()) {
            $this->lastError = AmazonI18n::get()->l('The PHP IMAP extension is not installed on this server, so buyer replies cannot be read. Ask your host to enable ext-imap.', 'amazonbuyerinbox');
            return false;
        }
        if (!self::isEnabled()) {
            $this->lastError = AmazonI18n::get()->l('Inbound buyer messages are disabled in the module settings.', 'amazonbuyerinbox');
            return false;
        }

        $mailbox = self::mailboxString();
        $user = trim((string) AmzproShop::get('AMZPRO_IMAP_USER'));
        $password = (string) AmzproShop::get('AMZPRO_IMAP_PASSWORD');
        if ($mailbox === '' || $user === '') {
            $this->lastError = AmazonI18n::get()->l('The mailbox host and user must be configured first.', 'amazonbuyerinbox');
            return false;
        }

        $connection = @imap_open($mailbox, $user, $password, 0, 1);
        if ($connection === false) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('Could not open the mailbox: %s', 'amazonbuyerinbox'),
                implode('; ', (array) imap_errors())
            );
            return false;
        }

        $ids = @imap_search($connection, 'UNSEEN');
        if (!is_array($ids)) {
            $ids = array(); // an empty result is not an error
        }
        $ids = array_slice($ids, 0, (int) $limit);

        foreach ($ids as $messageNumber) {
            $summary['scanned']++;
            $result = $this->processMessage($connection, $messageNumber);
            if ($result === 'filed') {
                $summary['matched']++;
                $summary['filed']++;
            } elseif ($result === 'matched') {
                $summary['matched']++;
            } else {
                $summary['skipped']++;
            }
        }

        @imap_close($connection);

        return $summary;
    }

    /**
     * @return string filed | matched | skipped
     */
    private function processMessage($connection, $messageNumber)
    {
        $header = @imap_headerinfo($connection, $messageNumber);
        $subject = ($header && isset($header->subject)) ? $this->decode($header->subject) : '';
        $from = '';
        if ($header && isset($header->from[0])) {
            $from = $header->from[0]->mailbox . '@' . $header->from[0]->host;
        }
        $body = $this->messageBody($connection, $messageNumber);

        // The order id may sit in either the subject or the body.
        $amazonOrderId = '';
        if (preg_match(self::ORDER_ID_PATTERN, $subject . ' ' . $body, $m)) {
            $amazonOrderId = $m[1];
        }
        if ($amazonOrderId === '') {
            // Not an Amazon buyer message; leave it unread for the human.
            return 'skipped';
        }

        $staged = Db::getInstance()->getRow(
            'SELECT `id_order`, `buyer_email`, `buyer_name`, `id_shop`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `amazon_order_id` = \'' . pSQL($amazonOrderId) . '\'
               AND ' . AmzproShop::sqlWhere()
        );
        if (!$staged) {
            $this->notices[] = sprintf(
                AmazonI18n::get()->l('%s: message received for an order this shop has not imported.', 'amazonbuyerinbox'),
                $amazonOrderId
            );
            return 'skipped';
        }

        $filed = $this->fileIntoCustomerService(
            $amazonOrderId,
            (int) $staged['id_order'],
            $from !== '' ? $from : $staged['buyer_email'],
            $subject,
            $body,
            (int) $staged['id_shop']
        );

        if ($filed) {
            // Only mark it read once it is safely in PrestaShop.
            @imap_setflag_full($connection, (string) $messageNumber, '\\Seen');
            return 'filed';
        }

        return 'matched';
    }

    /**
     * Create or extend the Customer Service thread for an Amazon order, in
     * the shop the order belongs to, so it shows up in that shop's Customer
     * Service.
     *
     * @param int $idShop the staged order's shop
     */
    private function fileIntoCustomerService($amazonOrderId, $idOrder, $email, $subject, $body, $idShop)
    {
        $idShop = (int) $idShop;
        $idCustomer = 0;
        if ($idOrder > 0) {
            $order = Db::getInstance()->getRow(
                'SELECT `id_customer`, `id_shop` FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_order` = ' . $idOrder
            );
            if ($order) {
                $idCustomer = (int) $order['id_customer'];
                if ((int) $order['id_shop']) {
                    $idShop = (int) $order['id_shop'];
                }
            }
        }
        if (!$idShop) {
            $idShop = AmzproShop::actingId();
        }
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT', null, AmzproShop::groupId($idShop), $idShop);

        // One thread per Amazon order keeps a conversation together.
        $idThread = (int) Db::getInstance()->getValue(
            'SELECT `id_customer_thread` FROM `' . _DB_PREFIX_ . 'customer_thread`
             WHERE `id_order` = ' . $idOrder . ' AND `id_shop` = ' . $idShop . '
               AND ' . ($idOrder > 0 ? '1' : '0')
        );

        if (!$idThread) {
            $thread = new CustomerThread();
            $thread->id_shop = $idShop;
            $thread->id_lang = $idLang;
            $thread->id_contact = $this->shopContactId($idShop);
            $thread->id_customer = $idCustomer;
            $thread->id_order = $idOrder;
            $thread->email = Validate::isEmail($email) ? $email : 'buyer@marketplace.amazon';
            $thread->status = 'open';
            $thread->token = Tools::passwdGen(12);
            if (!$thread->add()) {
                $this->notices[] = sprintf(
                    AmazonI18n::get()->l('%s: could not open a customer service thread.', 'amazonbuyerinbox'),
                    $amazonOrderId
                );
                return false;
            }
            $idThread = (int) $thread->id;
        }

        $text = 'Amazon order ' . $amazonOrderId . "\n"
            . ($subject !== '' ? 'Subject: ' . $subject . "\n" : '')
            . "\n" . $body;

        $message = new CustomerMessage();
        $message->id_customer_thread = $idThread;
        $message->id_employee = 0;
        $message->message = Tools::substr(strip_tags($text), 0, 65000);
        $message->private = 0;
        $message->read = 0;

        return (bool) $message->add();
    }

    /**
     * The contact a new thread is filed under when several shops run: one of
     * the order's shop's own contacts, the customer service one first, so the
     * thread never points at a contact of another shop. A single shop keeps
     * filing threads without a contact, as it always has.
     *
     * @param int $idShop
     * @return int 0 when the shop has no contact
     */
    private function shopContactId($idShop)
    {
        if (!AmzproShop::isMultistore()) {
            return 0;
        }

        return (int) Db::getInstance()->getValue(
            'SELECT c.`id_contact`
             FROM `' . _DB_PREFIX_ . 'contact` c
             INNER JOIN `' . _DB_PREFIX_ . 'contact_shop` cs
                ON (cs.`id_contact` = c.`id_contact` AND cs.`id_shop` = ' . (int) $idShop . ')
             ORDER BY c.`customer_service` DESC, c.`position` ASC, c.`id_contact` ASC'
        );
    }

    /** Plain-text body of a message, preferring text/plain over HTML. */
    private function messageBody($connection, $messageNumber)
    {
        $structure = @imap_fetchstructure($connection, $messageNumber);
        if (!$structure) {
            return '';
        }

        if (empty($structure->parts)) {
            return $this->decodePart(@imap_body($connection, $messageNumber), $structure->encoding);
        }

        $plain = '';
        $html = '';
        foreach ($structure->parts as $index => $part) {
            $section = (string) ($index + 1);
            $content = @imap_fetchbody($connection, $messageNumber, $section);
            if ($content === false) {
                continue;
            }
            $content = $this->decodePart($content, isset($part->encoding) ? $part->encoding : 0);
            $subtype = isset($part->subtype) ? Tools::strtoupper($part->subtype) : '';
            if ($subtype === 'PLAIN' && $plain === '') {
                $plain = $content;
            } elseif ($subtype === 'HTML' && $html === '') {
                $html = $content;
            }
        }

        if ($plain !== '') {
            return $plain;
        }

        return ($html !== '') ? strip_tags($html) : '';
    }

    private function decodePart($content, $encoding)
    {
        if ((int) $encoding === 3) {          // base64
            return (string) base64_decode($content);
        }
        if ((int) $encoding === 4) {          // quoted-printable
            return quoted_printable_decode($content);
        }

        return (string) $content;
    }

    private function decode($value)
    {
        $decoded = '';
        $parts = @imap_mime_header_decode($value);
        if (is_array($parts)) {
            foreach ($parts as $part) {
                $decoded .= $part->text;
            }
            return $decoded;
        }

        return (string) $value;
    }
}
