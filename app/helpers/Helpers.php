<?php

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;

class Helpers {

    /**
     * Define a quantidade de letras que a string conterá
     * 
     * @param string $string 
     * @param int $limit 
     * @param string $pointer 
     * @return string 
     */
    public static function str_limit_letters(string $string, int $limit, string $pointer = '...'): string
    {
        $string = trim(filter_var($string, FILTER_SANITIZE_SPECIAL_CHARS));
        $numLetters = strlen($string);

        if ($numLetters < $limit) {
            return $string;
        }

        $words = substr($string, 0, $limit);

        return "{$words} {$pointer}";
    }

    /**
     * Define um limite de requisições
     * 
     * @param string $key 
     * @param int $limit 
     * @param int $seconds 
     * @return bool TRUE caso o número de tentativas ultrapassou o limite, FALSE caso contrário
     */
    public static function request_limit(string $key, int $limit = 5, int $seconds = 60): bool
    {
        $session = new TSession();

        if ($session::getValue($key) && $session::getValue($key)['time'] >= time() && $session::getValue($key)['requests'] < $limit) {
            $session::setValue($key, [
                'time' => time() + $seconds,
                'requests' => $session::getValue($key)['requests'] + 1
            ]);

            return false;
        }

        if ($session::getValue($key) && $session::getValue($key)['time'] >= time() && $session::getValue($key)['requests'] >= $limit) {
            return true;
        }

        $session->setValue($key, [
            'time' => time() + $seconds,
            'requests' => 1
        ]);

        return false;
    }

    /**
     * Retorna o nível do usuário logado
     * @return int 
     */
    public static function get_level(): int
    {
        $ini = AdiantiApplicationConfig::get();

        if (TSession::getValue('logged')) {
            TTransaction::open($ini['database']['database_file_name']);
            $user = new Usuario(TSession::getValue('userid'));
            TTransaction::close();

            if ($user) {
                return $user->nivel;
            }
        }
    }

    /**
     * Verifica se o usuário logado tem permissão necessária
     * @param int $id 
     * @return bool 
     */
    public static function verify_level_user(int $level): bool
    {
        $ini = AdiantiApplicationConfig::get();
        
        if (TSession::getValue('logged')) {
            TTransaction::open($ini['database']['database_file_name']);
            $user = new Usuario(TSession::getValue('userid'));
            TTransaction::close();

            if ($user) {
                return $user->nivel == $level ? true : false;
            }
        }

        return false;
    }

    /**
     * Realiza os requests REST
     * @param mixed $url 
     * @param string $method 
     * @param array $params 
     * @param mixed|null $authorization 
     * @return mixed 
     * @throws Exception 
     */
    public static function request($url, $method = 'POST', $params = [], $authorization = null)
    {
        $ch = curl_init();

        if ($method == 'POST' or $method == 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_POST, true);
        } else if (($method == 'GET' or $method == 'DELETE') and !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $defaults = array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 10
        );

        if (!empty($authorization)) {
            $defaults[CURLOPT_HTTPHEADER] = ['Authorization: ' . $authorization];
        }

        curl_setopt_array($ch, $defaults);
        $output = curl_exec($ch);

        curl_close($ch);
        $return = (array) json_decode($output);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Return is not JSON. Check the URL: ' . $output);
        }

        if ($return['status'] == 'error') {
            throw new Exception($return['data']);
        }
        
        return $return['data'];
    }

    public static function uniqidReal($lenght = 13) {
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($lenght / 2));
        } else if (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            throw new Exception("Nenhuma função de criptografia segura foi encontrada");
        }

        return substr(bin2hex($bytes), 0, $lenght);
    }

    /**
     * Gera cores aleatórias hexadecimais
     * @return string 
     */
    public static function random_color(): string {
        $letters = '0123456789ABCDEF';
        $color = '#';
        
        for($i = 0; $i < 6; $i++) {
            $index = mt_rand(0, 15);
            $color .= $letters[$index];
        }
        
        return $color;
    }
}