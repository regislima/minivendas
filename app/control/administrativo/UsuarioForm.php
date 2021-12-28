<?php

use Adianti\Base\AdiantiStandardFormTrait;
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TEmailValidator;
use Adianti\Validator\TMaxLengthValidator;
use Adianti\Validator\TMinLengthValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Wrapper\BootstrapFormBuilder;

class UsuarioForm extends TPage 
{
    private $form;
    
    use AdiantiStandardFormTrait;

    public function __construct()
    {
        try {
            parent::__construct();

            # Verifica se o usuário tem permissão
            if (!Helpers::verify_level_user(1)) {
                new TMessage('info', 'Você não tem permissão para acessar essa página');

                if (!TSession::getValue('logged')) {
                    LoginForm::onLogout();
                }

                exit;
            }

            $this->form = new BootstrapFormBuilder('form_cadastro_usuario');
            $this->form->setFormTitle('Cadastro de Usuario');
            $this->form->setFieldSizes('100%');

            # Configurações obrigatórias do trait
            $this->setDatabase(DATABASE_FILENAME);
            $this->setActiveRecord('Usuario');

            // Campos do formulário
            $id = new TEntry('id');
            $id->setEditable(false);

            $nome = new TEntry('nome');
            $nome->addValidation('Nome', new TMaxLengthValidator, [100]);
            $nome->addValidation('Nome', new TRequiredValidator);
            
            $sobrenome = new TEntry('sobrenome');
            $sobrenome->addValidation('Sobrenome', new TMaxLengthValidator, [100]);
            $sobrenome->addValidation('Sobrenome', new TRequiredValidator);

            $email = new TEntry('email');
            $email->addValidation('Email', new TMaxLengthValidator, [255]);
            $email->addValidation('Email', new TEmailValidator);
            $email->addValidation('Email', new TRequiredValidator);

            $senha = new TPassword('senha');
            $senha->addValidation('Senha', new TMinLengthValidator, [8]);
            $senha->addValidation('Senha', new TMaxLengthValidator, [20]);
            
            $nivel = new TCombo('nivel');
            $nivel->addItems([0 => 'Padrão', 1 => 'Administrador', 2 => 'Vendedor']);
            $nivel->setValue(1);

            $ativo = new TCombo('ativo');
            $ativo->addItems([1 => 'ATIVO', 2 => 'INATIVO']);
            $ativo->setValue(1);

            // Adicionando campos ao formulário
            $linha = $this->form->addFields(
                [new TLabel('Código'), $id],
                [new TLabel('Nome*'), $nome],
                [new TLabel('Sobrenome*'), $sobrenome]
            );
            $linha->layout = ['col-sm-2', 'col-sm-5', 'col-sm-5'];

            $linha = $this->form->addFields(
                [new TLabel('Email*'), $email],
                [new TLabel('Senha*'), $senha],
                [new TLabel('Nível*'), $nivel],
                [new TLabel('Situação*'), $ativo]
            );
            $linha->layout = ['col-sm-3', 'col-sm-3', 'col-sm-3', 'col-sm-3'];

            $this->form->addAction('Enviar', new TAction([$this, 'onSave']), 'fa:save green');
            $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
            $this->form->addActionLink('Voltar', new TAction(['UsuarioList', 'onReload']), 'fa:arrow-left');

            $vbox = new TVBox;
            $vbox->style = 'width: 100%;';
            $vbox->add($this->form);

            parent::add($vbox);
        } catch (Exception $e) {
            new TMessage('erro', $e->getMessage());
        }
    }

    public function onSave($param)
    {
        try {
            TTransaction::open(DATABASE_FILENAME);
            $this->form->validate();

            $usuario = new Usuario;
            $usuario->fromArray((array) $this->form->getData());

            if (empty($usuario->id)) {
                $usuario->senha = password_hash($usuario->senha, PASSWORD_DEFAULT, ['cost' => 10]);
            } else {
                $senha = Usuario::find($usuario->id)->senha;

                if ($senha == $usuario->senha) {
                    $usuario->senha = $senha;
                } else {
                    $usuario->senha = password_hash($usuario->senha, PASSWORD_DEFAULT, ['cost' => 10]);
                }

                $usuario->transmitida = 0;
            }

            $usuario->store();
            
            # Preenche o form
            $this->form->setData($usuario);

            TTransaction::close();
            new TMessage('info', 'Usuario gravado com sucesso');
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
