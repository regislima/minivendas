<?php

use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TToast;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Wrapper\BootstrapDatagridWrapper;

class DashAdmin extends TPage
{
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

            $vbox = new TVBox;
            $vbox->style = 'width: 100%;';
            
            $div = new TElement('div');
            $div->class = "row";
            
            $indicadorTotalProdutos = new THtmlRenderer('app/resources/info-box.html');
            $indicadorTotalClientes = new THtmlRenderer('app/resources/info-box.html');
            $indicadorTotalVendas = new THtmlRenderer('app/resources/info-box.html');
            $indicadorTotalDespesas = new THtmlRenderer('app/resources/info-box.html');
            $indicadorTotalCidades = new THtmlRenderer('app/resources/info-box.html');
            $indicadorTotalUsuarios = new THtmlRenderer('app/resources/info-box.html');

            $totalProdutos = $this->getTotalProdutos();
            $totalClientes = $this->getTotalClientes();
            $totalVendas = $this->getTotalVendas();
            $totalDespesas = $this->getTotalDespesas();
            $totalCidades = $this->getTotalCidades();
            $totalUsuarios = $this->getTotalUsuarios();
            
            $indicadorTotalProdutos->enableSection('main', [
                'title' => 'Produtos Cadastrados',
                'icon' => 'gift',
                'background' => 'yellow',
                'value' => $totalProdutos
            ]);
            
            $indicadorTotalClientes->enableSection('main', [
                'title' => 'Clientes Cadastrados',
                'icon' => 'user-friends',
                'background' => 'orange',
                'value' => $totalClientes
            ]);

            $indicadorTotalVendas->enableSection('main', [
                'title' => 'Vendas Realizadas',
                'icon' => 'handshake',
                'background' => 'purple',
                'value' => $totalVendas
            ]);

            $indicadorTotalDespesas->enableSection('main', [
                'title' => 'Despesas Realizadas',
                'icon' => 'file-invoice-dollar',
                'background' => 'red',
                'value' => $totalDespesas
            ]);

            $indicadorTotalCidades->enableSection('main', [
                'title' => 'Cidades Cadastradas',
                'icon' => 'city',
                'background' => 'purple',
                'value' => $totalCidades
            ]);

            $indicadorTotalUsuarios->enableSection('main', [
                'title' => 'Usuários Cadastrados',
                'icon' => 'user-tie',
                'background' => 'purple',
                'value' => $totalUsuarios
            ]);
            
            $div->add($i1 = TElement::tag('div', $indicadorTotalProdutos));
            $div->add($i2 = TElement::tag('div', $indicadorTotalClientes));
            $div->add($i3 = TElement::tag('div', $indicadorTotalVendas));
            $div->add($i4 = TElement::tag('div', $indicadorTotalDespesas));
            $div->add($i5 = TElement::tag('div', $indicadorTotalCidades));
            $div->add($i6 = TElement::tag('div', $indicadorTotalUsuarios));
            
            $i1->class = 'col-sm-6';
            $i2->class = 'col-sm-6';
            $i3->class = 'col-sm-6';
            $i4->class = 'col-sm-6';
            $i5->class = 'col-sm-6';
            $i6->class = 'col-sm-6';

            # Datagrid
            $datagrid = new BootstrapDatagridWrapper(new TDataGrid);
            $datagrid->setId('produtos_pouco_estoque');
            $datagrid->style = "width: 100%; margin-bottom: 10px;";

            # Colunas do datagrid
            $datagrid->addColumn(new TDataGridColumn('id', 'ProdID', 'center', '20%'));
            $datagrid->addColumn(new TDataGridColumn('nome', 'Produto', 'left', '40%'));
            $datagrid->addColumn(new TDataGridColumn('estoque', 'Estoque Atual', 'center', '20%'));
            $datagrid->addColumn(new TDataGridColumn('estoque_minimo', 'Estoque Mínimo', 'center', '20%'));
            $datagrid->createModel();
            
            TTransaction::open(DATABASE_FILENAME);
            $conn = TTransaction::get();
            $prods = $conn->query('SELECT * FROM produto where estoque <= estoque_minimo', PDO::FETCH_OBJ)->fetchAll();
            TTransaction::close();

            if (count($prods) > 0) {
                foreach ($prods as $obj) {
                    $datagrid->addItem($obj);
                }

                TToast::show('warning', 'Existem produtos com pouco estoque. Fique atento', 'top center', 'far:check-circle');
            }

            $panel = new TPanelGroup('<h2>PRODUTOS COM POUCO ESTOQUE</h2>');
            $panel->style = 'margin-top: 20px;';
            $panel->getHeader()->class = 'text-center';
            $panel->getHeader()->style = 'font-weight: bold;';
            $panel->getBody()->style = 'overflow-x: auto;';
            $panel->add($datagrid);
            
            $vbox->add($div);

            // Caso tenha produto com pouco estoque
            if ($datagrid->getItems()) {
                $vbox->add($panel);
            }
            
            parent::add($vbox);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private function getTotalProdutos()
    {
        TTransaction::open(DATABASE_FILENAME);
        $totalProdutos = Produto::countObjects();
        TTransaction::close();

        return $totalProdutos;
    }

    private function getTotalClientes()
    {
        TTransaction::open(DATABASE_FILENAME);
        $totalClientes = Cliente::countObjects();
        TTransaction::close();

        return $totalClientes;
    }

    private function getTotalVendas()
    {
        TTransaction::open(DATABASE_FILENAME);
        $totalVendas = Venda::countObjects();
        TTransaction::close();

        return $totalVendas;
    }

    private function getTotalDespesas()
    {
        TTransaction::open(DATABASE_FILENAME);
        $totalDespesas = Despesa::countObjects();
        TTransaction::close();

        return $totalDespesas;
    }

    private function getTotalCidades()
    {
        TTransaction::open(DATABASE_FILENAME);
        $cidades = Cidade::countObjects();
        TTransaction::close();

        return $cidades;
    }

    private function getTotalUsuarios()
    {
        TTransaction::open(DATABASE_FILENAME);
        $usuarios = Usuario::countObjects();
        TTransaction::close();

        return $usuarios;
    }
}
