<?php

use Adianti\Base\AdiantiStandardListTrait;
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridActionGroup;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class DespesaList extends TPage
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

        # Configurações do trait
        $this->setDatabase(DATABASE_FILENAME);
        $this->setActiveRecord('Despesa');
        $this->setDefaultOrder('id', 'desc');
        $this->addFilterField('created_at', '>=', 'despesa_de');
        $this->addFilterField('created_at', '<=', 'despesa_ate');
        $this->addFilterField('id_forma_pagamento', '=', 'id_forma_pagamento');

        $this->form = new BootstrapFormBuilder('form_despesa_lista');
        $this->form->setFormTitle('Busca de Despesa');

        # Campos do formulário
        $despesaDe = new TDate('despesa_de');
        $despesaDe->setMask('dd/mm/yyyy');
        $despesaDe->setDatabaseMask('yyyy-mm-dd');
        $despesaDe->style = 'width: 150px;';

        $despesaAte = new TDate('despesa_ate');
        $despesaAte->setMask('dd/mm/yyyy');
        $despesaAte->setDatabaseMask('yyyy-mm-dd');
        $despesaAte->style = 'width: 150px;';

        $formaPagamento = new TDBCombo('id_forma_pagamento', DATABASE_FILENAME, 'FormaPagamento', 'id', 'forma');
        $formaPagamento->style = 'width: 200px;';
        
        $this->form->addFields([new TLabel('Data (de)', null, null, null, '100%'), $despesaDe], [new TLabel('Data (até)', null, null, null, '100%'), $despesaAte]);
        $this->form->addFields([new TLabel('Forma de Pagamento', null, null, null, '100%'), $formaPagamento]);
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addActionLink('Novo', new TAction(['DespesaForm', 'onEdit']), 'fa:plus green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser');

        # Mantem o formulário preenchido
        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        # Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%;';
        $this->datagrid->disableDefaultClick();

        # Colunas do datagrid
        $despesaDetalhes = new TDataGridAction(['DespesaDetalhes', 'onDetalhes'], ['key' => '{id}', 'register_state' => 'false']);
        $despesaDetalhes->setLabel('Detalhes');
        $despesaDetalhes->setImage('fa:search green');
        
        $despesaEditar = new TDataGridAction(['DespesaForm', 'onEdit'], ['key' => '{id}']);
        $despesaEditar->setLabel('Editar');
        $despesaEditar->setImage('fa:edit blue');
        
        $despesaExcluir = new TDataGridAction([$this, 'onDelete'], ['key' => '{id}', 'register_state' => 'false']);
        $despesaExcluir->setLabel('Excluir');
        $despesaExcluir->setImage('fa:trash-alt red');
        
        $acoes = new TDataGridActionGroup('Ações ', 'fa:th');
        $acoes->addAction($despesaDetalhes);
        $acoes->addAction($despesaEditar);
        $acoes->addAction($despesaExcluir);
        
        $despesaID = new TDataGridColumn('id', 'Código', 'center', '10%');
        
        $despesaData = new TDataGridColumn('created_at', 'Data da Despesa', 'center', '30%');
        $despesaData->setTransformer(function($valor) {
            $data = new DateTime($valor);
            
            return $data->format('d/m/Y H:i:s');
        });

        $despesaValor = new TDataGridColumn('valor', 'Valor', 'center', '30%');
        $despesaValor->setTransformer(function($valor) {
            return number_format($valor, 2, ',', '.');
        });

        $despesaFormaPagamento = new TDataGridColumn('id_forma_pagamento', 'Forma de Pagamento', 'center', '30%');
        $despesaFormaPagamento->setTransformer(function($valor) {
            $forma = new FormaPagamento($valor);
            
            return $forma->forma;
        });

        # Adicionando colunas no datagrid
        $this->datagrid->addColumn($despesaID);
        $this->datagrid->addColumn($despesaData);
        $this->datagrid->addColumn($despesaValor);
        $this->datagrid->addColumn($despesaFormaPagamento);
        $this->datagrid->addActionGroup($acoes);

        $this->datagrid->createModel();

        # Paginação
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        # Adicionado datagrid e paginação dentro de um painel
        $painel = new TPanelGroup();
        $painel->add($this->datagrid)->style = 'overflow-x: auto;';
        $painel->addHeaderActionLink('Imprimr Relatório', new TAction([$this, 'onReport'], ['register_state' => 'false']), 'far:file-pdf red');
        $painel->addFooter($this->pageNavigation);

        $vbox = new TVBox;
        $vbox->style = 'width: 100%;';
        $vbox->add($this->form);
        $vbox->add($painel);

        parent::add($vbox);
    }

    public function onClear()
    {
        $this->form->clear();
    }

    /**
     * Gera relatório com todos os despesas cadastradas
     * @param mixed $param 
     * @return void 
     */
    public function onReport($param)
    {
        $data = TSession::getValue(__CLASS__ . '_filter_data');
        $dataDe = $data->despesa_de ?? null;
        $dataAte = $data->despesa_ate ?? null;
        $formaPagamento = $data->id_forma_pagamento ?? null;

        $criteria = new TCriteria;

        if ($dataDe) {
            $criteria->add(new TFilter('data_despesa', '>=', $dataDe));
        }

        if ($dataAte) {
            $criteria->add(new TFilter('data_despesa', '<=', $dataAte));
        }

        if ($formaPagamento) {
            $criteria->add(new TFilter('id_forma_pagamento', '=', $formaPagamento));
        }

        try {
            TTransaction::open(DATABASE_FILENAME);
            
            $repo = new TRepository('Despesa');
            $despesas = $repo->load($criteria);
            
            if ($despesas) {
                
                # Largura das colunas
                $widths = [40, 100, 100, 150];
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
                $table->addCell('Despesas', 'center', 'header', 4);
                $table->addRow();
                $table->addCell('ID', 'center', 'title');
                $table->addCell('DATA DA DESPESA', 'center', 'title');
                $table->addCell('VALOR', 'center', 'title');
                $table->addCell('FORMA DE PAGAMENTO', 'center', 'title');
                
                $color = false;
                foreach ($despesas as $despesa) {
                    $style = $color ? 'linha_par' : 'linha_impar';
                    $table->addRow();
                    $table->addCell($despesa->id, 'center', $style);
                    $table->addCell(date_format(date_create($despesa->data_despesa), 'd/m/Y'), 'center', $style);
                    $table->addCell(number_format($despesa->valor, 2, ',', '.'), 'center', $style);
                    $table->addCell($despesa->formaPagamento, 'center', $style);
                    
                    $color = !$color;
                }

                $output = 'app/output/relatorio_despesas.pdf';

                if (!file_exists($output) or is_writable($output)) {
                    $table->save($output);
                    parent::openFile($output);
                } else {
                    throw new Exception("Permissão negada", $output);
                }
                
            }
            
            TTransaction::close();
        } catch (Exception $ex) {
            new TMessage('error', $ex->getMessage());
        }
    }
}
