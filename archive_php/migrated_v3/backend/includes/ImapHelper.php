<?php
class ImapHelper {
    private $hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
    private $username = 'nexorasystems25@gmail.com'; // Replace with your Gmail
    private $password = 'wahh orvr inmr cqjk';    // Replace with your 16-character App Password
    private $inbox;

    public function __construct() {
        // Suppress warnings as imap_open throws warnings on failure
        $this->inbox = @imap_open($this->hostname, $this->username, $this->password);
    }

    public function isConnected() {
        return $this->inbox !== false;
    }

    public function getError() {
        return imap_last_error();
    }

    public function getEmails($limit = 20, $page = 1) {
        if (!$this->isConnected()) {
            return [];
        }

        $emails = [];
        // Search for ALL emails (or could use 'UNSEEN' to only get unread)
        $emails_array = imap_search($this->inbox, 'ALL');

        if ($emails_array) {
            // Sort to get the newest first
            rsort($emails_array);

            // Pagination logic
            $offset = ($page - 1) * $limit;
            $emails_to_fetch = array_slice($emails_array, $offset, $limit);

            foreach ($emails_to_fetch as $email_number) {
                $overview = imap_fetch_overview($this->inbox, $email_number, 0);
                
                // Get message body
                $message = imap_fetchbody($this->inbox, $email_number, 1);
                
                // Some decoding might be necessary depending on encoding
                $structure = imap_fetchstructure($this->inbox, $email_number);
                if (isset($structure->parts) && is_array($structure->parts) && isset($structure->parts[0])) {
                    $part = $structure->parts[0];
                    if ($part->encoding == 3) {
                        $message = imap_base64($message);
                    } elseif ($part->encoding == 4) {
                        $message = imap_qprint($message);
                    }
                }

                $emails[] = [
                    'id' => $email_number,
                    'subject' => isset($overview[0]->subject) ? $this->decodeSubject($overview[0]->subject) : '(No Subject)',
                    'from' => isset($overview[0]->from) ? $overview[0]->from : 'Unknown',
                    'date' => isset($overview[0]->date) ? date("Y-m-d H:i:s", strtotime($overview[0]->date)) : '',
                    'seen' => isset($overview[0]->seen) ? $overview[0]->seen : 0,
                    'body' => $message
                ];
            }
        }

        return $emails;
    }

    private function decodeSubject($subject) {
        $decodedElements = imap_mime_header_decode($subject);
        $decodedSubject = '';
        foreach ($decodedElements as $element) {
            $decodedSubject .= $element->text;
        }
        return $decodedSubject;
    }

    public function getEmailCount() {
        if (!$this->isConnected()) {
            return 0;
        }
        return imap_num_msg($this->inbox);
    }

    public function markAsRead($email_number) {
        if ($this->isConnected()) {
            imap_setflag_full($this->inbox, $email_number, "\\Seen");
        }
    }

    public function deleteEmail($email_number) {
        if ($this->isConnected()) {
            imap_delete($this->inbox, $email_number);
            imap_expunge($this->inbox);
        }
    }

    public function close() {
        if ($this->isConnected()) {
            imap_close($this->inbox);
        }
    }
}
?>