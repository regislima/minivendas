<?php

use Adianti\Control\TPage;
use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TVBox;

class MinivendasRest extends TPage
{
    private $location;
    private $restAuth;
    private $response;
    private $pageStep;
    
    public function __construct()
    {
        parent::__construct();

        $ini = AdiantiApplicationConfig::get();

        $this->location = $ini['rest']['rest_server'];
        $this->restAuth = $ini['rest']['rest_auth'];
        $this->response = [];

        $this->pageStep = new TPageStep;

        $vbox = new TVBox;
        $vbox->style = 'width: 100%;';
        $vbox->add($this->pageStep);
        parent::add($vbox);
    }

    public function loadAll()
    {
        $this->pageStep->addItem('Produtos');
        $this->pageStep->addItem('Formas de Pagamento');
        $this->pageStep->addItem('Estados');
        $this->pageStep->addItem('Cidades');
        $this->pageStep->addItem('Clientes');
        $this->pageStep->addItem('Usuários');
        
        try {
            $this->loadProduto();
            $this->pageStep->select('Produtos');

            $this->loadFormaPagamentos();
            $this->pageStep->select('Formas de Pagamento');

            $this->loadEstados();
            $this->pageStep->select('Estados');

            $this->loadCidades();
            $this->pageStep->select('Cidades');

            $this->loadClientes();
            $this->pageStep->select('Clientes');

            $this->loadUsuarios();
            $this->pageStep->select('Usuários');

            $this->message('success', 'Dados sincronizados com sucesso');
        } catch (Exception $e) {
            $this->message('danger', 'Erro ao sincronizar os dados: ' . $e->getMessage());
        }
    }

    /**
     * Carrega todos os registros de estados
     * @return void 
     * @throws Exception 
     */
    public function loadEstados()
    {
        $parametros['class'] = 'EstadoRest';
        $parametros['method'] = 'loadAll';

        $this->response = Helpers::request($this->location, 'GET', $parametros, $this->restAuth);
        $this->sincronize('Estado');
    }

    /**
     * Carrega todos os registros de cidades
     * @return void 
     * @throws Exception 
     */
    public function loadCidades()
    {
        $parametros['class'] = 'CidadeRest';
        $parametros['method'] = 'loadAll';

        $this->response = Helpers::request($this->location, 'GET', $parametros, $this->restAuth);
        $this->sincronize('Cidade');
    }

    /**
     * Carrega todos os registros de formas de pagamento
     * @return void 
     * @throws Exception 
     */
    public function loadFormaPagamentos()
    {
        $parametros['class'] = 'FormaPagamentoRest';
        $parametros['method'] = 'loadAll';

        $this->response = Helpers::request($this->location, 'GET', $parametros, $this->restAuth);
        $this->sincronize('FormaPagamento');
    }

    /**
     * Carrega todos os registros de Usuários
     * @return void 
     * @throws Exception 
     */
    public function loadUsuarios()
    {
        $parametros['class'] = 'UsuarioRest';
        $parametros['method'] = 'loadAll';

        $this->response = Helpers::request($this->location, 'GET', $parametros, $this->restAuth);
        $this->sincronize('Usuario');
    }

    /**
     * Carrega todos os registros de produtos
     * @return void 
     * @throws Exception 
     */
    public function loadProduto()
    {
        $parametros['class'] = 'ProdutoRest';
        $parametros['method'] = 'loadAll';
    
        $this->response = Helpers::request($this->location, 'GET', $parametros, $this->restAuth);
        $this->sincronize('Produto');
    }

    /**
     * Carrega todos os registros de clientes
     * @return void 
     * @throws Exception 
     */
    public function loadClientes()
    {
        $parametros['class'] = 'ClienteRest';
        $parametros['method'] = 'loadAll';
    
        $this->response = Helpers::request($this->location, 'GET', $parametros, $this->restAuth);
        $this->sincronize("Cliente");
    }

    /**
     * Carrega todos os registros de vendas
     * @return void 
     * @throws Exception 
     */
    public function loadVendas()
    {
        $parametros['class'] = 'VendaRest';
        $parametros['method'] = 'loadAll';
        $parametros['filters'] = [['id_usuario', '=', TSession::getValue('userid')]];
    
        $this->response = Helpers::request($this->location, 'GET', $parametros, $this->restAuth);
        $this->sincronize("Venda");

        # Carrega os registros dos itens da venda
        $vendas = $this->response;
        foreach ($vendas as $venda) {
            $parametros['class'] = 'ItemVendaRest';
            $parametros['method'] = 'loadAll';
            $parametros['filters'] = [['uniqid_venda', '=', $venda->uniqid]];
    
            $this->response = Helpers::request($this->location, 'GET', $parametros, $this->restAuth);
            $this->sincronize("itemVenda");
        }
    }

    /**
     * Sobe todas as vendas (do usuário logado) que não foram transmitidas para o servidor
     * @return void 
     * @throws Exception 
     */
    public function uploadVendas()
    {
        try {
            TTransaction::open(DATABASE_FILENAME);

            $userId = TSession::getValue('userid');
            $parametros['class'] = 'VendaRest';
            $parametros['method'] = 'store';

            $vendas = Venda::where('id_usuario', '=', $userId)->where('transmitida', '=', 0)->load();

            # Caso o usuário tenha vendas não transmitidas
            if ($vendas) {
                foreach ($vendas as $venda) {
                    # Enviando a venda
                    $parametros['venda'] = $venda->toArray();

                    foreach ($venda->itens as $item) {
                        $parametros['itens'][] = $item->toArray();
                        $parametros['produto'][$item->id_produto] = (Produto::find($item->id_produto))->estoque;
                    }

                    if ($venda->pagamentos) {
                        foreach ($venda->pagamentos as $pagamento) {
                            $parametros['pagamentos'][] = $pagamento->toArray();
                        }
                    }
                    
                    Helpers::request($this->location, 'POST', $parametros, $this->restAuth);

                    # Marcando a venda como transmitida
                    $venda->transmitida = '1';
                    $venda->store();

                    # Limpando os arrays
                    $parametros['venda'] = [];
                    $parametros['itens'] = [];
                    $parametros['produto'] = [];
                    $parametros['pagamentos'] = [];
                }

                $this->message('success', 'Vendas enviadas com sucesso');
            } else {
                $this->message('success', 'Nenhuma venda para ser enviada');
            }

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            $this->message('error', 'Erro ao enviar as vendas: ' . $e->getMessage());
        }
    }

    private function sincronize(string $activeRecord)
    {
        TTransaction::open(DATABASE_FILENAME);

        # Caso venha objetos do servidor remoto
        if ($this->response) {
            foreach ($this->response as $value) {
                $activeRecord::create((array) $value);
            }
            
        # Caso não venha objetos do servidor remoto
        } else {
            # Carrega todos os objetos do banco de dados local, se existirem
            $objects = $activeRecord::countObjects();

            if ($objects) {
                if ($activeRecord == 'Usuario') {
                    $activeRecord::where('id', '>', 1)->delete();
                } else {
                    if ($activeRecord == 'Venda' or $activeRecord == 'ItemVenda') {
                        $activeRecord::where('uniqid', '!=', '')->delete();
                    } else {
                        $activeRecord::where('id', '>', 0)->delete();
                    }
                }
            }
        }

        TTransaction::close();
    }

    /**
     * Exibe um alerta
     * @param string $type Tipos de alerts bootstrap
     * @param string|null $message Texto que será exibido
     * @return void 
     */
    private function message(string $type, string $message = null)
    {
        # Criando a mensagem de sucesso
        $element = new TElement('div');
        $element->role = 'alert';

        if ($type == 'success') {
            $element->class = 'mt-4 alert alert-' . $type;
        }

        if ($type == 'danger') {
            $element->class = 'mt-4 alert alert-' . $type;
        }

        $element->add($message);

        parent::add($element);
    }
}
