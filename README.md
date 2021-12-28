# minivendas
Sistema de vendas pessoal feito com adianti framework

1 - Entre no diretório app/config;
    - Mude as configurações do arquivo database-mysql.ini para as do seu banco de dados (mariadb ou mysql);
    - Se desejar, insira informações REST no arquivo application.ini;
2 - Entre no diretório app/database;
    - Import o script database.sql para o seu banco de dados;
    - Import o script database_inserts.sql para o seu banco de dados;
    - Caso queira cadastrar mais estados e cidades, use o arquivo 'todas as cidades e estados do Brasil.txt'
3 - Faça o download do composer https://getcomposer.org/download/latest-stable/composer.phar;
4 - Coloque o arquivo composer.phar no diretório raiz do projeto;
5 - Abra um terminal;
    - Navegue até o diretório do projeto;
    - Execute o comando 'php composer.phar install'. (O php deve estar instalado ou setado nas variáveis de ambiente)
6 - Agora o projeto está pronto para rodar no seu servidor.

Obs: Credenciais:
    - Administrador: login: admin@admin.com senha: admin123
    - Padrão: login: padrao@padrao.com senha: padrao123
