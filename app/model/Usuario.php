<?php

use Adianti\Database\TRecord;

class Usuario extends TRecord
{
    const TABLENAME = 'usuarios';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    /** @var array - Objetos Venda */
    private $itens = [];

    public function __construct($id = null)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
        parent::addAttribute('sobrenome');
        parent::addAttribute('email');
        parent::addAttribute('senha');
        parent::addAttribute('nivel');
        parent::addAttribute('ativo');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }

    public function __toString()
    {
        return $this->nome . ' ' . $this->sobrenome;
    }

    public function load($id)
    {
        $this->itens = parent::loadComposite('Venda', 'id_usuario', $id);
        return parent::load($id);
    }
}
