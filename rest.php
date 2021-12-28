<?php

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Core\AdiantiCoreApplication;

header('Content-Type: application/json; charset=utf-8');

require_once 'init.php';

/**
 * Classe Rest que usa autorização 'Basic'
 */
class AdiantiRestServer
{
    public static function run($request)
    {
        $ini      = AdiantiApplicationConfig::get();
        $input    = json_decode(file_get_contents("php://input"), true);
        $request  = array_merge($request, (array) $input);
        $class    = isset($request['class']) ? $request['class']   : '';
        $method   = isset($request['method']) ? $request['method'] : '';
        $headers  = AdiantiCoreApplication::getHeaders();
        $response = NULL;

        $headers['Authorization'] = $headers['Authorization'] ?? ($headers['authorization'] ?? null);

        try {
            if (empty($headers['Authorization'])) {
                throw new Exception('Erro de autorização');
            } else {
                if (empty($ini['rest']['rest_key'])) {
                    throw new Exception('Chave REST não definida');
                }

                if (!password_verify($ini['rest']['rest_key'], $headers['Authorization'])) {
                    return json_encode(array('status' => 'error', 'data' => 'Erro de Autorização'));
                }
            }

            $response = AdiantiCoreApplication::execute($class, $method, $request, 'rest');
            
            if (is_array($response)) {
                array_walk_recursive($response, ['AdiantiStringConversion', 'assureUnicode']);
            }
            
            return json_encode(array('status' => 'success', 'data' => $response));
        } catch (Exception $e) {
            return json_encode(array('status' => 'error', 'data' => $e->getMessage()));
        } catch (Error $e) {
            return json_encode(array('status' => 'error', 'data' => $e->getMessage()));
        }
    }
}

print AdiantiRestServer::run($_REQUEST);
