# 🛒 Sistema de Vendas Pessoal

Este é um sistema de vendas pessoal desenvolvido com o **Adianti Framework**. esse sistema foi desenvolvido para atender as necessidades de vendas para meu irmão, que precisava de algo para controlar o estoque, gastos, receitas e gerar alguns reatórios para melhor visualização dos dados.

Siga os passos abaixo para configurar e executar o projeto.

## ⚙️ 1. Configuração do Banco de Dados

- Acesse o diretório `app/config`.
- Abra o arquivo `database-mysql.ini` e atualize as configurações com as informações do seu banco de dados (MariaDB ou MySQL).
- Se necessário, insira as informações REST no arquivo `application.ini`.

## 🗃️ 2. Importação dos Scripts SQL

- Vá para o diretório `app/database`.
- Importe o arquivo `database.sql` para o seu banco de dados.
- Em seguida, importe o arquivo `database_inserts.sql` para popular as tabelas.
- Para cadastrar mais estados e cidades, utilize o arquivo `todas as cidades e estados do Brasil.txt`.

## 📦 3. Instalação das Dependências

- Certifique-se de que o PHP está instalado e configurado nas variáveis de ambiente.
- Baixe o Composer através deste link: [getcomposer.org](https://getcomposer.org/download/latest-stable/composer.phar)
- Coloque o arquivo `composer.phar` no diretório raiz do projeto.
- Abra o terminal, navegue até o diretório do projeto e execute o seguinte comando:

```bash
php composer.phar install
````

## 🔑 4. Credenciais de Acesso

- Administrador:
  - Login: admin@admin.com
  - Senha: admin123

- Padrão:
  - Login: padrao@padrao.com
  - Senha: padrao123
