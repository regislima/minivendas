<?php

use Adianti\Database\TRecord;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TSelect;

class Venda extends TRecord 
{
    const TABLENAME = 'venda';
    const PRIMARYKEY = 'uniqid';
    const IDPOLICY = 'serial';

    /** @var Cliente - Objeto da classe Cliente */
    private $cliente;

    /** @var Usuario - Objeto da classe Usuario */
    private $usuario;

    /** @var array - Objetos ItemVenda */
    private $itens = [];

    /** @var array - Objetos VendaPagamento */
    private $pagamentos = [];

    /** @var FormaPagamento - Objeto FormaPagamento */
    private $formaPagamento;

    public function __construct($uniqid = null)
    {
        parent::__construct($uniqid);

        parent::addAttribute('valor_final');
        parent::addAttribute('valor_pago');
        parent::addAttribute('valor_devido');
        parent::addAttribute('situacao');
        parent::addAttribute('lucro');
        parent::addAttribute('transmitida');
        parent::addAttribute('id_cliente');
        parent::addAttribute('id_usuario');
        parent::addAttribute('id_forma_pagamento');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }

    public function get_cliente()
    {
        if (empty($this->cliente)) {
            $this->cliente = new Cliente($this->id_cliente);
        }

        return $this->cliente;
    }

    public function get_usuario()
    {
        if (empty($this->usuario)) {
            $this->usuario = new Usuario($this->id_usuario);
        }

        return $this->usuario;
    }

    public function get_formaPagamento()
    {
        if (empty($this->formaPagamento)) {
            $this->formaPagamento = new FormaPagamento($this->id_forma_pagamento);
        }

        return $this->formaPagamento;
    }

    /**
     * Adiciona um produto a venda
     * 
     * @param Produto $produto Objeto da classe Produto
     * @param int $quantidade Quantidade de produtos
     * @return void 
     */
    public function addItem(Produto $produto, int $quantidade, float $desconto)
    {
        $itemVenda = new ItemVenda;
        $itemVenda->id_produto = $produto->id;
        $itemVenda->quantidade = $quantidade;
        $itemVenda->preco_custo = $produto->preco_custo;
        $itemVenda->preco_venda = $produto->preco_venda;
        $itemVenda->preco_subtotal = $quantidade * $produto->preco_venda;
        $itemVenda->desconto = $desconto;
        $itemVenda->preco_total = $itemVenda->preco_subtotal - $desconto;

        array_push($this->itens, $itemVenda);
    }

    /**
     * Adiciona um pagamento a venda
     * @param VendaPagamento $vp Objeto da classe VendaPagamento
     * @return void 
     */
    public function addPagamento(VendaPagamento $vp)
    {
        $vp->uniqid_venda = $this->uniqid;
        array_push($this->pagamentos, $vp);
    }

    /**
     * Remove todos os itens do array
     * @return void 
     */
    public function clearItens()
    {
        $this->itens = [];
    }

    /**
     * Remove todos os pagamentos do array
     * @return void 
     */
    public function clearPagamentos()
    {
        $this->pagamentos = [];
    }

    /**
     * Calcula o valor apurado da venda
     * @return float 
     */
    public function calculaValorTotal(): float
    {
        $valorApurado = 0;

        foreach ($this->itens as $item) {
            $valorApurado += $item->preco_total;
        }

        return $valorApurado;
    }

    /**
     * Calcula o valor de custo da venda
     * @return float 
     */
    public function calculaValorCusto(): float
    {
        $custo = 0;

        foreach ($this->itens as $item) {
            $custo += ($item->quantidade * $item->preco_custo);
        }

        return $custo;
    }

    /**
     * Calcula o valor que foi pago da venda
     * @return float 
     */
    public function calculaValorPago(): float
    {
        $valor = 0;

        foreach ($this->pagamentos as $item) {
            $valor += $item->valor_pago;
        }

        return $valor;
    }

    /**
     * Repõe o estoque de produtos em caso de edição ou exclusão da venda
     * @return void 
     */
    public function reporEstoque()
    {
        foreach ($this->tmp as $item) {
            $prod = new Produto($item->id_produto);
            $prod->estoque += $item->quantidade;
            $prod->store();
        }

        $this->deleteComposite('ItemVenda', 'uniqid_venda', $this->uniqid);
    }

    /**
     * Retorna todos os itens da venda
     * 
     * @return array Vetor de objetos da classe ItemVenda
     */
    public function get_itens()
    {
        return $this->itens;
    }

    /**
     * Retorna todos os pagamentos da venda
     * 
     * @return array Vetor de objetos da classe VendaPagamento
     */
    public function get_pagamentos()
    {
        return $this->pagamentos;
    }

    public function store()
    {
        if (count($this->itens) > 0) {
            $this->uniqid = !empty($this->uniqid) ? $this->uniqid : Helpers::uniqidReal();
            $this->valor_final = $this->calculaValorTotal();
            $this->lucro = $this->valor_final - $this->calculaValorCusto();
            $this->id_usuario = TSession::getValue('userid');

            # Caso a venda foi dinheiro (a vista)
            if ($this->id_forma_pagamento == 1) {
                $this->valor_pago = $this->valor_final;
                $this->valor_devido = 0;
            } else {
                # Se forma de pagamento for fiado ou cheque
                # Caso tenha algum pagamento
                if (count($this->pagamentos) > 0) {
                    $this->valor_pago = $this->calculaValorPago();
                    $this->valor_devido = $this->valor_final - $this->valor_pago;
                } else {
                    $this->valor_pago = 0;
                    $this->valor_devido = $this->valor_final;
                }
            }

            $this->situacao = ($this->valor_devido == 0 ? 'Pago' : 'Devendo'); 
            
            parent::store();
            parent::saveComposite('ItemVenda', 'uniqid_venda', $this->uniqid, $this->itens);
            
            if (count($this->pagamentos) > 0) {
                parent::saveComposite('VendaPagamento', 'uniqid_venda', $this->uniqid, $this->pagamentos);
            }
        } else {
            $this->deleteComposite('ItemVenda', 'uniqid_venda', $this->uniqid);
            parent::store();
        }
    }

    public function load($uniqid)
    {
        $this->itens = parent::loadComposite('ItemVenda', 'uniqid_venda', $uniqid);
        $this->pagamentos = parent::loadComposite('VendaPagamento', 'uniqid_venda', $uniqid);
        return parent::load($uniqid);
    }

    public function delete($uniqid = null)
    {
        $uniqid = isset($uniqid) ? $uniqid : $this->uniqid;

        parent::deleteComposite('VendaPagamento', 'uniqid_venda', $uniqid);
        parent::deleteComposite('ItemVenda', 'uniqid_venda', $uniqid);
        parent::delete();
    }
}
