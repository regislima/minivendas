<?php

use Adianti\Database\TRecord;

class Despesa extends TRecord 
{
    const TABLENAME = 'despesa';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    private $formaPagamento;

    public function __construct($id = null)
    {
        parent::__construct($id);

        parent::addAttribute('valor');
        parent::addAttribute('data_resgate_cheque');
        parent::addAttribute('descricao');
        parent::addAttribute('id_forma_pagamento');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }

    public function get_formaPagamento()
    {
        if (empty($this->formaPagamento)) {
            $this->formaPagamento = new FormaPagamento($this->id_forma_pagamento);
        }

        return $this->formaPagamento;
    }
}
