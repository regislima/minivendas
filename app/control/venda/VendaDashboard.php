<?php

use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Template\THtmlRenderer;

class VendaDashboard extends TPage
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
            
            $indicadorValorBrutoDia = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorBrutoMes = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorLucroDia = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorLucroMes = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorSaldoDia = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorSaldoMes = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorEsperadoAno = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorRecebido = new THtmlRenderer('app/resources/info-box.html');
            $indicadorValorAReceber = new THtmlRenderer('app/resources/info-box.html');

            $valorBrutoDia = $this->getValorBrutoDia();
            $valorBrutoMes = $this->getValorBrutoMes();
            $valorLucroDia = $this->getValorLucroDia();
            $valorLucroMes = $this->getValorLucroMes();
            $valorSaldoDia = $this->getValorSaldoDia();
            $valorSaldoMes = $this->getValorSaldoMes();
            $valorEsperadoAno = $this->getValorEsperadoAno();
            $valorRecebido = $this->getValorRecebido();
            $valorAReceber = $this->getValorAReceber();
            
            $indicadorValorBrutoDia->enableSection('main', [
                'title' => 'Valor Bruto do Dia',
                'icon' => 'funnel-dollar',
                'background' => 'blue',
                'value' => 'R$ ' . number_format($valorBrutoDia, 2, ',', '.')
            ]);
            
            $indicadorValorBrutoMes->enableSection('main', [
                'title' => 'Valor Bruto do Mês',
                'icon' => 'funnel-dollar',
                'background' => 'blue',
                'value' => 'R$ ' . number_format($valorBrutoMes, 2, ',', '.')
            ]);

            $indicadorValorLucroDia->enableSection('main', [
                'title' => 'Lucro do Dia',
                'icon' => 'funnel-dollar',
                'background' => 'gray',
                'value' => 'R$ ' . number_format($valorLucroDia, 2, ',', '.')
            ]);

            $indicadorValorLucroMes->enableSection('main', [
                'title' => 'Lucro do Mês',
                'icon' => 'funnel-dollar',
                'background' => 'gray',
                'value' => 'R$ ' . number_format($valorLucroMes, 2, ',', '.')
            ]);

            $indicadorValorSaldoDia->enableSection('main', [
                'title' => 'Saldo Dia',
                'icon' => 'funnel-dollar',
                'background' => $this->getValorSaldoDia() < 0 ? 'red' : 'green',
                'value' => 'R$ ' . number_format($valorSaldoDia, 2, ',', '.')
            ]);

            $indicadorValorSaldoMes->enableSection('main', [
                'title' => 'Saldo do Mês',
                'icon' => 'funnel-dollar',
                'background' => $this->getValorSaldoMes() < 0 ? 'red' : 'green',
                'value' => 'R$ ' . number_format($valorSaldoMes, 2, ',', '.')
            ]);

            $indicadorValorEsperadoAno->enableSection('main', [
                'title' => 'Valor Esperado',
                'icon' => 'funnel-dollar',
                'background' => 'orange',
                'value' => 'R$ ' . number_format($valorEsperadoAno, 2, ',', '.')
            ]);

            $indicadorValorRecebido->enableSection('main', [
                'title' => 'Valor Recebido',
                'icon' => 'funnel-dollar',
                'background' => 'orange',
                'value' => 'R$ ' . number_format($valorRecebido, 2, ',', '.')
            ]);

            $indicadorValorAReceber->enableSection('main', [
                'title' => 'Valor a Receber',
                'icon' => 'funnel-dollar',
                'background' => 'orange',
                'value' => 'R$ ' . number_format($valorAReceber, 2, ',', '.')
            ]);
            
            $div->add($i1 = TElement::tag('div', $indicadorValorBrutoDia));
            $div->add($i3 = TElement::tag('div', $indicadorValorLucroDia));
            $div->add($i5 = TElement::tag('div', $indicadorValorSaldoDia));
            $div->add($i2 = TElement::tag('div', $indicadorValorBrutoMes));
            $div->add($i4 = TElement::tag('div', $indicadorValorLucroMes));
            $div->add($i6 = TElement::tag('div', $indicadorValorSaldoMes));
            $div->add($i8 = TElement::tag('div', $indicadorValorEsperadoAno));
            $div->add($i9 = TElement::tag('div', $indicadorValorRecebido));
            $div->add($i7 = TElement::tag('div', $indicadorValorAReceber));
            $div->add($pizza1 = $this->geraGraficoProdutosMaisVendidos());
            $div->add($pizza2 = $this->geraGraficoMelhoresVendedores());
            $div->add($pizza3 = $this->geraGraficoDividaClientes());
            $div->add($coluna = $this->geraGraficoSaldoMes());
            
            $i1->class = 'col-sm-4';
            $i2->class = 'col-sm-4';
            $i3->class = 'col-sm-4';
            $i4->class = 'col-sm-4';
            $i5->class = 'col-sm-4';
            $i6->class = 'col-sm-4';
            $i7->class = 'col-sm-4';
            $i8->class = 'col-sm-4';
            $i9->class = 'col-sm-4';
            $pizza1->class = 'col-sm-6';
            $pizza2->class = 'col-sm-6';
            $pizza3->class = 'col-sm-6';
            $coluna->class = 'col-sm-12';
            
            $vbox->add($div);
            
            parent::add($vbox);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Calcula o valor total esperado do ano
     * @return float 
     * @throws Exception 
     */
    private function getValorEsperadoAno(): float
    {
        TTransaction::open(DATABASE_FILENAME);
        $receita = Venda::where('YEAR(created_at)', '=',  date('Y'))->sumBy('valor_final');
        TTransaction::close();

        return $receita ?? 0;
    }

    /**
     * Calcula o valor total que já foi pago
     * @return mixed 
     * @throws Exception 
     */
    private function getValorRecebido()
    {
        TTransaction::open(DATABASE_FILENAME);
        $receita = Venda::where('YEAR(created_at)', '=',  date('Y'))->sumBy('valor_pago');
        TTransaction::close();

        return $receita ?? 0;
    }

    /**
     * Calcula o total de valor que é devido
     * @return float 
     * @throws Exception 
     */
    public function getValorAReceber(): float
    {
        TTransaction::open(DATABASE_FILENAME);
        $debitos = Venda::sumBy('valor_devido');
        TTransaction::close();

        return $debitos ?? 0;
    }

    /**
     * Calcula o montante recebido no dia, sem descontar nada
     * @return float 
     * @throws Exception 
     */
    private function getValorBrutoDia(): float
    {
        TTransaction::open(DATABASE_FILENAME);
        $valor = 0;
        $valor += Venda::where('date_format(created_at, "%Y-%m-%d")', '=', date('Y-m-d'))->where('id_forma_pagamento', '=', 1)->sumBy('valor_pago') ?? 0;
        $valor += VendaPagamento::where('date_format(created_at, "%Y-%m-%d")', '=', date('Y-m-d'))->sumBy('valor_pago') ?? 0;
        
        TTransaction::close();

        return $valor;
    }

    /**
     * Calcula o montante recebidos no mês, sem descontar nada
     * @param int|null $mes 
     * @return float 
     * @throws Exception 
     */
    private function getValorBrutoMes(int $mes = null): float
    {
        TTransaction::open(DATABASE_FILENAME);
        $data = empty($mes) ? date('Y-m') : ($mes < 10 ? date('Y-' . str_pad($mes, 2, "0", STR_PAD_LEFT)) : date("Y-{$mes}"));
        $valor = 0;

        $valor += Venda::where('date_format(created_at, "%Y-%m")', '=', $data)->where('id_forma_pagamento', '=', 1)->sumBy('valor_pago') ?? 0;
        $valor += VendaPagamento::where('date_format(created_at, "%Y-%m")', '=', $data)->sumBy('valor_pago') ?? 0;

        TTransaction::close();

        return $valor;
    }

    /**
     * Calcula o montante recebidos no dia, descontando o valor de custo
     * @return float 
     * @throws Exception 
     */
    public function getValorLucroDia(): float
    {
        TTransaction::open(DATABASE_FILENAME);

        $custo = 0;

        # Soma todo o custo de vendas do dia
        $vendas = Venda::where('date_format(created_at, "%Y-%m-%d")', '=', date('Y-m-d'))->load();
        if ($vendas) {
            foreach ($vendas as $venda) {
                $custo += $venda->calculaValorCusto();
            }
        }

        TTransaction::close();

        return $this->getValorBrutoDia() - $custo;
    }

    /**
     * Calcula o montante recebidos no mês, descontando o valor de custo
     * @return float 
     * @throws Exception 
     */
    public function getValorLucroMes(int $mes = null)
    {
        TTransaction::open(DATABASE_FILENAME);

        $data = empty($mes) ? date('Y-m') : ($mes < 10 ? date('Y-' . str_pad($mes, 2, "0", STR_PAD_LEFT)) : date("Y-{$mes}"));
        $custo = 0;

        # Soma todo o custo de vendas do mês
        $vendas = Venda::where('date_format(created_at, "%Y-%m")', '=', $data)->load();
        if ($vendas) {
            foreach ($vendas as $venda) {
                $custo += $venda->calculaValorCusto();
            }
        }

        TTransaction::close();

        return $this->getValorBrutoMes($mes) - $custo;
    }

    /**
     * Calcula o montante recebidos no mês, descontando o valor de custo e despesas
     * @return float 
     * @throws Exception 
     */
    public function getValorSaldoDia(): float
    {
        TTransaction::open(DATABASE_FILENAME);
        $despesa = Despesa::where('date_format(created_at, "%Y-%m-%d")', '=', date('Y-m-d'))->sumBy('valor');
        TTransaction::close();

        return $this->getValorLucroDia() - $despesa;
    }

    public function getValorSaldoMes(int $mes = null)
    {
        TTransaction::open(DATABASE_FILENAME);

        $data = empty($mes) ? date('Y-m') : ($mes < 10 ? date('Y-' . str_pad($mes, 2, "0", STR_PAD_LEFT)) : date("Y-{$mes}"));
        $despesa = Despesa::where('date_format(created_at, "%Y-%m")', '=', $data)->sumBy('valor');

        TTransaction::close();

        return $this->getValorLucroMes($mes) - $despesa;
    }

    private function geraGraficoProdutosMaisVendidos()
    {
        TTransaction::open(DATABASE_FILENAME);
        $html = new THtmlRenderer('app/resources/google_pie_chart.html');
        $produtos = ItemVenda::orderBy('quantidade', 'desc')->groupBy('id_produto')->sumBy('quantidade');
        
        if (is_array($produtos) && count($produtos) > 5) {
            $produtos = array_slice($produtos, 0, 5);
        }

        $data = [];
        $data[] = ['Produto', 'Quantidade'];

        if ($produtos) {
            foreach ($produtos as $produto) {
                $data[] = [Produto::find($produto->id_produto)->nome, (float) $produto->quantidade];
            }
        }
        
        TTransaction::close();
        
        # PS: Se os valores forem carregados do banco de dados, 
        # converter para float. Ex: (float) $row['total']
        
        $html->enableSection('main', [
            'data' => json_encode($data),
            'width' => '100%',
            'height' => '300px',
            'title' => '5 Produtos mais Vendidos',
            'ytitle' => '', 
            'xtitle' => '',
            'uniqid' => uniqid()
        ]);
        
        $container = new TVBox;
        $container->style = 'width: 100%;';
        $container->add($html);

        return $container;
    }

    public function geraGraficoMelhoresVendedores()
    {
        TTransaction::open(DATABASE_FILENAME);
        $html = new THtmlRenderer('app/resources/google_pie_chart.html');
        
        $vendedores = Venda::orderBy('valor_final', 'desc')->groupBy('id_usuario')->sumBy('valor_final');

        if (is_array($vendedores) && count($vendedores) > 5) {
            $vendedores = array_slice($vendedores, 0, 5);
        }

        $data = [];
        $data[] = ['Vendedor', 'Total Vendido'];

        if ($vendedores) {
            foreach ($vendedores as $vendedor) { 
                $data[] = [Usuario::find($vendedor->id_usuario)->nome, (float) $vendedor->valor_final];
            }
        }
        
        TTransaction::close();
        
        # PS: Se os valores forem carregados do banco de dados, 
        # converter para float. Ex: (float) $row['total']
        
        $html->enableSection('main', [
            'data' => json_encode($data),
            'width' => '100%',
            'height' => '300px',
            'title' => '5 Melhores Vendedores',
            'ytitle' => '', 
            'xtitle' => '',
            'uniqid' => uniqid()
        ]);
        
        $container = new TVBox;
        $container->style = 'width: 100%;';
        $container->add($html);

        return $container;
    }

    public function geraGraficoDividaClientes()
    {
        TTransaction::open(DATABASE_FILENAME);
        $html = new THtmlRenderer('app/resources/google_pie3d_chart.html');
        
        $clientes = Venda::orderBy('valor_devido', 'desc')->groupBy('id_cliente')->sumBy('valor_devido');
        
        if (is_array($clientes) && count($clientes) > 5) {
            $clientes = array_slice($clientes, 0, 5);
        }

        $data = [];
        $data[] = ['Cliente', 'Dívida'];

        if ($clientes) {
            foreach ($clientes as $cliente) { 
                $data[] = [Cliente::find($cliente->id_cliente)->nome, (float) $cliente->valor_devido];
            }
        }
        
        TTransaction::close();
        
        # PS: Se os valores forem carregados do banco de dados, 
        # converter para float. Ex: (float) $row['total']
        
        $html->enableSection('main', [
            'data' => json_encode($data),
            'width' => '100%',
            'height' => '300px',
            'title' => '5 Maiores Devedores',
            'ytitle' => '', 
            'xtitle' => '',
            'uniqid' => uniqid()
        ]);
        
        $container = new TVBox;
        $container->style = 'width: 100%;';
        $container->add($html);

        return $container;
    }

    private function geraGraficoSaldoMes()
    {
        TTransaction::open(DATABASE_FILENAME);
        $html = new THtmlRenderer('app/resources/google_column_chart.html');
        $data = [];
        $data[] = ['Mês', 'Saldo'];

        $mes = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];

        // Coleta as vendas mês a mês
        foreach ($mes as $key => $value) {
            $data[] = [$value, (float) $this->getValorSaldoMes($key)];
        }
        
        # PS: Se os valores forem carregados do banco de dados, 
        # converter para float. Ex: (float) $row['total']

        $html->enableSection('main', [
            'data' => json_encode($data),
            'width' => '100%',
            'height' => '300px', 
            'title' => 'Saldo por Mês (' . date('Y') . ')',
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