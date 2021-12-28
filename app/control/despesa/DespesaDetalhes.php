<?php

use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Template\THtmlRenderer;

class DespesaDetalhes extends TPage 
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
            
            $despesa = new Despesa($param['key']);
            $despesa->id_forma_pagamento = $despesa->formaPagamento->forma;
            
            if (!$despesa->data_resgate_cheque) {
                $despesa->data_resgate_cheque = '';
            }
            
            $html = new THtmlRenderer('app/resources/despesa_detalhes.html');

            $replaces = [];
            $replaces['title'] = 'Detalhes da Despesa';
            $replaces['despesa'] = $despesa;
            
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
