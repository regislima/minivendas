<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TMinValueValidator;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TToast;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class VendaForm extends TPage
{
    private $form;
    private $datagrid;
    
    public function __construct()
    {
        try {
            parent::__construct();

            # Verifica se o usuário tem permissão
            if (!Helpers::verify_level_user(2)) {
                new TMessage('info', 'Você não tem permissão para acessar essa página');

                if (!TSession::getValue('logged')) {
                    LoginForm::onLogout();
                }

                exit;
            }

            $this->form = new BootstrapFormBuilder('form_cadastro_venda');
            $this->form->setFormTitle('Realizar Venda');
            $this->form->setFieldSizes('100%');
            $this->form->setProperty('style', 'margin: 0; border: 0;');
            $this->form->setClientValidation(true);
            
            # Campos Detalhe
            $uniqId = new THidden('uniqid');
            $idCliente = new THidden('id_cliente');
            $idFormaPagamento = new THidden('id_forma_pagamento');

            $produto_detalhe_uniqid = new THidden('produto_detalhe_uniqid');
            
            $criteria = new TCriteria;
            $criteria->add(new TFilter('estoque', '>', 0));
            $produto_detalhe_produto_id = new TDBUniqueSearch('produto_detalhe_produto_id', DATABASE_FILENAME, 'Produto', 'id', 'nome', null, $criteria);
            $produto_detalhe_produto_id->setMinLength(2);
            $produto_detalhe_produto_id->setChangeAction(new TAction([$this, 'onSelecionaProduto'], ['static' => '1']));

            $produto_detalhe_preco = new TEntry('produto_detalhe_preco');
            $produto_detalhe_preco->setNumericMask(2, ',', '.', true);
            $produto_detalhe_preco->setEditable(false);

            $produto_detalhe_quantidade = new TEntry('produto_detalhe_quantidade');
            $produto_detalhe_quantidade->addValidation('Quantidade', new TMinValueValidator, [1]);
            $produto_detalhe_quantidade->inputmode = 'numeric';

            $produto_detalhe_desconto = new TEntry('produto_detalhe_desconto');
            $produto_detalhe_desconto->setNumericMask(2, ',', '.', true);
            $produto_detalhe_desconto->addValidation('Desconto', new TMinValueValidator, [0]);
            $produto_detalhe_desconto->inputmode = 'numeric';

            $addProduto = TButton::create('add_produto', [$this, 'onAddProduto'], 'Registrar', 'fa:plus-circle green');
            $addProduto->getAction()->setParameter('static', '1');

            $this->form->addFields(
                [$produto_detalhe_uniqid],
                [$uniqId],
                [$idCliente],
                [$idFormaPagamento]
            );
            
            $this->form->addFields(
                [new TLabel('Produto*'), $produto_detalhe_produto_id],
                [new TLabel('Preço*'), $produto_detalhe_preco],
                [new TLabel('Quantidade*'), $produto_detalhe_quantidade],
                [new TLabel('Desconto'), $produto_detalhe_desconto],
                [new TLabel(''), $addProduto]
            )->layout = ['col-sm-4', 'col-sm-2', 'col-sm-2', 'col-sm-2', 'col-sm-2'];

            # Datagrid
            $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
            $this->datagrid->setHeight(250);
            $this->datagrid->makeScrollable();
            $this->datagrid->setId('produtos_lista');
            $this->datagrid->generateHiddenFields();
            $this->datagrid->style = "min-width: 700px; width: 100%; margin-bottom: 10px;";

            $formataValor = function($value) {
                if (is_numeric($value)) {
                    return number_format($value, 2, ',', '.');
                }

                return $value;
            };

            # Colunas do datagrid
            $colUniq = new TDataGridColumn('uniqid', 'Uniqid', 'center', '10%');
            $colUniq->setVisibility(false);
            
            $colProdutoID = new TDataGridColumn('produto_id', 'ProdID', 'center', '5%');

            $colProdutoNome = new TDataGridColumn('produto_id', 'Produto', 'left', '20%');
            $colProdutoNome->setTransformer(function($value){
                return Produto::findInTransaction(DATABASE_FILENAME, $value)->nome;
            });
            
            $colProdutoPreco = new TDataGridColumn('preco_venda', 'Preço', 'center', '15%');
            $colProdutoPreco->setTransformer($formataValor);

            $colProdutoQuantidade = new TDataGridColumn('quantidade', 'Quantidade', 'center', '15%');
            
            $colProdutoDesconto = new TDataGridColumn('desconto', 'Desconto', 'center', '10%');
            $colProdutoDesconto->setTransformer($formataValor);

            $colProdutoLucro = new TDataGridColumn('lucro', 'Lucro', 'center', '10%');
            $colProdutoLucro->setTransformer($formataValor);
            
            $colSubtotal = new TDataGridColumn('=({quantidade} * {preco_venda}) - {desconto}', 'Subtotal', 'right', '15%');
            $colSubtotal->enableTotal('sum', 'R$', 2);
            $colSubtotal->setTransformer($formataValor);
            
            // Editar e Deletar do datagrid
            $acaoEditar = new TDataGridAction([$this, 'onEditItem']);
            $acaoEditar->setFields(['uniqid', '*']);
            $acaoDeletar = new TDataGridAction([$this, 'onDeleteItem']);
            $acaoDeletar->setField('uniqid');
            
            $this->datagrid->addColumn($colUniq);
            $this->datagrid->addColumn($colProdutoID);
            $this->datagrid->addColumn($colProdutoNome);
            $this->datagrid->addColumn($colProdutoPreco);
            $this->datagrid->addColumn($colProdutoQuantidade);
            $this->datagrid->addColumn($colProdutoDesconto);
            $this->datagrid->addColumn($colProdutoLucro);
            $this->datagrid->addColumn($colSubtotal);
            $this->datagrid->addAction($acaoEditar, 'Editar', 'far:edit blue');
            $this->datagrid->addAction($acaoDeletar, 'Excluir', 'far:trash-alt red');
            $this->datagrid->createModel();
            
            $panel = new TPanelGroup();
            $panel->class = 'mt-3';
            $panel->add($this->datagrid);
            $panel->getBody()->style = 'overflow-x: auto;';
            $this->form->addContent([$panel]);
            
            $this->form->addAction('Pagamento',  new TAction([$this, 'onPagamento'], ['static' => '1']), 'fa:save green');
            $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
            $this->form->addActionLink('Voltar', new TAction(['VendaList', 'onReload']), 'fa:arrow-left');

            $vbox = new TVBox;
            $vbox->style = 'width: 100%;';
            $vbox->add($this->form);

            parent::add($vbox);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    // Adiciona o preço automaticamente
    public function onSelecionaProduto($param)
    {
        if (!empty($param['produto_detalhe_produto_id']) ) {
            TTransaction::open(DATABASE_FILENAME);
            $produto = new Produto($param['produto_detalhe_produto_id']);
            TTransaction::close();

            TForm::sendData('form_cadastro_venda', (object) ['produto_detalhe_preco' => number_format($produto->preco_venda, 2, ',', '.')]);
        } else {
            TForm::sendData('form_cadastro_venda', (object) ['produto_detalhe_preco' => '']);
        }
    }

    public function onAddProduto($param)
    {
        try {
            $this->form->validate();
            $dados = $this->form->getData();

            TTransaction::open(DATABASE_FILENAME);

            if (!$dados->produto_detalhe_produto_id) {
                throw new Exception('Informe um produto.');
            }

            if (!$dados->produto_detalhe_desconto) {
                $dados->produto_detalhe_desconto = 0;
            }

            $prod = new Produto($dados->produto_detalhe_produto_id);
            // Verificando se a quantidade é maior que o estoque do produto escolhido
            if ($dados->produto_detalhe_quantidade > $prod->estoque) {
                throw new Exception("Quantidade maior que o estoque do produto. <br>Estoque: {$prod->estoque}");
            }

            // Verifica se a venda está sem ou lucro negativo
            if ((($dados->produto_detalhe_quantidade * $dados->produto_detalhe_preco) - $dados->produto_detalhe_desconto) <= ($dados->produto_detalhe_quantidade * $prod->preco_custo)) {
                TToast::show('warning', 'Venda sem lucro ou negativo!', 'top center');
            }

            $uniqID = !empty($dados->produto_detalhe_uniqid) ? $dados->produto_detalhe_uniqid : Helpers::uniqidReal();
            $gridValores = [
                'uniqid' => $uniqID,
                'produto_id' => $dados->produto_detalhe_produto_id,
                'preco_venda' => $dados->produto_detalhe_preco,
                'quantidade' => $dados->produto_detalhe_quantidade,
                'desconto' => $dados->produto_detalhe_desconto,
                'lucro' => (($dados->produto_detalhe_quantidade * $dados->produto_detalhe_preco) - $dados->produto_detalhe_desconto) - ($dados->produto_detalhe_quantidade * $prod->preco_custo)
            ];

            TTransaction::close();

            $linha = $this->datagrid->addItem((object) $gridValores);
            $linha->id = $uniqID;
            TDataGrid::replaceRowById('produtos_lista', $uniqID, $linha);

            # limpa os campos
            $dados->produto_detalhe_uniqid = '';
            $dados->produto_detalhe_produto_id = '';
            $dados->produto_detalhe_preco = '';
            $dados->produto_detalhe_quantidade = '';
            $dados->produto_detalhe_desconto = '';

            TForm::sendData('form_cadastro_venda', $dados, false, false);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onEditItem($param)
    {
        $data = new stdClass;
        $data->produto_detalhe_uniqid = $param['uniqid'];
        $data->produto_detalhe_produto_id = $param['produto_id'];
        $data->produto_detalhe_preco = number_format($param['preco_venda'], 2, ',', '.');
        $data->produto_detalhe_quantidade = $param['quantidade'];
        $data->produto_detalhe_desconto = number_format($param['desconto'], 2, ',', '.');
        
        TForm::sendData('form_cadastro_venda', $data, false, false);
    }

    public function onEdit($param)
    {
        try {
            TTransaction::open(DATABASE_FILENAME);
            
            if (isset($param['key'])) {
                $venda = new Venda($param['key']);
                
                foreach ($venda->itens as $item) {
                    $grid = new stdClass;
                    $grid->uniqid = Helpers::uniqidReal(6);
                    $grid->produto_id = $item->id_produto;
                    $grid->preco_venda = $item->preco_venda;
                    $grid->quantidade = $item->quantidade;
                    $grid->desconto = $item->desconto;
                    $grid->lucro = (($item->quantidade * $item->preco_venda) - $item->desconto) - ($item->quantidade * $item->preco_custo);
 
                    $linha = $this->datagrid->addItem($grid);
                    $linha->id = $grid->uniqid;
                }
                
                $this->form->setData($venda);
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public static function onDeleteItem($param)
    {
        # limpa os campos
        $dados = new stdClass;
        $dados->produto_detalhe_uniqid = '';
        $dados->produto_detalhe_produto_id = '';
        $dados->produto_detalhe_preco = '';
        $dados->produto_detalhe_quantidade = 1;
        $dados->produto_detalhe_desconto = 0;
        
        TForm::sendData('form_cadastro_venda', $dados, false, false);
        TDataGrid::removeRowById('produtos_lista', $param['uniqid']);
    }

    public function onPagamento($param)
    {
        try {
            if (isset($param['produtos_lista_uniqid']) && count($param['produtos_lista_uniqid']) > 0) {
                TTransaction::open(DATABASE_FILENAME);

                $data = $this->form->getData();
                $venda = new Venda;
                $venda->fromArray((array) $data);

                // No caso de edição
                if ($venda->uniqid) {
                    $venda = new Venda($venda->uniqid);
                    $venda->tmp = $venda->itens;
                    $venda->clearItens();
                }
                    
                if (!empty($param['produtos_lista_produto_id'])) {
                    foreach ($param['produtos_lista_produto_id'] as $key => $item_id) {
                        $desconto = number_format(floatval($param['produtos_lista_desconto'][$key]), 2);
                        $quantidade = intval($param['produtos_lista_quantidade'][$key]);
                        $prod = new Produto($item_id);

                        # Verifica se o produto tem estoque suficiente
                        if ($prod->estoque < $param['produtos_lista_quantidade'][$key]) {
                            throw new Exception('Produto ' . $prod->nome . ' com estoque insuficiente.');
                        }

                        $venda->addItem($prod, $quantidade, $desconto);
                    }
                }
                
                TTransaction::close();
                TSession::setValue('venda', $venda);
                AdiantiCoreApplication::loadPage('PagamentoForm', null, ['register_state' => 'false']);
            } else {
                new TMessage('info', 'Nenhum produto foi adicionado');
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', 'Erro ao efetuar a venda: ' . $e->getMessage());
        }
    }

    public function onClear($param)
    {
        AdiantiCoreApplication::loadPage('VendaForm');
    }
}
