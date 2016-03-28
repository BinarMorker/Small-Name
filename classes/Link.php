<?php 

class Link {
    
    public $id;
    public $url;
    public $expiration;
    public $maxuses;
    public $creationtime;
    public $expirationtime;
    public $redirections;
    public $valid;
    public $status;
    public $urlcode;
    public $authorid;
    
    private function __construct($id, $url, $expiration, $maxuses, $creationtime, $redirections, $authorid, $length) {
        $this->id = PseudoCrypt::hash($id, $length);
        $this->url = $url;
        $this->expiration = self::expiration_times()[$expiration];
        $this->maxuses = self::max_uses()[$maxuses];
        $this->creationtime = date("d-m-Y H:i:s", strtotime($creationtime));
        $this->expirationtime = ($expiration > 0) ? (date("d-m-Y H:i:s", strtotime('+ ' . $expiration . ' hours', strtotime($creationtime))) . ' (' . $this->expiration . ')') : 'Never';
        $this->redirections = $redirections;
        $this->urlcode = self::get_status($url);
        $this->authorid = $authorid;
        $this->length = $length;
        
        if (in_array(self::get_status($url), self::http_errors())) {
            $this->valid = false;
            $this->status = 'Invalid';
        } elseif (
            (
                $expiration > 0 
                && 
                time('now') >= strtotime('+ ' . $expiration . ' hours', strtotime($creationtime))
            ) 
            || 
            (
                $maxuses > 0 
                && 
                $redirections >= $maxuses
            )
        ) {
            $this->valid = false;
            $this->status = 'Expired';
        } else {
            $this->valid = true;
            $this->status = 'Valid';
        }
    }
    
    public static function get_status($url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $code;
    }
    
    private static function http_errors() {
        return array(
            "0",
            "404",
            "400",
            "405",
            "410",
            "500",
            "501",
            "502",
            "503"
        );
    }
    
    public static function get_for_user($account_id) {
        $result = Database::select('links', array('google_id' => $account_id));
        $links = array();
        
        foreach ($result as $link) {
            $links[] = new static(
                $link['id'],
                $link['url'],
                $link['expiration'],
                $link['maxuses'],
                $link['creationtime'],
                $link['redirections'],
                $link['google_id'],
                $link['length']
            );
        }
        
        return $links;
    }
    
    public static function get($id) {
        $result = Database::select('links', array('id' => PseudoCrypt::unhash($id, strlen($id))))[0];
        $link = new static(
            $result['id'],
            $result['url'],
            $result['expiration'],
            $result['maxuses'],
            $result['creationtime'],
            $result['redirections'],
            $result['google_id'],
            $result['length']
        );
        return $link;
    }
    
    public static function create_for_user($url, $expiration, $maxuses, $account_id, $length) {
        if (in_array(self::get_status($url), self::http_errors())) {
            return -1;
        }
        
        $id = Database::insert('links', array('url' => $url, 'expiration' => $expiration, 'maxuses' => $maxuses, 'google_id' => $account_id, 'length' => $length));
        return PseudoCrypt::hash($id, $length);
    }
    
    public static function create($url, $expiration, $maxuses) {
        if (in_array(self::get_status($url), self::http_errors())) {
            return -1;
        }
        
        $id = Database::insert('links', array('url' => $url, 'expiration' => $expiration, 'maxuses' => $maxuses));
        return PseudoCrypt::hash($id);
    }
    
    public function add_redirection() {
        Database::update('links', array('redirections' => 'redirections + 1'), array('id' => PseudoCrypt::unhash($this->id, $this->length)));
    }
    
    public function save() {
        throw new Exception('Not yet implemented');
    }
    
    public function delete() {
        Database::delete('links', array('id' => PseudoCrypt::unhash($this->id, $this->length)));
    }
    
    private static function expiration_times() {
        return array(
            "24" => "24 hours",
            "48" => "48 hours",
            "168" => "1 week",
            "336" => "2 weeks",
            "-1" => "Never"
        );
    }
    
    private static function max_uses() {
        return array(
            "1" => "1",
            "5" => "5",
            "10" => "10",
            "100" => "100",
            "-1" => "&infin;"
        );
    }
    
}