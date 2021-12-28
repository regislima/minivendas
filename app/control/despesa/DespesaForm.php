<?php

use Adianti\Base\AdiantiStandardFormTrait;
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TMaxLengthValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TText;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapFormBuilder;

class DespesaForm extends TPage 
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

            $this->form = new BootstrapFormBuilder('form_cadastro_despesa');
            $this->form->setFormTitle('Cadastro de Despesa');
            $this->form->setFieldSizes('100%');

            # Configuração do trait
            $this->setDatabase(DATABASE_FILENAME);
            $this->setActiveRecord('Despesa');

            # Campos do formulário
            $id = new TEntry('id');
            $id->setEditable(false);

            $valor = new TEntry('valor');
            $valor->addValidation('Valor', new TMaxLengthValidator, [10]);
            $valor->addValidation('Valor', new TRequiredValidator);
            $valor->setNumericMask(2, ',', '.', true);
            $valor->inputmode = 'numeric';

            $formaPagamento = new TDBCombo('id_forma_pagamento', DATABASE_FILENAME, 'FormaPagamento', 'id', 'forma');
            $formaPagamento->addValidation('Forma de Pagamento', new TRequiredValidator);
            $formaPagamento->setChangeAction(new TAction([$this, 'onSelecionaFormaPagamento']));

            $dataResgateCheque = new TDate('data_resgate_cheque');
            $dataResgateCheque->setMask('dd/mm/yyyy');
            $dataResgateCheque->setDatabaseMask('yyyy-mm-dd');
            $dataResgateCheque->setEditable(false);
            $dataResgateCheque->addValidation('Data de resgate', new TDateValidator, ['dd/mm/yyyy']);
            $dataResgateCheque->inputmode = 'numeric';

            $descricao = new TText('descricao');
            $descricao->addValidation('Descrição', new TMaxLengthValidator, [255]);
            $descricao->setSize('100%', 80);
            $descricao->addValidation('Descrição', new TRequiredValidator);

            $this->form->addFields(
                [new TLabel('Código'), $id],
                [new TLabel('Valor'), $valor],
                [new TLabel('Forma de Pagamento'), $formaPagamento],
                [new TLabel('Data para Resgate do Cheque'), $dataResgateCheque]
            )->layout = ['col-sm-2', 'col-sm-2', 'col-sm-2', 'col-sm-3'];

            $this->form->addFields([new TLabel('Descrição'), $descricao]);

            $this->form->addAction('Enviar', new TAction([$this, 'onSave']), 'fa:save green');
            $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser');
            $this->form->addActionLink('Voltar', new TAction(['DespesaList', 'onReload']), 'fa:arrow-left');

            $vbox = new TVBox;
            $vbox->style = 'width: 100%;';
            $vbox->add($this->form);

            parent::add($vbox);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onSelecionaFormaPagamento($param)
    {
        // Cheque
        if ($param['id_forma_pagamento'] == '4') {
            TDate::enableField('form_cadastro_despesa', 'data_resgate_cheque');
        } else {
            TDate::disableField('form_cadastro_despesa', 'data_resgate_cheque');
        }
    }

    public function onSave($param)
    {
        try {
            $data = $this->form->getData();
            $this->form->validate();

            TTransaction::open(DATABASE_FILENAME);
            
            $despesa = Despesa::create((array) $data);
            $this->form->setData($despesa);
            new TMessage('info', 'Registro salvo');

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
