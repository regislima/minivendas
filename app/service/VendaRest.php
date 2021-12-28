<?php

use Adianti\Database\TTransaction;
use Adianti\Service\AdiantiRecordService;

class VendaRest extends AdiantiRecordService
{
    const DATABASE = DATABASE_FILENAME;
    const ACTIVE_RECORD = 'Venda';

    public function store($request)
    {
        try {
            TTransaction::open(self::DATABASE);

            # Gravando a venda
            $request['venda']['transmitida'] = 1;
            Venda::create($request['venda']);

            # Gravando os itens
            foreach ($request['itens'] as $item) {
                ItemVenda::create($item);
            }

            # Atualizando o estoque dos produtos
            foreach ($request['produto'] as $id => $estoque) {
                $prod = new Produto($id);
                $prod->estoque = $estoque;
                $prod->store();
            }

            # Gravando os pagamentos, se existir
            if (isset($request['pagamentos'])) {
                foreach ($request['pagamentos'] as $pagamento) {
                    VendaPagamento::create($pagamento);
                }
            }
            
            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
        }
    }
}