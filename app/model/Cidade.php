<?php

use Adianti\Database\TRecord;

class Cidade extends TRecord
{
    const TABLENAME = 'cidade';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    /**
     * 
     * @var Estado Objeto da classe Estado.
     */
    private $estado;

    public function __construct($id = null)
    {
        parent::__construct($id);

        parent::addAttribute('nome');
        parent::addAttribute('id_estado');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }

    public function __toString()
    {
        return $this->nome;
    }

    public function get_estado()
    {
        if (empty($this->estado)) {
            $this->estado = new Estado($this->id_estado);
        }

        return $this->estado;
    }
}
