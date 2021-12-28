<?php

use Adianti\Database\TRecord;

class Cliente extends TRecord
{
    const TABLENAME = 'cliente';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    /**
     * 
     * @var Cidade Objeto da classe Cidade.
     */
    private $cidade;

    public function __construct($id = null)
    {
        parent::__construct($id);

        parent::addAttribute('nome');
        parent::addAttribute('endereco');
        parent::addAttribute('telefone');
        parent::addAttribute('id_cidade');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }

    public function __toString()
    {
        return $this->nome;
    }

    public function get_cidade()
    {
        if (empty($this->cidade)) {
            $this->cidade = new Cidade($this->id_cidade);
        }

        return $this->cidade;
    }
}
