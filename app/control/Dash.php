<?php

use Adianti\Control\TPage;

class Dash extends TPage
{
    public function __construct()
    {
        parent::__construct();

        $file = parse_ini_file('app/config/' . DATABASE_FILENAME . '.ini');
        $object = null;
        
        switch (Helpers::get_level()) {
            case 1:
                $object = new DashAdmin;
                break;

            case 2:
                $object = new DashVendedor;  
                break;

            default:
                $object = new MinivendasRest;
                $object->loadUsuarios();
                LoginForm::onLogout();
                exit;
        }

        parent::add($object);
    }
}
