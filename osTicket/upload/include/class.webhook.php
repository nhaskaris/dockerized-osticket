<?php
class Webhook {
    var $id;
    var $ht;

    function __construct($id) {
        $this->id = 0;
        $this->load($id);
    }

    function load($id=0) {
        if (!$id && !($id=$this->id)) return;

        $sql='SELECT * FROM '.TABLE_PREFIX.'webhook WHERE id='.db_input($id);
        if (($res=db_query($sql)) && db_num_rows($res))
            $this->ht = db_fetch_array($res);

        $this->id = $this->ht['id'];
        return $this->id;
    }

    function getId() { return $this->id; }
    function getName() { return $this->ht['name']; }
    function getUrl() { return $this->ht['url']; }
    function getStatus() { return $this->ht['status']; }
    function getHeaders() { return $this->ht['headers']; }
    function getTimeout() { return $this->ht['timeout']; }
    
    function getEvents() {
        return [
            'ticket.created' => $this->ht['event_new_ticket'],
            'ticket.closed'  => $this->ht['event_ticket_closed'],
            'staff.reply'    => $this->ht['event_staff_reply'],
            'client.reply'   => $this->ht['event_client_reply'],
        ];
    }

    function update($vars, &$errors) {
        if (!$vars['name']) $errors['name'] = 'Name is required';
        if (!$vars['url']) $errors['url'] = 'URL is required';
        
        if ($errors) return false;

        $sql = 'UPDATE '.TABLE_PREFIX.'webhook SET updated=NOW() '
             .', status='.db_input(isset($vars['status']) ? 1 : 0)
             .', name='.db_input($vars['name'])
             .', url='.db_input($vars['url'])
             .', headers='.db_input($vars['headers'])
             .', timeout='.db_input($vars['timeout'])
             .', event_new_ticket='.db_input(isset($vars['event_new_ticket']) ? 1 : 0)
             .', event_ticket_closed='.db_input(isset($vars['event_ticket_closed']) ? 1 : 0)
             .', event_staff_reply='.db_input(isset($vars['event_staff_reply']) ? 1 : 0)
             .', event_client_reply='.db_input(isset($vars['event_client_reply']) ? 1 : 0)
             .' WHERE id='.db_input($this->id);

        return db_query($sql);
    }

    function delete() {
        $sql = 'DELETE FROM '.TABLE_PREFIX.'webhook WHERE id='.db_input($this->id);
        return db_query($sql);
    }

    static function create($vars, &$errors) {
        if (!$vars['name']) $errors['name'] = 'Name is required';
        if (!$vars['url']) $errors['url'] = 'URL is required';

        if ($errors) return false;

        $sql = 'INSERT INTO '.TABLE_PREFIX.'webhook SET created=NOW(), updated=NOW() '
             .', status='.db_input(isset($vars['status']) ? 1 : 0)
             .', name='.db_input($vars['name'])
             .', url='.db_input($vars['url'])
             .', headers='.db_input($vars['headers'])
             .', timeout='.db_input($vars['timeout'])
             .', event_new_ticket='.db_input(isset($vars['event_new_ticket']) ? 1 : 0)
             .', event_ticket_closed='.db_input(isset($vars['event_ticket_closed']) ? 1 : 0)
             .', event_staff_reply='.db_input(isset($vars['event_staff_reply']) ? 1 : 0)
             .', event_client_reply='.db_input(isset($vars['event_client_reply']) ? 1 : 0);

        if (db_query($sql) && ($id=db_insert_id()))
            return new Webhook($id);

        return null;
    }

    static function lookup($id) {
        return ($id && is_numeric($id) && ($w=new Webhook($id)) && $w->getId()) ? $w : null;
    }

    // --- The Sender Logic ---
    static function send($event, $ticket, $entry=null) {
        
        $map = [
            'ticket.created' => 'event_new_ticket',
            'ticket.closed'  => 'event_ticket_closed',
            'staff.reply'    => 'event_staff_reply',
            'client.reply'   => 'event_client_reply'
        ];

        if (!isset($map[$event])) return;
        $col = $map[$event];

        // Find active webhooks for this event
        $sql = "SELECT * FROM ".TABLE_PREFIX."webhook WHERE status=1 AND `$col`=1";
        $res = db_query($sql);
        
        if (!$res || db_num_rows($res) == 0) return;

        // Prepare Payload
        $payload = [
            'event'     => $event,
            'ticket_id' => $ticket->getId(),
            'number'    => $ticket->getNumber(),
            'subject'   => (string)$ticket->getSubject(),
            'status'    => (string)$ticket->getStatus(),
            'priority'  => $ticket->getPriority() ? (string)$ticket->getPriority() : 'Normal',
            'source'    => $ticket->getSource(),
            'link' => ($cfg = $GLOBALS['cfg']) ? $cfg->getBaseUrl() . 'scp/tickets.php?id=' . $ticket->getId() : '',
            'timestamp' => time()
        ];

        if ($entry && is_object($entry)) {
            $body = $entry->getBody();
            $payload['message'] = (is_object($body)) ? $body->getClean() : (string)$body;
            $payload['poster']  = (string)$entry->getPoster();
            $payload['poster_type'] = ($entry->getStaffId() > 0) ? 'Staff' : 'User';
        }

        while ($row = db_fetch_array($res)) {
            self::fire($row, $payload);
        }
    }

    static function fire($config, $payload) {
        $headers = ['Content-Type: application/json', 'User-Agent: osTicket-Webhook/1.0'];
        
        if (!empty($config['headers'])) {
            foreach (explode("\n", $config['headers']) as $h) {
                if (trim($h)) $headers[] = trim($h);
            }
        }

        $ch = curl_init($config['url']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)($config['timeout'] ?: 5));
        
        curl_exec($ch);
        curl_close($ch);
    }
}
class WebhookManager { static function send($e, $t, $m=null) { Webhook::send($e,$t,$m); } }
?>