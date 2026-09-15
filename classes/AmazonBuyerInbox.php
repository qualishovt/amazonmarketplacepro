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
 * The mailbox is read by AmazonImapClient, without PHP's IMAP extension.
 * An SSL mailbox needs PHP's OpenSSL extension; without it the feature
 * reports itself unavailable rather than failing at run time.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';
require_once dirname(__FILE__) . '/AmazonImapClient.php';
require_once dirname(__FILE__) . '/AmazonMailMessage.php';

/**
 * Each shop reads its own mailbox (settings per shop) and files only the
 * messages about orders it imported. Two shops may share one mailbox: a
 * message for the other shop's order stays unread until that shop's run
 * files it.
 */
class AmazonBuyerInbox
{
    /** Amazon order ids look like 123-1234567-1234567. */
    public static $ORDER_ID_PATTERN = '/\b(\d{3}-\d{7}-\d{7})\b/';

    /** Bytes read of each message: the text comes first, attachments after. */
    public static $MAX_MESSAGE_BYTES = 1048576;

    private $lastError;
    private $notices = [];

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getNotices()
    {
        return $this->notices;
    }

    /** @return bool PHP can open a network connection, with TLS for an SSL mailbox */
    public static function isAvailable()
    {
        return function_exists('stream_socket_client') && extension_loaded('openssl');
    }

    /**
     * @param int|null $idShop default: the current shop
     *
     * @return bool
     */
    public static function isEnabled($idShop = null)
    {
        return (bool) AmzproShop::get('AMZPRO_IMAP_ENABLED', $idShop);
    }

    /**
     * Read unseen mail and file anything that names an Amazon order.
     *
     * @param int $limit Messages to process in one run
     *
     * @return array|false Summary, or false when the mailbox cannot be opened
     */
    public function fetchNewMessages($limit = 50)
    {
        $summary = ['scanned' => 0, 'matched' => 0, 'filed' => 0, 'skipped' => 0];

        if (!self::isAvailable()) {
            $this->lastError = AmazonI18n::get()->l('PHP\'s OpenSSL extension is not enabled on this server, so buyer replies cannot be read. Ask your host to enable it.', 'amazonbuyerinbox');

            return false;
        }
        if (!self::isEnabled()) {
            $this->lastError = AmazonI18n::get()->l('Inbound buyer messages are disabled in the module settings.', 'amazonbuyerinbox');

            return false;
        }

        $host = trim((string) AmzproShop::get('AMZPRO_IMAP_HOST'));
        $user = trim((string) AmzproShop::get('AMZPRO_IMAP_USER'));
        $password = (string) AmzproShop::get('AMZPRO_IMAP_PASSWORD');
        if ($host === '' || $user === '') {
            $this->lastError = AmazonI18n::get()->l('The mailbox host and user must be configured first.', 'amazonbuyerinbox');

            return false;
        }
        $port = (int) AmzproShop::get('AMZPRO_IMAP_PORT');
        $folder = trim((string) AmzproShop::get('AMZPRO_IMAP_FOLDER'));

        $imap = new AmazonImapClient();
        $opened = $imap->connect($host, $port ? $port : 993, (bool) AmzproShop::get('AMZPRO_IMAP_SSL'))
            && $imap->login($user, $password)
            && $imap->select($folder !== '' ? $folder : 'INBOX');
        $uids = $opened ? $imap->searchUnseen() : false;
        if ($uids === false) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('Could not open the mailbox: %s', 'amazonbuyerinbox'),
                $imap->getLastError()
            );
            $imap->logout();

            return false;
        }
        $uids = array_slice($uids, 0, (int) $limit);

        foreach ($uids as $uid) {
            ++$summary['scanned'];
            $result = $this->processMessage($imap, $uid);
            if ($result === 'filed') {
                ++$summary['matched'];
                ++$summary['filed'];
            } elseif ($result === 'matched') {
                ++$summary['matched'];
            } else {
                ++$summary['skipped'];
            }
        }

        $imap->logout();

        return $summary;
    }

    /**
     * @param AmazonImapClient $imap
     * @param int $uid
     *
     * @return string filed | matched | skipped
     */
    private function processMessage($imap, $uid)
    {
        $raw = $imap->fetchMessage($uid, self::$MAX_MESSAGE_BYTES);
        if ($raw === false) {
            return 'skipped';
        }
        $mail = new AmazonMailMessage($raw);
        $subject = $mail->subject();
        $from = $mail->fromAddress();
        $body = $mail->text();

        // The order id may sit in either the subject or the body.
        $amazonOrderId = '';
        if (preg_match(self::$ORDER_ID_PATTERN, $subject . ' ' . $body, $m)) {
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
            $imap->markSeen($uid);

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
        $message->private = false;
        $message->read = false;

        return (bool) $message->add();
    }

    /**
     * The contact a new thread is filed under when several shops run: one of
     * the order's shop's own contacts, the customer service one first, so the
     * thread never points at a contact of another shop. A single shop keeps
     * filing threads without a contact, as it always has.
     *
     * @param int $idShop
     *
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
}
