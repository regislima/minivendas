<?php

use Adianti\Base\AdiantiStandardFormTrait;
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Registry\TSession;
use Adianti\Validator\TMaxLengthValidator;
use Adianti\Validator\TMinValueValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TText;
use Adianti\Wrapper\BootstrapFormBuilder;

class ProdutoForm extends TPage 
{
    private $form;
    
    use AdiantiStandardFormTrait;

    public function __construct()
    {        
        try {
            parent::__construct();

            # Verifica se o usuário tem permissão
            if (!Helpers::verify_level_user(1)) {
                new TMessage('info', 'Você não tem permissão para acessar essa página');

                if (!TSession::getValue('logged')) {
                    LoginForm::onLogout();
                }

                exit;
            }

            $this->form = new BootstrapFormBuilder('form_cadastro_produto');
            $this->form->setFormTitle('Cadastro de Produto');
            $this->form->setFieldSizes('100%');

            # Configurações obrigatórias do trait
            $this->setDatabase(DATABASE_FILENAME);
            $this->setActiveRecord('Produto');

            // Campos do formulário
            $id = new TEntry('id');
            $id->setEditable(false);

            $nome = new TEntry('nome');
            $nome->addValidation('Nome', new TMaxLengthValidator, [100]);
            $nome->addValidation('Nome', new TRequiredValidator);
            
            $estoque = new TEntry('estoque');
            $estoque->addValidation('Estoque', new TMinValueValidator, [1]);
            $estoque->inputmode = 'numeric';

            $estoqueMinimo = new TEntry('estoque_minimo');
            $estoqueMinimo->addValidation('Estoque Mínimo', new TMinValueValidator, [1]);
            $estoqueMinimo->inputmode = 'numeric';
            
            $precoCusto = new TEntry('preco_custo');
            $precoCusto->addValidation('Preço de Custo', new TRequiredValidator);
            $precoCusto->setNumericMask(2, ',', '.', true);
            $precoCusto->inputmode = 'numeric';
            
            $precoVenda = new TEntry('preco_venda');
            $precoVenda->addValidation('Preço de Venda', new TRequiredValidator);
            $precoVenda->setNumericMask(2, ',', '.', true);
            $precoVenda->inputmode = 'numeric';
                        
            $obs = new TText('obs');
            $obs->addValidation('Nome', new TMaxLengthValidator, [255]);
            $obs->setSize('100%', 80);
            $obs->placeholder = 'Observações adicionais do produto';

            // Adicionando campos ao formulário
            $linha = $this->form->addFields(
                [new TLabel('Código'), $id],
                [new TLabel('Nome*'), $nome],
                [new TLabel('Estoque*'), $estoque],
                [new TLabel('Estoque Mínimo*'), $estoqueMinimo]
            );
            $linha->layout = ['col-sm-2', 'col-sm-4', 'col-sm-3', 'col-sm-3'];

            $linha = $this->form->addFields(
                [new TLabel('Preço de Custo*'), $precoCusto],
                [new TLabel('Preço de Venda*'), $precoVenda]
            );
            $linha->layout = ['col-sm-3', 'col-sm-3'];

            $linha = $this->form->addFields([new TLabel('Observações'), $obs]);
            $linha->layout = ['col-sm-12'];

            $this->form->addAction('Enviar', new TAction([$this, 'onSave']), 'fa:save green');
            $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
            $this->form->addActionLink('Voltar', new TAction(['ProdutoList', 'onReload']), 'fa:arrow-left');

            $vbox = new TVBox;
            $vbox->style = 'width: 100%;';
            $vbox->add($this->form);

            parent::add($vbox);
        } catch (Exception $e) {
            new TMessage('erro', $e->getMessage());
        }
    }
}
