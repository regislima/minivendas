<?php

use Adianti\Database\TTransaction;
use Adianti\Service\AdiantiRecordService;
use Adianti\Widget\Dialog\TMessage;

class ProdutoRest extends AdiantiRecordService
{
    const DATABASE = DATABASE_FILENAME;
    const ACTIVE_RECORD = 'Produto';

    public function updateEstoque($request)
    {
        try {
            TTransaction::open(self::DATABASE);

            $prod = new Produto($request['id']);
            $prod->estoque = $request['estoque'];
            $prod->save();

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}