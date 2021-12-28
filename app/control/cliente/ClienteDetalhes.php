<?php

use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Template\THtmlRenderer;

class ClienteDetalhes extends TPage
{
    public function __construct()
    {        
        parent::__construct();

        # Verifica se o usuário tem permissão
        if (!Helpers::verify_level_user(1)) {
            new TMessage('info', 'Você não tem permissão para acessar essa página');

            if (!TSession::getValue('logged')) {
                LoginForm::onLogout();
            }

            exit;
        }
    
        parent::setTargetContainer('adianti_right_panel');
    }

    public function onDetalhes($param)
    {
        try {
            TTransaction::open(DATABASE_FILENAME);
            
            $cliente = new Cliente($param['key']);
            $cliente->id_cidade = $cliente->cidade->nome;

            $ddd = substr($cliente->telefone, 0, 2);
            $prefix = substr($cliente->telefone, 2, 5);
            $sufix = substr($cliente->telefone, 7);

            $cliente->telefone = (empty($cliente->telefone) ? '' : "({$ddd}) {$prefix}-{$sufix}"); 
            $cliente->endereco ?? $cliente->endereco = '';
            
            $html = new THtmlRenderer('app/resources/cliente_detalhes.html');

            $replaces = [];
            $replaces['title'] = 'Detalhes do Cliente';
            $replaces['cliente'] = $cliente;
            
            $html->enableSection('main', $replaces);
            parent::add($html);

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public static function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
