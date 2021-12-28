<?php

use Adianti\Control\TAction;
use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Registry\TSession;
use Adianti\Widget\Dialog\TMessage;

require_once 'init.php';

class TApplication extends AdiantiCoreApplication
{
    public static function run($debug = null)
    {
        new TSession();

        if ($_REQUEST) {
            $ini    = AdiantiApplicationConfig::get();
            $class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
            $debug  = is_null($debug) ? $ini['general']['debug'] : $debug;

            if (TSession::getValue('logged') && TSession::getValue('active') == 1) {
                parent::run($debug);
            } else if ($class == 'LoginForm') {
                parent::run($debug);
            } else {
                new TMessage('error', 'Permissão negada', new TAction(array('LoginForm', 'onLogout')));
            }
        }
    }
}

TApplication::run();
