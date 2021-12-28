<?php

use Adianti\Base\AdiantiStandardListTrait;
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridActionGroup;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TAlert;
use Adianti\Widget\Dialog\TInputDialog;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TToast;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class VendaList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    
    use AdiantiStandardListTrait;

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

        # Configurações do trait
        $this->setDatabase(DATABASE_FILENAME);
        $this->setActiveRecord('Venda');

        $this->form = new BootstrapFormBuilder('form_venda_lista');
        $this->form->setFormTitle('Busca de Venda');
        $this->form->setFieldSizes('100%');

        # Campos do formulário
        $formaPagamento = new TDBCombo('id_forma_pagamento', DATABASE_FILENAME, 'FormaPagamento', 'id', 'forma');

        $situacao = new TCombo('situacao');
        $situacao->addItems(['Pago' => 'Pago', 'Devendo' => 'Devendo']);

        $cliente = new TDBUniqueSearch('id_cliente', DATABASE_FILENAME, 'Cliente', 'id', 'nome');
        $cliente->setMinLength(2);

        $vendaDe = new TDate('venda_de');
        $vendaDe->setMask('dd/mm/yyyy', true);
        $vendaDe->setDatabaseMask('yyyy-mm-dd');

        $vendaAte = new TDate('venda_ate');
        $vendaAte->setMask('dd/mm/yyyy', true);
        $vendaAte->setDatabaseMask('yyyy-mm-dd');

        $cidade = new TDBUniqueSearch('id_cidade', DATABASE_FILENAME, 'Cidade', 'id', 'nome');
        $cidade->setMinLength(2);

        $field = $this->form->addFields([new TLabel('Forma de Pagamento'), $formaPagamento], [new TLabel('Situação'), $situacao], [new TLabel('Cliente'), $cliente]);
        $field->layout = ['col-sm-2', 'col-sm-2', 'col-sm-2'];

        $field = $this->form->addFields([new TLabel('Data (de)'), $vendaDe], [new TLabel('Data (até)'), $vendaAte], [new TLabel('Cidade'), $cidade]);
        $field->layout = ['col-sm-2', 'col-sm-2', 'col-sm-2'];

        # Tipos de Relatórios
        $dropdown = new TDropDown('Relatórios', 'fa:list');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction('Relatório de Vendas', new TAction([$this, 'onReportVendas'], ['register_state' => 'false']), 'far:file-pdf fa-fw red');
        $dropdown->addAction('Relatório de Cobrança', new TAction([$this, 'onReportCobranca'], ['register_state' => 'false']), 'far:file-pdf fa-fw red');
        $dropdown->addAction('Relatório de Produtos', new TAction([$this, 'onReportProdutoVendido'], ['register_state' => 'false']), 'far:file-pdf fa-fw red');

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addActionLink('Novo', new TAction(['VendaForm', 'onEdit']), 'fa:plus green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser');
        $this->form->addFooterWidget($dropdown);

        # Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%;';
        $this->datagrid->disableDefaultClick();

        # Colunas do datagrid
        $vendaDetalhes = new TDataGridAction(['VendaDetalhes', 'onDetalhes'], ['key' => '{uniqid}', 'register_state' => 'false']);
        $vendaDetalhes->setLabel('Detalhes');
        $vendaDetalhes->setImage('fa:search green');

        $vendaEditar = new TDataGridAction(['VendaForm', 'onEdit'], ['key' => '{uniqid}']);
        $vendaEditar->setLabel('Editar');
        $vendaEditar->setImage('fa:edit blue');

        $vendaDelete = new TDataGridAction([$this, 'onDelete'], ['key' => '{uniqid}', 'register_state' => 'false']);
        $vendaDelete->setLabel('Excluir');
        $vendaDelete->setImage('fas:trash red');

        $acoes = new TDataGridActionGroup('Ações ', 'fa:th');
        $acoes->addAction($vendaDetalhes);
        $acoes->addAction($vendaEditar);
        $acoes->addAction($vendaDelete);

        $vendaData = new TDataGridColumn('created_at', 'Data da Venda', 'center', '30%');
        $vendaData->setTransformer(function ($valor) {
            $data = new DateTime($valor);

            return $data->format('d/m/Y H:i:s');
        });

        $vendaValor = new TDataGridColumn('valor_final', 'Valor', 'center', '25%');
        $vendaValor->setTransformer(function ($valor) {
            return number_format($valor, 2, ',', '.');
        });

        $vendaFormaPagamento = new TDataGridColumn('id_forma_pagamento', 'Forma de Pagamento', 'center', '25%');
        $vendaFormaPagamento->setTransformer(function ($valor) {
            $forma = new FormaPagamento($valor);

            return $forma->forma;
        });

        $vendaSituacao = new TDataGridColumn('situacao', 'Situação', 'center', '20%');
        $vendaSituacao->setTransformer(function ($valor, $objeto, $row) {
            $div = new TElement('span');
            if ($valor == 'Pago') {
                $div->class="label label-success";
            }

            if ($valor == 'Devendo') {
                $div->class="label label-warning";
            }

            $div->add($objeto->situacao);
            return $div;
        });

        # Adicionando colunas no datagrid
        $this->datagrid->addColumn($vendaData);
        $this->datagrid->addColumn($vendaValor);
        $this->datagrid->addColumn($vendaFormaPagamento);
        $this->datagrid->addColumn($vendaSituacao);
        $this->datagrid->addActionGroup($acoes);
        $this->datagrid->createModel();

        # Paginação
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
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

    public function onSearch($param)
    {
        $data = $this->form->getData();
        $filters = [];

        if ($data->id_forma_pagamento) {
            $filters[] = new TFilter('id_forma_pagamento', '=', $data->id_forma_pagamento);
        }

        if ($data->situacao) {
            $filters[] = new TFilter('situacao', '=', $data->situacao);
        }

        if ($data->id_cliente) {
            $filters[] = new TFilter('id_cliente', '=', $data->id_cliente);
        }

        if ($data->venda_de && $data->venda_ate) {
            $filters[] = new TFilter("date_format(created_at, '%Y-%m-%d')", 'BETWEEN', $data->venda_de, $data->venda_ate);
        } else if ($data->venda_de) {
            $filters[] = new TFilter('date_format(created_at, "%Y-%m-%d")', '>=', $data->venda_de);
        } else if ($data->venda_ate) {
            $filters[] = new TFilter('date_format(created_at, "%Y-%m-%d")', '<=', $data->venda_ate);
        }

        if ($data->id_cidade) {
            if (!$data->id_cliente)
                $filters[] = new TFilter('id_cliente', '=', "select id from cliente where id_cidade = {$data->id_cidade}");
        }

        TSession::setValue('VendaList_filter', $filters);
        TSession::setValue('VendaList_data', $data);
        $this->onReload();
    }

    public function onReload($param = null)
    {
        $criteria = new TCriteria;
        $criteria->setProperty('order', 'created_at');
        $criteria->setProperty('direction', 'desc');
        $criteria->setProperty('limit', 10);
        $criteria->setProperties($param);

        if (TSession::getValue('VendaList_filter')) {
            foreach (TSession::getValue('VendaList_filter') as $filter) {
                $criteria->add($filter);
            }
        }

        try {
            TTransaction::open(DATABASE_FILENAME);

            $usuario = new Usuario(TSession::getValue('userid'));

            if ($usuario) {
                $repo = new TRepository('Venda');
                
                if ($usuario->nivel == 1) {
                    $vendas = $repo->load($criteria);
                }

                if ($usuario->nivel == 2) {
                    $criteria->add(new TFilter('id_usuario', '=', $usuario->id));
                    $vendas = $repo->load($criteria);
                }
            }
            
            $this->datagrid->clear();

            if ($vendas) {
                foreach ($vendas as $venda) {
                    $this->datagrid->addItem($venda);
                }
            }

            $criteria->resetProperties();
            $this->pageNavigation->setCount($repo->count($criteria));
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit(10);

            TTransaction::close();
            $this->form->setData(TSession::getValue('VendaList_data'));
            $this->loaded = true;
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onDelete($param)
    {
        $form = new BootstrapFormBuilder('confirm_form');
        
        $pass = new TPassword('password');
        $pass->autofocus = 'autofocus';

        $form->addContent([new TAlert('warning', 'Cuidado, essa operação não poderá ser desfeita. Caso precise repor os produtos, terá que ser feito manualmente.')]);
        $form->addFields([new TLabel('Senha')], [$pass]);
        $form->addAction('Confirmar', new TAction([__CLASS__, 'onConfirm'], ['uniqid' => $param['uniqid'], 'register_state' => 'false']), 'far:check-circle blue');
        
        // show the input dialog
        new TInputDialog('Insira a senha para excluir a venda', $form);
    }

    public function onConfirm($param)
    {
        try {
            TTransaction::open(DATABASE_FILENAME);
            $email = (new Usuario(TSession::getValue('userid')))->email;
            
            if (Auth::authenticate($email, $param['password'])) {
                $venda = new Venda($param['uniqid']);
                $venda->delete();

                TTransaction::close();
                TToast::show('success', 'Venda excluída', 'top center');
                $this->onReload();
            } else {
                throw new Exception("Autenticação falhou");
            }

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
        
    }

    public function onClear()
    {
        $this->form->clear();
        TSession::delValue('VendaList_filter');
        TSession::delValue('VendaList_data');
        $this->onReload();
    }

    /**
     * Gera relatório de vendas
     * @param mixed $param 
     * @return void 
     */
    public function onReportVendas($param)
    {
        $criteria = new TCriteria;
        $criteria->setProperty('order', 'created_at');
        $criteria->setProperty('direction', 'desc');

        if (TSession::getValue('VendaList_filter')) {
            foreach (TSession::getValue('VendaList_filter') as $filter) {
                $criteria->add($filter);
            }
        }

        try {
            TTransaction::open(DATABASE_FILENAME);

            $repo = new TRepository('Venda');
            $vendas = $repo->load($criteria);

            if ($vendas) {

                # Largura das colunas
                $widths = [40, 100, 150, 70, 70, 80, 70];
                $table = new TTableWriterPDF($widths);
                $table->style = 'width: 100%;';

                if (!empty($table)) {
                    $table->addStyle('header', 'Helvetica', '16', 'B', '#000000', '#ffffff', 'none');
                    $table->addStyle('title', 'Helvetica', '9', 'B', '#000000', '#d3d3d3');
                    $table->addStyle('linha_par', 'Helvetica', '9', '', '#000000', '#e3e3e3');
                    $table->addStyle('linha_impar', 'Helvetica', '9', '', '#000000', '#ffffff');
                }

                # Cabeçalho
                $table->addRow();
                $table->addCell('Vendas', 'center', 'header', 7);
                $table->addRow();
                $table->addCell('Nº', 'center', 'title');
                $table->addCell('DATA DA VENDA', 'center', 'title');
                $table->addCell('CLIENTE', 'center', 'title');
                $table->addCell('VALOR', 'center', 'title');
                $table->addCell('LUCRO', 'center', 'title');
                $table->addCell('PAGAMENTO', 'center', 'title');
                $table->addCell('SITUAÇÃO', 'center', 'title');

                $color = false;
                $i = 1;
                foreach ($vendas as $venda) {
                    $style = $color ? 'linha_par' : 'linha_impar';

                    $table->addRow();
                    $table->addCell($i, 'center', $style);
                    $table->addCell(date_format(date_create($venda->created_at), 'd/m/Y H:i:s'), 'center', $style);
                    $table->addCell($venda->cliente, 'center', $style);
                    $table->addCell(number_format($venda->valor_final, 2, ',', '.'), 'center', $style);
                    $table->addCell(number_format($venda->lucro, 2, ',', '.'), 'center', $style);
                    $table->addCell($venda->formaPagamento, 'center', $style);
                    $table->addCell($venda->situacao, 'center', $style);

                    $color = !$color;
                    $i++;
                }

                $output = 'app/output/relatorio_vendas.pdf';

                if (!file_exists($output) or is_writable($output)) {
                    $table->save($output);
                    parent::openFile($output);
                } else {
                    throw new Exception("Permissão negada", $output);
                }
            }

            TTransaction::close();
            $this->form->setData(TSession::getValue('VendaList_data'));
        } catch (Exception $ex) {
            new TMessage('error', $ex->getMessage());
        }
    }

    /**
     * Gera relatório de cobrança
     * @param mixed $param 
     * @return void 
     */
    public function onReportCobranca($param)
    {
        $criteria = new TCriteria;
        $criteria->setProperty('order', 'created_at');
        $criteria->setProperty('direction', 'desc');

        if (TSession::getValue('VendaList_filter')) {
            foreach (TSession::getValue('VendaList_filter') as $filter) {
                $criteria->add($filter);
            }
        }

        try {
            TTransaction::open(DATABASE_FILENAME);

            $repo = new TRepository('Venda');
            $vendas = $repo->load($criteria);

            if ($vendas) {

                # Largura das colunas
                $widths = [40, 100, 100, 60, 60, 60, 100];
                $table = new TTableWriterPDF($widths);
                $table->style = 'width: 100%;';

                if (!empty($table)) {
                    $table->addStyle('header', 'Helvetica', '16', 'B', '#000000', '#ffffff', 'none');
                    $table->addStyle('title', 'Helvetica', '9', 'B', '#000000', '#d3d3d3');
                    $table->addStyle('linha_par', 'Helvetica', '9', '', '#000000', '#e3e3e3');
                    $table->addStyle('linha_impar', 'Helvetica', '9', '', '#000000', '#ffffff');
                }

                # Cabeçalho
                $table->addRow();
                $table->addCell('Cobrança', 'center', 'header', 7);
                $table->addRow();
                $table->addCell('Nº', 'center', 'title');
                $table->addCell('DATA DA VENDA', 'center', 'title');
                $table->addCell('CLIENTE', 'center', 'title');
                $table->addCell('VALOR', 'center', 'title');
                $table->addCell('PAGO', 'center', 'title');
                $table->addCell('DEVIDO', 'center', 'title');
                $table->addCell('CIDADE', 'center', 'title');

                $color = false;
                $i = 1;
                foreach ($vendas as $venda) {
                    $style = $color ? 'linha_par' : 'linha_impar';

                    $table->addRow();
                    $table->addCell($i, 'center', $style);
                    $table->addCell(date_format(date_create($venda->created_at), 'd/m/Y H:i:s'), 'center', $style);
                    $table->addCell($venda->cliente, 'center', $style);
                    $table->addCell(number_format($venda->valor_final, 2, ',', '.'), 'center', $style);
                    $table->addCell(number_format($venda->valor_pago, 2, ',', '.'), 'center', $style);
                    $table->addCell(number_format($venda->valor_devido, 2, ',', '.'), 'center', $style);
                    $table->addCell($venda->cliente->cidade, 'center', $style);

                    $color = !$color;
                    $i++;
                }

                $output = 'app/output/relatorio_cobranca.pdf';

                if (!file_exists($output) or is_writable($output)) {
                    $table->save($output);
                    parent::openFile($output);
                } else {
                    throw new Exception("Permissão negada", $output);
                }
            }

            TTransaction::close();
            $this->form->setData(TSession::getValue('VendaList_data'));
        } catch (Exception $ex) {
            new TMessage('error', $ex->getMessage());
        }
    }

    /**
     * Gera relatório de produtos
     * @param mixed $param 
     * @return void 
     */
    public function onReportProdutoVendido($param)
    {
        $criteria = new TCriteria;

        if (TSession::getValue('VendaList_filter')) {
            foreach (TSession::getValue('VendaList_filter') as $filter) {
                $criteria->add($filter);
            }
        }

        try {
            TTransaction::open(DATABASE_FILENAME);

            $vendas = Venda::getIndexedArray('uniqid', 'uniqid', $criteria);
            $itens = ItemVenda::where('uniqid_venda', 'IN', $vendas)->groupBy('id_produto, preco_custo')->sumByAnd('quantidade', 'quantidade')->sumBy('preco_total');

            if ($itens) {
                # Largura das colunas
                $widths = [40, 120, 100, 60, 60, 60];
                $table = new TTableWriterPDF($widths);
                $table->style = 'width: 100%;';

                if (!empty($table)) {
                    $table->addStyle('header', 'Helvetica', '16', 'B', '#000000', '#ffffff', 'none');
                    $table->addStyle('title', 'Helvetica', '9', 'B', '#000000', '#d3d3d3');
                    $table->addStyle('linha_par', 'Helvetica', '9', '', '#000000', '#e3e3e3');
                    $table->addStyle('linha_impar', 'Helvetica', '9', '', '#000000', '#ffffff');
                }

                # Cabeçalho
                $table->addRow();
                $table->addCell('Produtos Vendidos', 'center', 'header', 5);
                $table->addRow();
                $table->addCell('Nº', 'center', 'title');
                $table->addCell('PRODUTO', 'center', 'title');
                $table->addCell('QUANTIDADE', 'center', 'title');
                $table->addCell('TOTAL', 'center', 'title');
                $table->addCell('CUSTO', 'center', 'title');
                $table->addCell('LUCRO', 'center', 'title');

                $color = false;
                $i = 1;
                foreach ($itens as $item) {
                    $style = $color ? 'linha_par' : 'linha_impar';
                    $custo = $item->preco_custo * $item->quantidade;
                    $lucro = $item->preco_total - $custo;

                    $table->addRow();
                    $table->addCell($i, 'center', $style);
                    $table->addCell((new Produto($item->id_produto))->nome, 'center', $style);
                    $table->addCell($item->quantidade, 'center', $style);
                    $table->addCell($item->preco_total, 'center', $style);
                    $table->addCell(number_format($custo, 2, ',', '.'), 'center', $style);
                    $table->addCell(number_format($lucro, 2, ',', '.'), 'center', $style);

                    $color = !$color;
                    $i++;
                }

                $output = 'app/output/relatorio_produtos_vendidos.pdf';

                if (!file_exists($output) or is_writable($output)) {
                    $table->save($output);
                    parent::openFile($output);
                } else {
                    throw new Exception("Permissão negada", $output);
                }
            }

            TTransaction::close();
            $this->form->setData(TSession::getValue('VendaList_data'));
        } catch (Exception $ex) {
            new TMessage('error', $ex->getMessage());
        }
    }
}
