<?php

use Adianti\Base\AdiantiStandardListTrait;
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Registry\TSession;
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
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class ClienteList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    
    use AdiantiStandardListTrait;

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

        # Configurações obrigatórias do trait
        $this->setDatabase(DATABASE_FILENAME);
        $this->setActiveRecord('Cliente');
        $this->setDefaultOrder('id', 'asc');
        $this->addFilterField('nome', 'like', 'pesquisar');

        # Formulário
        $this->form = new BootstrapFormBuilder('form_cliente_lista');
        $this->form->setFormTitle('Busca de Clientes');
        $this->form->setFieldSizes('100%');

        # Campos do formulário
        $clienteBusca = new TEntry('pesquisar');
        
        $this->form->addFields([new TLabel('Nome'), $clienteBusca]);
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addActionLink('Novo', new TAction(['ClienteForm', 'onEdit']), 'fa:plus green');

        # Mantem o formulário preenchido
        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        # Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%;';
        $this->datagrid->disableDefaultClick();

        # Colunas do datagrid
        $clienteDetalhes = new TDataGridAction(['ClienteDetalhes', 'onDetalhes'], ['key' => '{id}', 'register_state' => 'false']);
        $clienteDetalhes->setLabel('Detalhes');
        $clienteDetalhes->setImage('fa:search green');
        
        $clienteEditar = new TDataGridAction(['ClienteForm', 'onEdit'], ['key' => '{id}']);
        $clienteEditar->setLabel('Editar');
        $clienteEditar->setImage('fa:edit blue');
        
        $clienteExcluir = new TDataGridAction([$this, 'onDelete'], ['key' => '{id}', 'register_state' => 'false']);
        $clienteExcluir->setLabel('Excluir');
        $clienteExcluir->setImage('fa:trash-alt red');
        
        $acoes = new TDataGridActionGroup('Ações ', 'fa:th');
        $acoes->addAction($clienteDetalhes);
        $acoes->addAction($clienteEditar);
        $acoes->addAction($clienteExcluir);
        
        $clienteID = new TDataGridColumn('id', 'Código', 'center', '10%');
        $clienteNome = new TDataGridColumn('nome', 'Nome', 'center', '35%');
        $clienteCidade = new TDataGridColumn('cidade->nome', 'Nome', 'center', '35%');
        
        $clienteTelefone = new TDataGridColumn('telefone', 'Telefone', 'center', '20%');
        $clienteTelefone->setTransformer(function($valor) {
            $ddd = substr($valor, 0, 2);
            $prefix = substr($valor, 2, 5);
            $sufix = substr($valor, 7);

            return "({$ddd}) {$prefix}-{$sufix}";
        });

        # Adicionando colunas no datagrid
        $this->datagrid->addColumn($clienteID);
        $this->datagrid->addColumn($clienteNome);
        $this->datagrid->addColumn($clienteCidade);
        $this->datagrid->addColumn($clienteTelefone);
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