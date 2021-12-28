INSERT INTO estado (id, sigla, nome) VALUES (1, 'AV', 'Avulso');
INSERT INTO cidade (id, nome, id_estado) VALUES (1, 'Avulso', 1);

# Cliente avulso
INSERT INTO cliente (id, nome, endereco, telefone, id_cidade) VALUES (1, 'Avulso', 'Nenhum', '00000000000', 1);

# Usuário padrão (senha: padrao123)
INSERT INTO usuarios (id, nome, sobrenome, email, senha, nivel, ativo) VALUES (1, 'Padrao', 'Padrao', 'padrao@padrao.com', '$2y$10$/GD11kDR6LAJOZ9d.v118OycQHx2VkkJrXF35lLAkgMWsHiKmpzeW', 0, 1);

# Usuário administrador (senha: admin123)
INSERT INTO usuarios (id, nome, sobrenome, email, senha, nivel, ativo) VALUES (2, 'Admin', 'Admin', 'admin@admin.com', '$2y$10$JPwDRSF0PdeHbmIjNOHCHuW6UBhAOh5RGPg13nuovexfgMFlOzokO', 1, 1);

INSERT INTO forma_pagamento (id, forma) VALUES (1, 'Dinheiro');
INSERT INTO forma_pagamento (id, forma) VALUES (2, 'Fiado');
INSERT INTO forma_pagamento (id, forma) VALUES (3, 'Cheque');