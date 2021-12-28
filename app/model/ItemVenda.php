<?php

use Adianti\Database\TRecord;

class ItemVenda extends TRecord 
{
    const TABLENAME = 'item_venda';
    const PRIMARYKEY = 'uniqid';
    const IDPOLICY = 'serial';

    /** @var Produto Objeto da classe Produto */
    private $produto;

    public function __construct($uniqid = null)
    {
        parent::__construct($uniqid);

        parent::addAttribute('quantidade');
        parent::addAttribute('preco_custo');
        parent::addAttribute('preco_venda');
        parent::addAttribute('preco_subtotal');
        parent::addAttribute('desconto');
        parent::addAttribute('preco_total');
        parent::addAttribute('uniqid_venda');
        parent::addAttribute('id_produto');
    }

    public function get_produto()
    {
        if (empty($this->produto)) {
            $this->produto = new Produto($this->id_produto);
        }

        return $this->produto;
    }

    public function store()
    {
        $this->uniqid = !empty($this->uniqid) ? $this->uniqid : Helpers::uniqidReal();
        parent::store();
    }
}
