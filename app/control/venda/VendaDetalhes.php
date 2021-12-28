<?php

use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Template\THtmlRenderer;

class VendaDetalhes extends TPage
{
    public function __construct()
    {
        parent::__construct();

        # Verifica se o usuário tem permissão
        if (!Helpers::verify_level_user(1) && !Helpers::verify_level_user(2)) {
            new TMessage('info', 'Você não tem permissão para acessar essa página');

            if (!TSession::getValue('logged')) {
                LoginForm::onLogout();
            }

            exit;
        }

        parent::setTargetContainer('adianti_right_panel');
    }

    public function onDetalhes($param)
    {
        try {
            TTransaction::open(DATABASE_FILENAME);
            $venda = new Venda($param['key']);            
            
            $html = new THtmlRenderer('app/resources/venda_detalhes.html');
            $html->enableSection('main', [
                'uniqid' => $venda->uniqid,
                'created_at' => $venda->created_at,
                'valor_final' => $venda->valor_final,
                'valor_pago' => $venda->valor_pago,
                'valor_devido' => $venda->valor_devido,
                'situacao' => $venda->situacao,
                'lucro' => $venda->lucro,
                'custo' => $venda->calculaValorCusto(),
                'forma_pagamento' => $venda->formaPagamento->forma,
                'cliente' => $venda->cliente->nome,
                'usuario' => $venda->usuario->nome
            ]);

            foreach ($venda->itens as $item) {
                $itens[] = [
                    'id_produto' => $item->id_produto,
                    'produto_nome' => $item->produto->nome,
                    'preco_venda' => $item->preco_venda,
                    'preco_subtotal' => $item->preco_subtotal,
                    'quantidade' => $item->quantidade,
                    'desconto' => $item->desconto,
                    'preco_total' => $item->preco_total
                ];
            }
            $html->enableSection('itens', $itens, true);
            
            parent::add($html);
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public static function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
