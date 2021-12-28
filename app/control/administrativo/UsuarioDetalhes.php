<?php

use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Template\THtmlRenderer;

class UsuarioDetalhes extends TPage
{
    public function __construct()
    {
        parent::__construct();

        # Verifica se o usuário tem permissão
        if (!Helpers::verify_level_user(1) && !Helpers::verify_level_user(2)) {
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

            $usuario = new Usuario($param['key']);

            switch ($usuario->nivel) {
                case 0:
                    $usuario->nivel = 'Padrão';
                    break;
                
                case 1:
                    $usuario->nivel = 'Administrador';
                    break;

                case 2:
                    $usuario->nivel = 'Vendedor';
                    break;
            }

            $usuario->ativo == 1 ? $usuario->ativo = 'ATIVO' : $usuario->ativo = 'INATIVO';

            if (empty($usuario->updated_at)) {
                $usuario->updated_at = '';
            }

            $html = new THtmlRenderer('app/resources/usuario_detalhes.html');

            $replaces = [];
            $replaces['title'] = 'Detalhes do Usuário';
            $replaces['usuario'] = $usuario;

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
