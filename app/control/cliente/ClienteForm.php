<?php

use Adianti\Base\AdiantiStandardFormTrait;
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TMaxLengthValidator;
use Adianti\Validator\TMinLengthValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class ClienteForm extends TPage
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

            $this->form = new BootstrapFormBuilder('form_cadastro_cliente');
            $this->form->setFormTitle('Cadastro de Cliente');
            $this->form->setFieldSizes('100%');

            # Configurações obrigatórias do trait
            $this->setDatabase(DATABASE_FILENAME);
            $this->setActiveRecord('Cliente');

            // Campos do formulário
            $id = new TEntry('id');
            $id->setEditable(false);

            $nome = new TEntry('nome');
            $nome->addValidation('Nome', new TMaxLengthValidator, [100]);
            $nome->addValidation('Nome', new TMinLengthValidator, [4]);

            $telefone = new TEntry('telefone');
            $telefone->id = 'telefone';
            $telefone->inputmode = 'numeric';

            $cidade = new TDBUniqueSearch('id_cidade', DATABASE_FILENAME, 'Cidade', 'id', 'nome');
            $cidade->setMinLength(1);
            $cidade->addValidation('Cidade', new TRequiredValidator);

            $endereco = new TEntry('endereco');
            $endereco->addValidation('Endereço', new TMaxLengthValidator, [100]);

            // Adicionando campos ao formulário
            $linha = $this->form->addFields(
                [new TLabel('Código'), $id],
                [new TLabel('Nome*'), $nome],
                [new TLabel('Telefone'), $telefone],
                [new TLabel('Cidade*'), $cidade]
            );
            $linha->layout = ['col-sm-2', 'col-sm-5', 'col-sm-2', 'col-sm-3'];

            $linha = $this->form->addFields([new TLabel('Endereço'), $endereco]);
            $linha->layout = ['col-sm-5'];

            $this->form->addAction('Enviar', new TAction([$this, 'onSave']), 'fa:save green');
            $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
            $this->form->addActionLink('Voltar', new TAction(['ClienteList', 'onReload']), 'fa:arrow-left');

            $vbox = new TVBox;
            $vbox->style = 'width: 100%;';
            $vbox->add($this->form);

            # Criando a máscara para os campos
            TScript::create("$('#telefone').mask('(00) 00000-0000');");

            parent::add($vbox);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onSave($param)
    {
        $data = $this->form->getData();
        $this->form->validate();

        TTransaction::open(DATABASE_FILENAME);

        $data->telefone = preg_replace("/[^0-9]/", "", $data->telefone);
        $cliente = Cliente::create((array) $data);
        $this->form->setData($cliente);
        new TMessage('info', 'Registro salvo');

        TTransaction::close();
    }
}