<?php

use Adianti\Control\TAction;
use Adianti\Control\TWindow;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TMaxValueValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TFrame;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TToast;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;

class Pagamento extends TWindow
{
    private $form;
    
    public function __construct($param)
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
        
        if (parent::isMobile()) {
            parent::setSize(0.8, null);
        }
        
        parent::removePadding();

        $this->form = new BootstrapFormBuilder('form_cadastro_pagamento');
        $this->form->setFormTitle('Incluir Pagamento');
        $this->form->setFieldSizes('100%');
        $this->form->setClientValidation(true);

        try {
            TTransaction::open(DATABASE_FILENAME);
            $venda = new Venda($param['key']);
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('info', $e->getMessage());
        }

        # Conteúdo do frame
        $table = new TTable;
        $row = $table->addRow();
        $row->addCell('DATA')->setProperty('style', 'text-align: center; font-weight: bold; width: 50%;');
        $row->addCell('VALOR')->setProperty('style', 'text-align: center; font-weight: bold; width: 50%;');
        
        if ($venda->pagamentos) {
            foreach ($venda->pagamentos as $value) {
                $row = $table->addRow();
                $date = new DateTime($value->created_at);
                $row->addCell($date->format('d/m/Y H:i:s'))->setProperty('style', 'text-align: center;');
                $row->addCell(number_format($value->valor_pago, 2, ',', '.'))->setProperty('style', 'text-align: center;');
            }
        }

        $button = new TButton('mostrar_esconder');
        $button->class = 'btn btn-primary btn-sm active';
        $button->setLabel('Ver Histórico');
        $button->addFunction("\$('[oid=frame-history]').slideToggle(); $(this).toggleClass('active')");

        $frame = new TFrame();
        $frame->oid = 'frame-history';
        $frame->setLegend('Histórico de Pagamentos');
        count($venda->pagamentos) > 0 ? $frame->add($table) : $frame->add('Nenhum pagamento necessário ou realizado');

        $hidden = new THidden('uniqid');
        $hidden->setValue($venda->uniqid);

        $valor = new TEntry('valor');
        $valor->addValidation('Valor', new TRequiredValidator);
        $valor->addValidation('Valor', new TMaxValueValidator, [$venda->valor_devido]);
        $valor->setNumericMask(2, ',', '.', true);
        $valor->inputmode = 'numeric';

        # Esconde os controles caso a situação seja 'Pago'
        if ($venda->situacao == 'Devendo' && Helpers::verify_level_user(2)) {
            $this->form->addFields([$hidden]);
            $this->form->addFields([new TLabel('Valor'), $valor])->layout = ['col-sm-2'];
            $this->form->addAction('Descontar', new TAction([$this, 'onSave'], ['key' => $venda->uniqid]), 'fa:save green');
        }
        
        $this->form->addContent([$button]);
        $this->form->addContent([$frame]);
        
        $vbox = new TVBox;
        $vbox->style = 'width: 100%;';
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public function onSave($param)
    {
        try {
            TTransaction::open(DATABASE_FILENAME);
            
            $data = $this->form->getData();
            $venda = new Venda($data->uniqid);

            if ($data->valor > $venda->valor_devido)
                throw new Exception("Valor informado maior que o valor devido");

            $vp = new VendaPagamento();
            $vp->valor_pago = $data->valor;

            $venda->addPagamento($vp);
            $venda->store();
            
            TToast::show('success', 'Pagamento incluído', 'top center');
            TTransaction::close();

            # Fecha a janela automaticamente
            TScript::create("$('#{$this->id}').remove();");

            # Atualiza a janela de detalhes
            AdiantiCoreApplication::loadPage('VendaDetalhes', 'onDetalhes', ['key' => $venda->uniqid, 'register_state' => 'false']);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('info', $e->getMessage());
        }
    }
}