<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class PagamentoForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();

        # Verifica se o usuário tem permissão
        if (!Helpers::verify_level_user(2)) {
            new TMessage('info', 'Você não tem permissão para acessar essa página');

            if (!TSession::getValue('logged')) {
                LoginForm::onLogout();
            }

            exit;
        }

        parent::setTargetContainer('adianti_right_panel');
        $venda = TSession::getValue('venda');

        $div = new TElement('div');
        $div->class = "row";
        
        $indicadorTotal = new THtmlRenderer('app/resources/info-box.html');
        $indicadorTotal->enableSection('main', [
            'title' => 'Valor Total',
            'icon' => 'funnel-dollar',
            'background' => 'blue',
            'value' => 'R$ ' . number_format($venda->calculaValorTotal(), 2, ',', '.')
        ]);

        $div->add($i1 = TElement::tag('div', $indicadorTotal));
        $i1->class = 'col-sm-12';

        try {
            $this->form = new BootstrapFormBuilder('form_venda_pagamento');
            $this->form->setFieldSizes('100%');
            $this->form->setProperty('style', 'margin: 0; border: 0;');

            $cliente = new TDBUniqueSearch('id_cliente', DATABASE_FILENAME, 'Cliente', 'id', 'nome');
            $cliente->setMinLength(2);
            $cliente->setValue($venda->id_cliente);
            $cliente->addValidation('Cliente', new TRequiredValidator);

            $formaPagamento = new TDBCombo('id_forma_pagamento', DATABASE_FILENAME, 'FormaPagamento', 'id', 'forma');
            $formaPagamento->setValue($venda->id_forma_pagamento);
            $formaPagamento->addValidation('Forma de Pagamento', new TRequiredValidator);

            $this->form->addContent([$div]);
            $this->form->addFields([new TLabel('Cliente*'), $cliente], [new TLabel('Forma de Pagamento*'), $formaPagamento])->layout = ['col-sm-12', 'col-sm-12 mt-3'];
            $this->form->addAction('FINALIZAR VENDA', new TAction([$this, 'onFinaliza']), '')->class = 'btn btn-lg btn-primary';
            $this->form->addHeaderAction('Fechar', new TAction([__CLASS__, 'onClose']), 'fas:times');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
        
        $vbox = new TVBox;
        $vbox->style = 'width: 100%;';
        $vbox->add($this->form);
        
        parent::add($vbox);
    }

    public static function onClose($param)
    {
        TScript::create("Template.closeRightPanel();");
    }

    public function onFinaliza($param)
    {
        try {
            $data = $this->form->getData();
            $this->form->validate();

            // Cliente avulso só pode fazer compra 'a vista'
            if (($data->id_cliente == 1) && ($data->id_forma_pagamento != 1)) {
                throw new Exception("Cliente avulso só pode compra a vista");
            }

            $venda = TSession::getValue('venda');
            $venda->id_cliente = $data->id_cliente;
            $venda->id_forma_pagamento = $data->id_forma_pagamento;

            TTransaction::open(DATABASE_FILENAME);

            // No caso de edição
            if ($venda->uniqid) {
                // Atualiza o estoque dos produtos
                $venda->reporEstoque();

                // Marca a venda como não transmitida
                $venda->transmitida = '0';
            }

            $venda->store();

            // Atualiza o estoque dos produtos
            foreach ($venda->itens as $item) {
                $prod = new Produto($item->id_produto);
                $prod->estoque -= $item->quantidade;
                $prod->store();
            }
            
            TTransaction::close();
            TSession::delValue('venda');
            new TMessage('info', 'Venda realizada com sucesso', new TAction(['VendaForm', 'onClear']));
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}