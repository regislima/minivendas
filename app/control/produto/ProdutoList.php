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

class ProdutoList extends TPage 
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
        $this->setActiveRecord('Produto');
        $this->setDefaultOrder('id', 'asc');
        $this->addFilterField('nome', 'like', 'pesquisar');

        # Formulário
        $this->form = new BootstrapFormBuilder('form_produto_lista');
        $this->form->setFormTitle('Busca de Produtos');
        $this->form->setFieldSizes('100%');

        # Campos do formulário
        $produtoBusca = new TEntry('pesquisar');
        $this->form->addFields([new TLabel('Nome'), $produtoBusca]);
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addActionLink('Novo', new TAction(['ProdutoForm', 'onEdit']), 'fa:plus green');

        # Mantem o formulário preenchido
        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        # Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%;';
        $this->datagrid->disableDefaultClick();

        # Colunas do datagrid
        $produtoDetalhes = new TDataGridAction(['ProdutoDetalhes', 'onDetalhes'], ['key' => '{id}', 'register_state' => 'false']);
        $produtoDetalhes->setLabel('Detalhes');
        $produtoDetalhes->setImage('fa:search green');
        
        $produtoEditar = new TDataGridAction(['ProdutoForm', 'onEdit'], ['key' => '{id}']);
        $produtoEditar->setLabel('Editar');
        $produtoEditar->setImage('fa:edit blue');
        
        $produtoExcluir = new TDataGridAction([$this, 'onDelete'], ['key' => '{id}', 'register_state' => 'false']);
        $produtoExcluir->setLabel('Excluir');
        $produtoExcluir->setImage('fa:trash-alt red');
        
        $acoes = new TDataGridActionGroup('Ações ', 'fa:th');
        $acoes->addAction($produtoDetalhes);
        $acoes->addAction($produtoEditar);
        $acoes->addAction($produtoExcluir);

        $produtoID = new TDataGridColumn('id', 'Código', 'center', '10%');
        $produtoNome = new TDataGridColumn('nome', 'Nome', 'left', '40%');
        
        $produtoPrecoCusto = new TDataGridColumn('preco_custo', 'Preco de Custo', 'center', '20%');
        $produtoPrecoCusto->setTransformer(function($precoCusto){
            return number_format($precoCusto, 2, ',', '.');
        });
        
        $produtoPrecoVenda = new TDataGridColumn('preco_venda', 'Preco de Venda', 'center', '20%');
        $produtoPrecoVenda->setTransformer(function($precoVenda){
            return number_format($precoVenda, 2, ',', '.');
        });
        
        $produtoEstoque = new TDataGridColumn('estoque', 'Estoque', 'center', '10%');

        # Adicionando colunas no datagrid
        $this->datagrid->addColumn($produtoID);
        $this->datagrid->addColumn($produtoNome);
        $this->datagrid->addColumn($produtoPrecoCusto);
        $this->datagrid->addColumn($produtoPrecoVenda);
        $this->datagrid->addColumn($produtoEstoque);
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
