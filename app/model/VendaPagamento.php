<?php

use Adianti\Database\TRecord;

class VendaPagamento extends TRecord
{
    const TABLENAME = 'venda_pagamento';
    const PRIMARYKEY = 'uniqid';
    const IDPOLICY = 'serial';

    public function __construct($uniqid = null)
    {
        parent::__construct($uniqid);

        parent::addAttribute('uniqid_venda');
        parent::addAttribute('valor_pago');
        parent::addAttribute('created_at');
    }

    public function store()
    {
        $this->uniqid = !empty($this->uniqid) ? $this->uniqid : Helpers::uniqidReal();
        parent::store();
    }
}