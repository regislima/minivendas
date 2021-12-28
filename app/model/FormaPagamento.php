<?php

use Adianti\Database\TRecord;

class FormaPagamento extends TRecord 
{
    const TABLENAME = 'forma_pagamento';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null)
    {
        parent::__construct($id);

        parent::addAttribute('forma');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }

    public function __toString()
    {
        return $this->forma;
    }
}
