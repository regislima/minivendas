<?php

use Adianti\Database\TRecord;

class Estado extends TRecord
{
    const TABLENAME = 'estado';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null)
    {
        parent::__construct($id);

        parent::addAttribute('sigla');
        parent::addAttribute('nome');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }

    public function __toString()
    {
        return $this->nome;
    }
}