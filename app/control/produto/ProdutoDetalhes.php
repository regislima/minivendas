<?php

use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Template\THtmlRenderer;

class ProdutoDetalhes extends TPage
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
            
            $produto = new Produto($param['key']);
            $produto->obs ?? ($produto->obs = '');
            
            $html = new THtmlRenderer('app/resources/produto_detalhes.html');

            $replaces = [];
            $replaces['title'] = 'Detalhes do Produto';
            $replaces['produto'] = $produto;
            
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
