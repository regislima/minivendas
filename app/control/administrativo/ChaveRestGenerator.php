<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Registry\TSession;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;

class ChaveRestGenerator extends TPage
{
    private $form;

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

        $this->form = new BootstrapFormBuilder('form_hash');
        $this->form->setFormTitle('Gerar Chave');

        $key = new TEntry('key');
        $key->addValidation('Chave', new TRequiredValidator);
        $key->autofocus = true;
        
        $hashKey = new TEntry('hashkey');
        $hashKey->setTip('Copie esse código e cole no arquivo <i>app/config/application.ini</i> na linha <i>rest_auth</i>');

        $this->form->addFields([new TLabel('Chave')], [$key]);
        $this->form->addFields([new TLabel('Hash')], [$hashKey]);
        $this->form->addAction('Gerar', new TAction([$this, 'onGeraHash']), 'fas:puzzle-piece blue');

        $wrapper = new TElement('div');
        $wrapper->style = 'margin: auto; margin-top: 100px; max-width: 800px;';
        $wrapper->id = 'gera-wrapper';
        $wrapper->add($this->form);

        parent::add($wrapper);
    }

    public function onGeraHash($param)
    {
        try {
            $this->form->validate();
            $dados = $this->form->getData();
            $hash = password_hash($dados->key, PASSWORD_DEFAULT, ['cost' => 10]);
            $dados->hashkey = $hash;
            $this->form->setData($dados);
        } catch (Exception $e) {
            new TMessage('info', $e->getMessage());
        }
    }
}
