<?php

use Adianti\Base\AdiantiStandardFormListTrait;
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Registry\TSession;
use Adianti\Validator\TMaxLengthValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridActionGroup;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class CidadeFormList extends TPage 
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    
    use AdiantiStandardFormListTrait;

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

        $this->form = new BootstrapFormBuilder('form_cadastro_cidade');
        $this->form->setFormTitle('Cadastro de Cidade');
        $this->form->setFieldSizes('100%');

        # Configurações obrigatórias do trait
        $this->setDatabase(DATABASE_FILENAME);
        $this->setActiveRecord('Cidade');
        $this->setDefaultOrder('id', 'asc');

        # Campos do formulário
        $id = new TEntry('id');
        $id->setEditable(false);

        $nome = new TEntry('nome');
        $nome->addValidation('Nome', new TMaxLengthValidator, [50]);
        $nome->addValidation('Nome', new TRequiredValidator);

        $estado = new TDBUniqueSearch('id_estado', DATABASE_FILENAME, 'Estado', 'id', 'nome');
        $estado->setMinLength(1);
        $estado->addValidation('Cidade', new TRequiredValidator);

        $linha = $this->form->addFields(
            [new TLabel('Código'), $id],
            [new TLabel('Nome*'), $nome],
            [new TLabel('Estado*'), $estado]
        );
        $linha->layout = ['col-sm-2', 'col-sm-4', 'col-sm-3'];

        $this->form->addAction('Enviar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser');

        # Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%;';
        $this->datagrid->disableDefaultClick();

        # Colunas do datagrid
        $cidadeEditar = new TDataGridAction([$this, 'onEdit'], ['key' => '{id}']);
        $cidadeEditar->setLabel('Editar');
        $cidadeEditar->setImage('fa:edit blue');
        
        $cidadeExcluir = new TDataGridAction([$this, 'onDelete'], ['key' => '{id}', 'register_state' => 'false']);
        $cidadeExcluir->setLabel('Excluir');
        $cidadeExcluir->setImage('fa:trash-alt red');
        
        $acoes = new TDataGridActionGroup('Ações ', 'fa:th');
        $acoes->addAction($cidadeEditar);
        $acoes->addAction($cidadeExcluir);

        $cidadeID = new TDataGridColumn('id', 'Código', 'center', '20%');
        $cidadeNome = new TDataGridColumn('nome', 'Nome', 'center', '60%');
        $cidadeEstado = new TDataGridColumn('{estado->nome} - {estado->sigla}', 'Estado', 'left', '20%');

        # Adicionando colunas no datagrid
        $this->datagrid->addColumn($cidadeID);
        $this->datagrid->addColumn($cidadeNome);
        $this->datagrid->addColumn($cidadeEstado);
        $this->datagrid->addActionGroup($acoes);

        $this->datagrid->createModel();

        # Paginação
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        # Adicionado datagrid e paginação dentro de um painel
        $painel = new TPanelGroup();
        $painel->add($this->datagrid)->style = 'overflow-x: auto;';
        $painel->addFooter($this->pageNavigation);

        $vbox = new TVBox;
        $vbox->style = 'width: 100%;';
        $vbox->add($this->form);
        $vbox->add($painel);

        parent::add($vbox);
    }
}
