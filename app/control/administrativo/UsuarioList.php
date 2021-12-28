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

class UsuarioList extends TPage
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
        $this->setActiveRecord('Usuario');
        $this->setDefaultOrder('id', 'asc');
        $this->addFilterField('nome', 'like', 'pesquisar');

        # Formulário
        $this->form = new BootstrapFormBuilder('form_usuario_lista');
        $this->form->setFormTitle('Busca de Usuários');
        $this->form->setFieldSizes('100%');

        # Campos do formulário
        $usuarioBusca = new TEntry('pesquisar');
        $this->form->addFields([new TLabel('Nome'), $usuarioBusca]);
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addActionLink('Novo', new TAction(['UsuarioForm', 'onEdit']), 'fa:plus green');

        # Mantem o formulário preenchido
        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        # Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%;';
        $this->datagrid->disableDefaultClick();

        # Ações do datagrid
        $usuarioDetalhes = new TDataGridAction(['UsuarioDetalhes', 'onDetalhes'], ['key' => '{id}', 'register_state' => 'false']);
        $usuarioDetalhes->setLabel('Detalhes');
        $usuarioDetalhes->setImage('fa:search green');
        
        $usuarioEditar = new TDataGridAction(['UsuarioForm', 'onEdit'], ['key' => '{id}']);
        $usuarioEditar->setLabel('Editar');
        $usuarioEditar->setImage('fa:edit blue');
        
        $usuarioExcluir = new TDataGridAction([$this, 'onDelete'], ['key' => '{id}', 'register_state' => 'false']);
        $usuarioExcluir->setLabel('Excluir');
        $usuarioExcluir->setImage('fa:trash-alt red');
        
        $acoes = new TDataGridActionGroup('Ações ', 'fa:th');
        $acoes->addAction($usuarioDetalhes);
        $acoes->addAction($usuarioEditar);
        $acoes->addAction($usuarioExcluir);

        $usuarioID = new TDataGridColumn('id', 'Código', 'center', '10%');
        $usuarioNome = new TDataGridColumn('nome', 'Nome', 'left', '15%');
        $usuarioSobrenome = new TDataGridColumn('sobrenome', 'Sobrenome', 'left', '15%');
        $usuarioEmail = new TDataGridColumn('email', 'Email', 'center', '35%');
        
        $usuarioNivel = new TDataGridColumn('nivel', 'Nível', 'center', '10%');
        $usuarioNivel->setTransformer(function($valor) {
            if ($valor == 0) {
                $valor = 'Padrão';
            }
            
            if ($valor == 1) {
                $valor = 'Administrador';
            }

            if ($valor == 2) {
                $valor = 'Vendedor';
            }
            
            return $valor;
        });

        # Adicionando colunas no datagrid
        $this->datagrid->addColumn($usuarioID);
        $this->datagrid->addColumn($usuarioNome);
        $this->datagrid->addColumn($usuarioSobrenome);
        $this->datagrid->addColumn($usuarioEmail);
        $this->datagrid->addColumn($usuarioNivel);
        $this->datagrid->addActionGroup($acoes);

        $this->datagrid->createModel();

        # Paginação
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        # Adicionado datagrid e paginação dentro de um painel
        $painel = new TPanelGroup;
        $painel->add($this->datagrid)->style = 'overflow-x: auto;';
        $painel->addFooter($this->pageNavigation);

        $vbox = new TVBox;
        $vbox->style = 'width: 100%;';
        $vbox->add($this->form);
        $vbox->add($painel);

        parent::add($vbox);
    }
}
