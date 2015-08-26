<?php 
(new sessionShare(['othersite.example.com'], [
    'cookie' => [
        'enabled' => true, 
        'allowed' => ['login_token']
    ],
    'session' => [
        'enabled' => true, 
        'allowed' => [
            'user' => ['user_login']
        ]
    ]
]))->output();

class sessionShare {
    
    public $output = array();
    private $ref;
    private $opts;
    private $allowed;
    private $type;
    private $fetch;
    
    public function __construct($allowed, $opts) {
        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->allowed  = $allowed;
            $this->opts     = $opts;
            $this->ref      = $_SERVER['HTTP_REFERER'];
            $this->init();
        }
        return $this;
    }
    
    public function init() {
        if (isset($this->ref)) {
            $this->ref = parse_url($this->ref)['host'];

            if (in_array($this->ref, $this->allowed)) {
                if (isset($_GET['type']) && in_array($_GET['type'], array_keys($this->opts))) {
                    if ($this->opts[$_GET['type']]['enabled'] == true) {
                        $this->type = $_GET['type'];
                        
                        $this->loadSession();
                    }
                }
            }
        }
        
        return $this;
    }
    
    public function loadSession() {
        if (isset($_GET['fetch'])) {
            $this->fetch = $this->type == 'cookie' ? $_COOKIE : $_SESSION;
            
            foreach($_GET['fetch'] as $key => $value) {
                if (is_array($value)) {
                    if (!is_array($this->output[$key]))
                        $this->output[$key] = array();

                    foreach($value as $val) {
                        if (isset($this->opts[$this->type]['allowed'][$key]) && in_array($val, $this->opts[$this->type]['allowed'][$key]))
                            if (isset($this->fetch[$key][$val]))
                                $this->output[$key][$val] = $this->fetch[$key][$val];
                    }
                } else {
                    if (isset($this->opts[$this->type]['allowed'][$key]) && !is_array($this->opts[$this->type]['allowed'][$key]))
                        $this->output[$key] = $this->fetch[$key];
                }
            }
        }
    }
    
    public function output() {
        header('content-type: application/json; charset=utf-8');
        
        echo $_GET['callback'] . '(' . json_encode($this->output). ')';
    }
}