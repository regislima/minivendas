<?php

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;

class Auth
{
    /**
     * Autentica o usuário
     * @param mixed $login 
     * @param mixed $senha 
     * @return bool TRUE em caso de sucesso, FALSE caso contrário
     * @throws Exception 
     */
    public static function authenticate($login, $senha): bool
    {
        $ini = AdiantiApplicationConfig::get();
        TTransaction::open($ini['database']['database_file_name']);

        $user = Usuario::where('email', '=', $login)->first();

        if ($user) {
            if (self::verify($senha, $user->senha)) {
                self::loadSessionVars($user);
                TTransaction::close();

                return true;
            }
        }

        TTransaction::close();
        return false;
    }

    /**
     * Verifica se a senha informada é a mesma que está registrada no banco de dados
     * @param $userHashSenha Senha registrada no banco de dados
     * @param $senha Senha informada pelo usuário
     * @return bool TRUE se a senha coincide, FALSE caso contrário
     */
    private static function verify($senha, string $userHashSenha): bool
    {
        if (!password_verify($senha, $userHashSenha)) {
            return false;
        }

        return true;
    }

    /**
     * Carrega a sessão com as informações do usuário logado
     * @param mixed $user 
     * @return void 
     */
    private static function loadSessionVars($user): void
    {
        TSession::setValue('logged', TRUE);
        TSession::setValue('userid', $user->id);
        TSession::setValue('active', $user->ativo);
    }
}
