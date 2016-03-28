<?php 

/**
 * Main applicationm class
 */
class Application {
    
    private $data = array();
    const DEFAULT_EXPIRATION = 48;
    const DEFAULT_MAXUSES = -1;
    const DEFAULT_LENGTH = 5;
    
    public function run($request) {
        $this->authenticate($request);
        $this->data['error'] = $this->request_pop($request, 'error');
        
        if (!isset($request['query'])) {
            $this->index();
        } else {
            $query = explode('/', $this->request_pop($request, 'query'), 3);
            
            if (count($query) > 0) {
                switch ($query[0]) {
                    case 'link':
                        if (count($query) > 1 && $query[1]) {
                            $link = Link::get($query[1]);
                            $this->data['newlink'] = false;
                            
                            if (count($query) > 2 && $query[2]) {
                                switch ($query[2]) {
                                    // TODO: Allow modification
                                    /*case 'modify':
                                        $this->modify($link);
                                        exit;
                                        break;*/
                                    case 'delete':
                                        $link->delete();
                                        $_SESSION['error'] = "The url was deleted";
                                        $this->redirect('/');
                                        break;
                                    default: 
                                        break;
                                }
                            } else {
                                $this->data['newlink'] = $this->request_pop($request, 'newlink');
                            }
                            
                            $this->link($link);
                        } else {
                            $this->redirect('/');
                        }
                        
                        break;
                    case 'shorten':
                        if (count($query) > 2 && $query[1] && $query[2]) {
                            $suffix = "";
                            
                            if (count($request) > 0) {
                                $suffix = '?' . http_build_query($request);
                            }
                            
                            $url = $query[1] . '//' . $query[2] . $suffix;
                            $expiration = self::DEFAULT_EXPIRATION;
                            $maxuses = self::DEFAULT_MAXUSES;
                            $length = self::DEFAULT_LENGTH;
                        } elseif (isset($request['shorten'])) {
                            $url = $request['shorten'];
                            
                            if (isset($request['expiration'])) {
                                $expiration = $request['expiration'];
                            } else {
                                $expiration = self::DEFAULT_EXPIRATION;
                            }
                            
                            if (isset($request['maxuses'])) {
                                $maxuses = $request['maxuses'];
                            } else {
                                $maxuses = self::DEFAULT_MAXUSES;
                            }
                            
                            if (isset($request['length'])) {
                                $length = $request['length'];
                            } else {
                                $length = self::DEFAULT_LENGTH;
                            }
                        } else {
                            if ($this->data['signedin']) {
                                $this->shorten();
                            } else {
                                $this->redirect('/');
                            }
                            
                            break;
                        }
                        
                        if ($this->data['signedin']) {
                            $id = Link::create_for_user($url, $expiration, $maxuses, $this->data['userid'], $length);
                        } else {
                            $id = Link::create($url, $expiration, $maxuses, $length);
                        }
                        
                        if ($id == "-1") {
                            $_SESSION['error'] = "The url is invalid";
                            
                            if ($this->data['signedin']) {
                                $this->redirect('/shorten');
                            } else {
                                $this->redirect('/');
                            }
                            
                            break;
                        }
                        
                        $_SESSION['newlink'] = true;
                        $this->redirect('/link/'.$id);
                        break;
                    default:
                        $link = Link::get($query[0]);
                        
                        if ($link->valid) {
                            $link->add_redirection();
                            $this->redirect($link->url, true);
                        } elseif ($link->status == 'Invalid') {
                            $_SESSION['error'] = "This small name is invalid";
                            $this->redirect('/');
                        } else { 
                            $_SESSION['error'] = "This small name is expired";
                            $this->redirect('/');
                        }
                        
                        break;
                }
            } else {
                $this->index();
            }
        }
    }
    
    private function authenticate($request) {
        $client = new Google_Client();
        $client->setClientId(Config::get('google_clientid'));
        $client->setClientSecret(Config::get('google_clientsecret'));
        $client->setRedirectUri((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']);
        $client->addScope("email");
        $client->addScope("profile");
        $service = new Google_Service_Oauth2($client);
        
        if (isset($request['code'])) {
            $client->authenticate($this->request_pop($request, 'code'));
            $_SESSION['access_token'] = $client->getAccessToken();
            
            if($client->isAccessTokenExpired()) {
                $this->request_pop($request, 'access_token');
                $auth_url = $client->createAuthUrl();
                $this->redirect($auth_url);
            }
            
            $this->redirect('/');
        }
        
        if (isset($request['access_token']) && $request['access_token']) {
            $client->setAccessToken($request['access_token']);
            
            if($client->isAccessTokenExpired()) {
                $this->request_pop($request, 'access_token');
                $auth_url = $client->createAuthUrl();
                $this->redirect($auth_url);
            }
            
            $user = $service->userinfo->get();
            $this->data['signedin'] = true;
            $this->data['userid'] = $user->id;
            $this->data['useremail'] = $user->email;
            $this->data['userimage'] = $user->picture;
        } else {
            $this->request_pop($request, 'access_token');
            $auth_url = $client->createAuthUrl();
            $this->data['signedin'] = false;
            $this->data['authurl'] = $auth_url;
        }
        
        if (isset($request['logout'])) {
            $this->request_pop($request, 'access_token');
            $this->redirect('/');
        }
    }
    
    private function index() {
        if ($this->data['signedin']) {
            $this->data['links'] = Link::get_for_user($this->data['userid']);
            $view = 'views/dashboard.html';
        } else {
            $view = 'views/index.html';
        }
        
        $template = new Template($view, 'views/layout.html');
        echo $template->process($this->data);
    }
    
    private function link($link) {
        $template = new Template('views/link.html', 'views/layout.html');
        $this->data = array_merge($this->data, get_object_vars($link));
        echo $template->process($this->data);
    }
    
    private function modify($link) {
        $template = new Template('views/modify.html', 'views/layout.html');
        $this->data = array_merge($this->data, get_object_vars($link));
        echo $template->process($this->data);
    }
    
    private function shorten() {
        $template = new Template('views/shorten.html', 'views/layout.html');
        echo $template->process($this->data);
    }
    
    private function redirect($url, $external = false) {
        if ($external) {
            if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
                $url = 'http://' . $url;
            }
        }
        
        header('Location: ' . $url);
        exit();
    }
    
    private function request_pop($request, $key) {
        if (isset($request[$key])) {
            $value = $request[$key];
            unset($_GET[$key]);
            unset($_POST[$key]);
            unset($_COOKIE[$key]);
            unset($_REQUEST[$key]);
            unset($_SESSION[$key]);
            unset($request[$key]);
            return $value;
        } else {
            return false;
        }
    }
}
