CREATE DATABASE minivendas CHARACTER SET = 'utf8mb4' COLLATE = 'utf8mb4_general_ci';

USE minivendas;

CREATE TABLE estado (
    id SMALLINT PRIMARY KEY NOT NULL, 
    sigla CHAR(2) NOT NULL,
    nome VARCHAR(50) NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE cidade (
    id INT PRIMARY KEY NOT NULL,
    nome VARCHAR(50) NOT NULL,
    id_estado SMALLINT NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_estado) REFERENCES estado(id)
);

CREATE TABLE forma_pagamento (
    id SMALLINT PRIMARY KEY NOT NULL,
    forma VARCHAR(20) NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE usuarios (
    id SMALLINT PRIMARY KEY NOT NULL,
    nome varchar(100) NOT NULL,
    sobrenome varchar(100) NOT NULL,
    email varchar(255) NOT NULL,
    senha varchar(255) NOT NULL,
    nivel SMALLINT NOT NULL DEFAULT 1,
    ativo TINYINT NOT NULL DEFAULT 1,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (email)
);

CREATE TABLE produto (
    id SMALLINT PRIMARY KEY NOT NULL,
    nome VARCHAR(100) NOT NULL,
    estoque SMALLINT NOT NULL,
    estoque_minimo SMALLINT NOT NULL,
    preco_custo DECIMAL(10,2) NOT NULL,
    preco_venda DECIMAL(10,2) NOT NULL,
    obs TEXT,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE despesa (
    id SMALLINT PRIMARY KEY NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    id_forma_pagamento SMALLINT NOT NULL,
    data_resgate_cheque DATE,
    descricao VARCHAR(200) NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_forma_pagamento) REFERENCES forma_pagamento(id)
);

CREATE TABLE cliente (
    id SMALLINT PRIMARY KEY NOT NULL,
    nome VARCHAR(100) NOT NULL,
    endereco VARCHAR(100) NULL,
    telefone VARCHAR(20) NULL,
    id_cidade INT NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cidade) REFERENCES cidade(id)
);

CREATE TABLE venda (
    uniqid VARCHAR(15) PRIMARY KEY NOT NULL,
    id_cliente SMALLINT NOT NULL,
    id_usuario SMALLINT NOT NULL,
    id_forma_pagamento SMALLINT NOT NULL,
    valor_final DECIMAL(10,2) NOT NULL,
    valor_pago DECIMAL(10,2) NOT NULL,
    valor_devido DECIMAL(10,2) NOT NULL,
    situacao VARCHAR(10) NOT NULL COMMENT 'Pago, Devendo',
    lucro DECIMAL(10,2) NOT NULL,
    transmitida CHAR(1) NOT NULL DEFAULT '0',
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_forma_pagamento) REFERENCES forma_pagamento(id)
);

CREATE TABLE item_venda (
    uniqid VARCHAR(15) PRIMARY KEY NOT NULL,
    id_produto SMALLINT NOT NULL,
    uniqid_venda VARCHAR(15) NOT NULL,
    quantidade SMALLINT NOT NULL,
    preco_custo DECIMAL(10,2) NOT NULL,
    preco_venda DECIMAL(10,2) NOT NULL,
    desconto DECIMAL(10,2),
    preco_subtotal DECIMAL(10,2) NOT NULL,
    preco_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_produto) REFERENCES produto(id),
    FOREIGN KEY (uniqid_venda) REFERENCES venda(uniqid)
);

CREATE TABLE venda_pagamento (
    uniqid VARCHAR(15) PRIMARY KEY NOT NULL,
    uniqid_venda VARCHAR(15) NOT NULL,
    valor_pago DECIMAL(10,2) NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uniqid_venda) REFERENCES venda(uniqid)
);