<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TEmailValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TPassword;
use Adianti\Wrapper\BootstrapFormBuilder;

class LoginForm extends TPage
{
    protected $form;

    function __construct($param)
    {
        parent::__construct();

        $this->style = 'clear: both;';

        $this->form = new BootstrapFormBuilder('form_login');
        $this->form->setFormTitle('ENTRAR');

        $login = new TEntry('login');
        $login->setSize('70%', 40);
        $login->style = 'height: 35px; font-size: 14px; float: left; border-bottom-left-radius: 0; border-top-left-radius: 0;';
        $login->placeholder = 'Email';
        $login->autofocus = 'autofocus';

        $senha = new TPassword('senha');
        $senha->setSize('70%', 40);
        $senha->style = 'height: 35px; font-size: 14px; float: left; border-bottom-left-radius: 0; border-top-left-radius: 0;';
        $senha->placeholder = 'Senha';

        $user = '<span class="login-avatar"><span class="fa fa-user"></span></span>';
        $locker = '<span class="login-avatar"><span class="fa fa-lock"></span></span>';

        $this->form->addFields([$user, $login])->layout = ['col-sm-12 display-flex'];
        $this->form->addFields([$locker, $senha])->layout = ['col-sm-12 display-flex'];

        $btn = $this->form->addAction('Entrar', new TAction(array($this, 'onLogin')), '');
        $btn->class = 'btn btn-primary';
        $btn->style = 'height: 40px; width: 90%; display: block; margin: auto; font-size: 17px;';

        $wrapper = new TElement('div');
        $wrapper->style = 'margin: auto; margin-top: 100px; max-width: 460px;';
        $wrapper->id = 'login-wrapper';
        $wrapper->add($this->form);

        parent::add($wrapper);
    }

    /**
     * Autentica o usuário
     */
    public static function onLogin($param)
    {
        try {
            $data = (object) $param;

            $pass = filter_var($data->senha, FILTER_SANITIZE_STRIPPED);

            (new TRequiredValidator)->validate('Email', $data->login);
            (new TEmailValidator)->validate('Email', $data->login);
            (new TRequiredValidator)->validate('Senha', $pass);

            # Verifica se foram efetuadas as tentativas de login especificadas
            if (Helpers::request_limit('raonivendaslogin', 3)) {
                new TMessage('warning', 'Você já efetuou 3 tentativas. Aguarde 1 minuto e tente novamente');

                return;
            }

            TSession::regenerate();
            $user = Auth::authenticate($data->login, $pass);

            if ($user) {
                AdiantiCoreApplication::gotoPage('Dash');
            } else {
                new TMessage('error', 'Credenciais incorretas');
            }

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * Logout
     */
    public static function onLogout()
    {
        TSession::freeSession();
        AdiantiCoreApplication::gotoPage('LoginForm');
    }
}
