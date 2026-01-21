<?php





class CookieManager {
    
    


    const REMEMBER_ME_EXPIRY = 30 * 24 * 60 * 60;  
    const FORM_DATA_EXPIRY = 7 * 24 * 60 * 60;  
    const PREFERENCE_EXPIRY = 365 * 24 * 60 * 60;  
    const SESSION_EXPIRY = 24 * 60 * 60;  
    
    










    public static function set($name, $value, $expiry = self::SESSION_EXPIRY, $httpOnly = true, $secure = false, $sameSite = 'Lax') {
         
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        
         
        $encodedValue = urlencode($value);
        
         
        $expires = time() + $expiry;
        
         
        $cookieString = $name . '=' . $encodedValue;
        $cookieString .= '; expires=' . gmdate('D, d M Y H:i:s', $expires) . ' GMT';
        $cookieString .= '; path=/';
        $cookieString .= '; SameSite=' . $sameSite;
        
              if ($httpOnly) {
            $cookieString .= '; HttpOnly';
        }
        
         
        if ($secure || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
            $cookieString .= '; Secure';
        }
        
        return setcookie($name, $encodedValue, $expires, '/', '', $secure, $httpOnly);
    }
    
    






    public static function get($name, $default = null) {
        if (!isset($_COOKIE[$name])) {
            return $default;
        }
        
        $value = urldecode($_COOKIE[$name]);
        
         
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        return $value;
    }
    
    





    public static function delete($name) {
        if (isset($_COOKIE[$name])) {
            unset($_COOKIE[$name]);
        }
        return setcookie($name, '', time() - 3600, '/');
    }
    
    





    public static function exists($name) {
        return isset($_COOKIE[$name]);
    }
    
    






    public static function setRememberMe($userId, $userType) {
         
        $token = bin2hex(random_bytes(32));
        
        $data = [
            'user_id' => $userId,
            'user_type' => $userType,
            'token' => hash('sha256', $token),  
            'created_at' => time()
        ];
        
         
        self::set('remember_me_token', $token . '|' . base64_encode(json_encode($data)), self::REMEMBER_ME_EXPIRY, true, false, 'Strict');
        
        return $token;
    }
    
    




    public static function getRememberMe() {
        if (!self::exists('remember_me_token')) {
            return null;
        }
        
        $cookieValue = self::get('remember_me_token');
        if (!$cookieValue || !is_string($cookieValue)) {
            return null;
        }
        
         
        $parts = explode('|', $cookieValue, 2);
        if (count($parts) !== 2) {
            return null;
        }
        
        $token = $parts[0];
        $dataJson = base64_decode($parts[1]);
        $data = json_decode($dataJson, true);
        
        if (!$data || !isset($data['token']) || !isset($data['user_id']) || !isset($data['user_type'])) {
            return null;
        }
        
         
        if (hash('sha256', $token) !== $data['token']) {
            return null;
        }
        
         
        if (isset($data['created_at']) && (time() - $data['created_at']) > self::REMEMBER_ME_EXPIRY) {
            self::delete('remember_me_token');
            return null;
        }
        
        return [
            'user_id' => $data['user_id'],
            'user_type' => $data['user_type']
        ];
    }
    
    


    public static function clearRememberMe() {
        self::delete('remember_me_token');
    }
    
    






    public static function saveFormData($formName, $data) {
        $cookieName = 'form_data_' . $formName;
        return self::set($cookieName, $data, self::FORM_DATA_EXPIRY, false, false, 'Lax');
    }
    
    





    public static function getFormData($formName) {
        $cookieName = 'form_data_' . $formName;
        return self::get($cookieName, null);
    }
    
    




    public static function clearFormData($formName) {
        $cookieName = 'form_data_' . $formName;
        self::delete($cookieName);
    }
    
    






    public static function savePreference($key, $value) {
        $preferences = self::get('user_preferences', []);
        if (!is_array($preferences)) {
            $preferences = [];
        }
        $preferences[$key] = $value;
        return self::set('user_preferences', $preferences, self::PREFERENCE_EXPIRY, false, false, 'Lax');
    }
    
    






    public static function getPreference($key, $default = null) {
        $preferences = self::get('user_preferences', []);
        if (is_array($preferences) && isset($preferences[$key])) {
            return $preferences[$key];
        }
        return $default;
    }
    
    




    public static function getAllPreferences() {
        return self::get('user_preferences', []);
    }
    
    


    public static function clearPreferences() {
        self::delete('user_preferences');
    }
    
    





    public static function trackVisit($page, $metadata = []) {
        $visits = self::get('visit_history', []);
        if (!is_array($visits)) {
            $visits = [];
        }
        
        $visits[] = [
            'page' => $page,
            'timestamp' => time(),
            'metadata' => $metadata
        ];
        
         
        if (count($visits) > 50) {
            $visits = array_slice($visits, -50);
        }
        
        self::set('visit_history', $visits, self::SESSION_EXPIRY * 7, false, false, 'Lax');
    }
    
    





    public static function getVisitHistory($limit = 10) {
        $visits = self::get('visit_history', []);
        if (!is_array($visits)) {
            return [];
        }
        
         
        usort($visits, function($a, $b) {
            return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
        });
        
        return array_slice($visits, 0, $limit);
    }
    
    




    public static function clearAll($preserveCookies = []) {
        foreach ($_COOKIE as $name => $value) {
            if (!in_array($name, $preserveCookies)) {
                self::delete($name);
            }
        }
    }
}

?>
