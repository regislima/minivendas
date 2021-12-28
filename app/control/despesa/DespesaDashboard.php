<?php

use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Template\THtmlRenderer;

class DespesaDashboard extends TPage 
{
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

            $vbox = new TVBox;
            $vbox->style = 'width: 100%;';
            
            $div = new TElement('div');
            $div->class = "row";
            
            $indicadorValorTotal = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorMes = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorDia = new THtmlRenderer('app/resources/info-box.html');

            $valorTotal = $this->getValorTotal();
            $valorMes = $this->getValorMes();
            $valorDia = $this->getValorDia();
            
            $indicadorValorTotal->enableSection('main', [
                'title' => 'Gasto Total',
                'icon' => 'funnel-dollar',
                'background' => 'green',
                'value' => 'R$ ' . number_format($valorTotal, 2, ',', '.')
            ]);
            
            $indicadorValorMes->enableSection('main', [
                'title' => 'Gasto no Mês',
                'icon' => 'funnel-dollar',
                'background' => 'orange',
                'value' => 'R$ ' . number_format($valorMes, 2, ',', '.')
            ]);

            $indicadorValorDia->enableSection('main', [
                'title' => 'Gasto no Dia',
                'icon' => 'funnel-dollar',
                'background' => 'blue',
                'value' => 'R$ ' . number_format($valorDia, 2, ',', '.')
            ]);
            
            $div->add($i1 = TElement::tag('div', $indicadorValorTotal));
            $div->add($i2 = TElement::tag('div', $indicadorValorMes));
            $div->add($i3 = TElement::tag('div', $indicadorValorDia));
            $div->add($pizza = $this->geraGraficoPizza());
            $div->add($coluna = $this->geraGraficoColunas());
            
            $i1->class = 'col-sm-6';
            $i2->class = 'col-sm-6';
            $i3->class = 'col-sm-6';
            $pizza->class = 'col-sm-12';
            $coluna->class = 'col-sm-12';
            
            $vbox->add($div);
            
            parent::add($vbox);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private function getValorTotal()
    {
        TTransaction::open(DATABASE_FILENAME);
        $valorTotal = Despesa::sumBy('valor');
        TTransaction::close();

        return $valorTotal;
    }

    private function getValorMes()
    {
        TTransaction::open(DATABASE_FILENAME);

        $mesAtual = date('m');
        $valorMes = Despesa::where('MONTH(created_at)', '=', $mesAtual)->sumBy('valor');
        TTransaction::close();

        return $valorMes;
    }

    private function getValorDia()
    {
        TTransaction::open(DATABASE_FILENAME);

        $diaAtual = date('d');
        $valorDia = Despesa::where('DAY(created_at)', '=', $diaAtual)->sumBy('valor');
        TTransaction::close();

        return $valorDia;
    }

    private function geraGraficoPizza()
    {
        TTransaction::open(DATABASE_FILENAME);
        $html = new THtmlRenderer('app/resources/google_pie_chart.html');
        
        $valorFormas = Despesa::groupBy('id_forma_pagamento')->sumBy('valor');
        $data = [];
        $data[] = ['Forma de Pagamento', 'Valor'];

        if ($valorFormas) {
            for ($i = 0; $i < sizeof($valorFormas); $i++) { 
                $data[] = [FormaPagamento::find($valorFormas[$i]->id_forma_pagamento)->forma, (float) $valorFormas[$i]->valor];
            }
        }
        
        TTransaction::close();
        
        # PS: Se os valores forem carregados do banco de dados, 
        # converter para float. Ex: (float) $row['total']
        
        $html->enableSection('main', [
            'data' => json_encode($data),
            'width' => '100%',
            'height' => '300px',
            'title' => 'Formas de Pagamento',
            'ytitle' => 'Forma', 
            'xtitle' => 'Valor',
            'uniqid' => uniqid()
        ]);
        
        $container = new TVBox;
        $container->style = 'width: 100%;';
        $container->add($html);

        return $container;
    }

    private function geraGraficoColunas()
    {
        TTransaction::open(DATABASE_FILENAME);
        $html = new THtmlRenderer('app/resources/google_column_chart.html');
        $data = [];
        $data[] = ['Mês', 'Despesas'];

        $mes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        // Coleta as despesas mês a mês
        for ($i = 1; $i <= 12; $i++) { 
            $data[] = [$mes[$i - 1], (float) Despesa::where('MONTH(created_at)', '=', $i)->where('YEAR(created_at)', '=',  date('Y'))->sumBy('valor')];
        }
        
        # PS: Se os valores forem carregados do banco de dados, 
        # converter para float. Ex: (float) $row['total']

        $html->enableSection('main', [
            'data' => json_encode($data),
            'width' => '100%',
            'height' => '300px', 
            'title' => 'Despesa por Mês (' . date('Y') . ')',
            'ytitle' => 'Valores (R$)',
            'xtitle' => 'Mês',
            'uniqid' => uniqid()
        ]);
        
        $container = new TVBox;
        $container->style = 'width: 100%;';
        $container->add($html);
        
        return $container;
    }
}
