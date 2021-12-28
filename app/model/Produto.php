<?php

use Adianti\Database\TRecord;

class Produto extends TRecord
{
    const TABLENAME = 'produto';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null)
    {
        parent::__construct($id);

        parent::addAttribute('nome');
        parent::addAttribute('estoque');
        parent::addAttribute('estoque_minimo');
        parent::addAttribute('preco_custo');
        parent::addAttribute('preco_venda');
        parent::addAttribute('obs');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }
}
