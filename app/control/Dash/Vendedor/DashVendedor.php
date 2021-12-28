<?php

use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Template\THtmlRenderer;

class DashVendedor extends TPage
{
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

        try {
            $vbox = new TVBox;
            $vbox->style = 'width: 100%;';
            
            $div = new TElement('div');
            $div->class = "row";
            
            $indicadorVendasDia = new THtmlRenderer('app/resources/info-box.html');
            $indicadorVendasMes = new THtmlRenderer('app/resources/info-box.html');
            $indicadorVendasNaoTransmitidas = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorVendasDia = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorVendasMes = new THtmlRenderer('app/resources/info-box.html');

            $totalVendasDia = $this->getTotalVendasDia();
            $totalVendasMes = $this->getTotalVendasMes();
            $totalNaoTransmitidas = $this->getTotalNaoTransmitidas();
            $totalValorVendasDia = $this->getTotalValorVendasDia();
            $totalValorVendasMes = $this->getTotalValorVendasMes();
            
            $indicadorVendasDia->enableSection('main', [
                'title' => 'Vendas Realizadas Hoje',
                'icon' => 'handshake',
                'background' => 'yellow',
                'value' => $totalVendasDia
            ]);
            
            $indicadorVendasMes->enableSection('main', [
                'title' => 'Vendas Realizadas no Mês',
                'icon' => 'handshake',
                'background' => 'orange',
                'value' => $totalVendasMes
            ]);

            $indicadorVendasNaoTransmitidas->enableSection('main', [
                'title' => 'Vendas Não Transmitidas',
                'icon' => 'paper-plane',
                'background' => 'orange',
                'value' => $totalNaoTransmitidas
            ]);

            $indicadorValorVendasDia->enableSection('main', [
                'title' => 'Valor Apurado Hoje',
                'icon' => 'fas fa-dollar-sign',
                'background' => '$cccccc',
                'value' => number_format($totalValorVendasDia, 2, ',', '.')
            ]);

            $indicadorValorVendasMes->enableSection('main', [
                'title' => 'Valor Apurado no Mês',
                'icon' => 'fas fa-dollar-sign',
                'background' => 'red',
                'value' => number_format($totalValorVendasMes, 2, ',', '.')
            ]);
            
            $div->add($i1 = TElement::tag('div', $indicadorVendasDia));
            $div->add($i2 = TElement::tag('div', $indicadorVendasMes));
            $div->add($i3 = TElement::tag('div', $indicadorVendasNaoTransmitidas));
            $div->add($i4 = TElement::tag('div', $indicadorValorVendasDia));
            $div->add($i5 = TElement::tag('div', $indicadorValorVendasMes));
            $div->add($grafico = $this->geraGraficoVendasMes());
            
            $i1->class = 'col-sm-6';
            $i2->class = 'col-sm-6';
            $i3->class = 'col-sm-6';
            $i4->class = 'col-sm-6';
            $i5->class = 'col-sm-6';
            $grafico->class = 'col-sm-12';
            
            $vbox->add($div);
            parent::add($vbox);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private function getTotalVendasDia()
    {
        TTransaction::open(DATABASE_FILENAME);
        
        $idVendedor = TSession::getValue('userid');
        
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_usuario', '=', $idVendedor));
        $criteria->add(new TFilter('date_format(created_at, "%Y-%m-%d")', '=', date('Y-m-d')));
        
        $vendasDia = Venda::countObjects($criteria);
        
        TTransaction::close();

        return $vendasDia ?? 0;
    }

    private function getTotalVendasMes()
    {
        TTransaction::open(DATABASE_FILENAME);
        
        $idVendedor = TSession::getValue('userid');
        
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_usuario', '=', $idVendedor));
        $criteria->add(new TFilter('date_format(created_at, "%Y-%m")', '=', date('Y-m')));
        
        $vendasDia = Venda::countObjects($criteria);
        
        TTransaction::close();

        return $vendasDia ?? 0;
    }

    private function getTotalNaoTransmitidas(): int
    {
        TTransaction::open(DATABASE_FILENAME);
        $naoTransmitidas =  Venda::where('transmitida', '=', '0')->count();
        TTransaction::close();

        return $naoTransmitidas;
    }

    /**
     * Calcula o valor recebido no dia
     * @return float 
     * @throws Exception 
     */
    private function getTotalValorVendasDia() : float
    {
        TTransaction::open(DATABASE_FILENAME);
        $idVendedor = TSession::getValue('userid');

        # Seleciona todas as vendas feitas pelo usuário logado que foram pagas a vista no dia atual
        $valorVendasDia = Venda::where('id_usuario', '=', $idVendedor)->where('date_format(created_at, "%Y-%m-%d")', '=', date('Y-m-d'))->where('id_forma_pagamento', '=', 1)->sumBy('valor_pago') ?? 0;
        
        # Seleciona todas as vendas feitas pelo usuário logado que tiveram alguma parcela paga no dia atual
        $vendas = Venda::where('id_usuario', '=', $idVendedor)->where('id_forma_pagamento', '!=', 1)->load();
        if ($vendas) {
            foreach ($vendas as $venda) {
                if ($venda->pagamentos) {
                    foreach ($venda->pagamentos as $pagamento) {
                        if (date_create($pagamento->created_at)->format('Y-m-d') == date('Y-m-d')) {
                            $valorVendasDia += $pagamento->valor_pago;
                        }
                    }
                }
            }
        }

        TTransaction::close();

        return $valorVendasDia;
    }

    /**
     * Calcula o valor apurado no mês
     * @param int|null $mes 
     * @return float 
     * @throws Exception
     */
    private function getTotalValorVendasMes(int $mes = null) : float
    {
        $data = empty($mes) ? date('Y-m') : ($mes < 10 ? date('Y-' . str_pad($mes, 2, "0", STR_PAD_LEFT)) : date("Y-{$mes}"));

        TTransaction::open(DATABASE_FILENAME);
        $idVendedor = TSession::getValue('userid');
        
        $valorVendasMes = Venda::where('id_usuario', '=', $idVendedor)->where('date_format(created_at, "%Y-%m")', '=', $data)->where('id_forma_pagamento', '=', 1)->sumBy('valor_pago') ?? 0;

        # Seleciona todas as vendas feitas pelo usuário logado que tiveram alguma parcela paga no mês atual
        $vendas = Venda::where('id_usuario', '=', $idVendedor)->where('id_forma_pagamento', '!=', 1)->load();
        if ($vendas) {
            foreach ($vendas as $venda) {
                if ($venda->pagamentos) {
                    foreach ($venda->pagamentos as $pagamento) {
                        if (date_create($pagamento->created_at)->format('Y-m') == $data) {
                            $valorVendasMes += $pagamento->valor_pago;
                        }
                    }
                }
            }
        }

        TTransaction::close();

        return $valorVendasMes;
    }


    /* Gráfico usando ChartJS
    private function geraGraficoVendasMes()
    {
        $html = new THtmlRenderer('app/resources/chartjs/bar.html');
        $data = [];

        $mes = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];

        // Coleta as vendas mês a mês
        foreach ($mes as $key => $value) { 
            $data[$value] = (float) $this->getTotalValorVendasMes($key);
        }
        
        # PS: Se os valores forem carregados do banco de dados, 
        # converter para float. Ex: (float) $row['total']

        $html->enableSection('main', [
            'width' => '100%',
            'height' => '300px',
            'title' => 'Venda por Mês (' . date('Y') . ')',
            'uniqid' => uniqid(),
            'labels' => json_encode(array_keys($data)),
            'data' => json_encode(array_values($data))
        ]);
        
        $container = new TVBox;
        $container->style = 'width: 100%;';
        $container->add($html);
        
        return $container;
    }

    /** Gera gráfico usando google chart */
    private function geraGraficoVendasMes()
    {
        $html = new THtmlRenderer('app/resources/google_column_chart.html');
        $data = [];
        $data[] = ['Mês', 'Vendas'];

        $mes = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];

        // Coleta as vendas mês a mês
        foreach ($mes as $key => $value) { 
            $data[] = [$value, (float) $this->getTotalValorVendasMes($key)];
        }
        
        # PS: Se os valores forem carregados do banco de dados, 
        # converter para float. Ex: (float) $row['total']

        $html->enableSection('main', [
            'data' => json_encode($data),
            'width' => '100%',
            'height' => '300px', 
            'title' => 'Venda por Mês (' . date('Y') . ')',
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
