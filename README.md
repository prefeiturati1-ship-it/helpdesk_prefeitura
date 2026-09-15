# 🛠️ Help Desk Borborema — Sistema de Gestão de Chamados de TI

Sistema web desenvolvido para gerenciamento, atendimento e acompanhamento de chamados de suporte técnico da Prefeitura de Borborema.

A aplicação permite a comunicação entre servidores municipais (solicitantes), técnicos de TI e administradores, oferecendo recursos para abertura e acompanhamento de chamados, gestão da fila de atendimento, administração de usuários e setores, controle de dispositivos e coleta de informações de telemetria.

---

## 🚀 Funcionalidades Principais

### 👤 Perfil Usuário — Solicitante

* **Abertura de Chamados**

  * Título da solicitação.
  * Categoria.
  * Prioridade.
  * Descrição detalhada do problema.

* **Gerador de Protocolos**

  * Criação automática de protocolo único para cada chamado.
  * Formato: `YYYYMMDD-XXXX`.

* **Meus Chamados**

  * Visualização dos chamados abertos pelo usuário.
  * Acompanhamento do status.
  * Consulta do histórico de atendimento.

* **Interação / Réplica**

  * Leitura das respostas enviadas pelo suporte.
  * Envio de novas mensagens relacionadas ao chamado.

* **Meu Perfil**

  * Atualização dos dados cadastrais.
  * Alteração de informações de contato.
  * Alteração de senha.

---

### 🛠️ Perfil Técnico

* **Fila de Atendimento**

  * Visualização dos chamados da Prefeitura.
  * Organização por prioridade e status.
  * Chamados `Novo` priorizados na fila.
  * Chamados `Em Andamento` na sequência.
  * Chamados `Fechado` posteriormente.

* **Gestão de Chamados**

  * Visualização dos dados completos da solicitação.
  * Consulta dos dados do solicitante.
  * Consulta do setor relacionado.
  * Alteração do status do chamado.

* **Respostas e Atendimento**

  * Registro de orientações.
  * Envio de pareceres técnicos.
  * Histórico das interações realizadas.

* **Cadastro e Gestão**

  * Cadastro de chamados.
  * Cadastro de problemas.
  * Cadastro de usuários.
  * Consulta de históricos relacionados aos computadores.

* **Meu Perfil**

  * Gerenciamento dos dados do próprio técnico.

---

### 👑 Perfil Administrador

O administrador possui acesso ampliado aos recursos do sistema, incluindo:

* Todas as funcionalidades disponíveis para técnicos.
* Gerenciamento de setores.
* Gerenciamento de técnicos.
* Gerenciamento de dispositivos.
* Administração de usuários.
* Consulta de informações e históricos.
* Relatórios.
* Controle geral da fila de atendimento.
* Configurações administrativas.

---

## 💻 Gestão de Dispositivos

O sistema possui um módulo específico para gerenciamento dos equipamentos de informática da Prefeitura.

Entre os recursos estão:

* Cadastro de dispositivos.
* Consulta dos equipamentos cadastrados.
* Visualização de informações dos dispositivos.
* Componentes visuais específicos para apresentação dos equipamentos.
* Integração com informações de telemetria.

O módulo está relacionado aos arquivos:

```text
admin/dispositivos.php
includes/components/card_dispositivo.php
api/telemetria.php
```

---

## 📡 Telemetria

O sistema possui uma API destinada ao recebimento e processamento de informações de telemetria dos equipamentos.

Arquivo principal:

```text
api/telemetria.php
```

A estrutura permite futuramente ampliar o monitoramento dos computadores e dispositivos da Prefeitura, possibilitando a coleta de informações técnicas dos equipamentos integrados ao sistema.

---

## 📊 Relatórios

O sistema possui componentes destinados à geração e apresentação de informações gerenciais.

Arquivo:

```text
includes/relatorios.php
```

Os relatórios podem ser utilizados para apoiar o acompanhamento da quantidade de chamados, atendimentos, equipamentos e demais informações administrativas do Help Desk.

---

## 🔄 Comunicação AJAX

O projeto possui recursos de comunicação assíncrona para atualização e consulta de informações sem a necessidade de recarregar completamente as páginas.

Arquivo:

```text
ajax/buscar_respostas.php
```

Esse módulo é utilizado principalmente para consulta dinâmica de respostas e interações relacionadas aos chamados.

---

## 🗂️ Estrutura de Pastas

A estrutura atual do projeto é:

```text
helpdesk_prefeitura/
│
├── gerar_admin.php
├── helpdesk_prefeitura.sql
├── Help_TI_Borborema.vbs
├── index.php
├── perfil.php
├── README.md
├── treino.php
│
├── account/
│   ├── autocadastro.php
│   ├── esqueci_senha.php
│   ├── login.php
│   ├── logout.php
│   └── painel.php
│
├── admin/
│   ├── dispositivos.php
│   ├── gerenciar_setores.php
│   └── gerenciar_tecnicos.php
│
├── ajax/
│   └── buscar_respostas.php
│
├── api/
│   └── telemetria.php
│
├── assets/
│   └── img/
│       └── brasao.png
│
├── config/
│   └── conexao.php
│
├── includes/
│   ├── cards_admin.php
│   ├── cards_tecnico.php
│   ├── cards_usuario.php
│   ├── relatorios.php
│   │
│   └── components/
│       └── card_dispositivo.php
│
├── suporte/
│   ├── cadastro_chamados.php
│   ├── cadastro_problemas.php
│   ├── cadastro_usuario.php
│   ├── fila_chamados.php
│   ├── historicos_pcs.php
│   ├── hsitoricos_pcs.php
│   └── ver_chamado.php
│
├── usuario/
│   ├── abrir_chamado.php
│   ├── meus_chamados.php
│   └── ver_chamado.php
│
└── versoes-php/
    ├── link-php.txt
    └── php-7.4.33-Win32-vc15-x64.zip
```

---

## 📁 Descrição dos Diretórios

### `/account`

Responsável pelos recursos de autenticação e acesso ao sistema.

| Arquivo             | Função                                 |
| ------------------- | -------------------------------------- |
| `autocadastro.php`  | Cadastro de novos usuários             |
| `esqueci_senha.php` | Recuperação de senha                   |
| `login.php`         | Autenticação dos usuários              |
| `logout.php`        | Encerramento da sessão                 |
| `painel.php`        | Dashboard principal adaptado ao perfil |

---

### `/admin`

Módulo exclusivo para funções administrativas.

| Arquivo                  | Função                          |
| ------------------------ | ------------------------------- |
| `dispositivos.php`       | Gerenciamento de dispositivos   |
| `gerenciar_setores.php`  | Gestão de secretarias e setores |
| `gerenciar_tecnicos.php` | Gestão de técnicos e permissões |

---

### `/ajax`

Contém funcionalidades executadas por requisições AJAX.

| Arquivo                | Função                                   |
| ---------------------- | ---------------------------------------- |
| `buscar_respostas.php` | Busca dinâmica de respostas dos chamados |

---

### `/api`

Contém endpoints da aplicação destinados à comunicação externa ou integração com outros sistemas.

| Arquivo          | Função                                           |
| ---------------- | ------------------------------------------------ |
| `telemetria.php` | Recebimento/processamento de dados de telemetria |

---

### `/assets`

Armazena arquivos estáticos da aplicação.

Atualmente contém:

```text
assets/
└── img/
    └── brasao.png
```

O arquivo `brasao.png` representa a identidade visual utilizada pelo sistema.

---

### `/config`

Contém configurações de infraestrutura.

| Arquivo       | Função                        |
| ------------- | ----------------------------- |
| `conexao.php` | Conexão PDO com MySQL/MariaDB |

A conexão deve utilizar PDO e credenciais adequadas ao ambiente de instalação.

---

### `/includes`

Contém componentes reutilizáveis da interface e funcionalidades compartilhadas.

Arquivos principais:

```text
cards_admin.php
cards_tecnico.php
cards_usuario.php
relatorios.php
```

Também possui:

```text
includes/
└── components/
    └── card_dispositivo.php
```

O diretório permite centralizar componentes utilizados por diferentes módulos da aplicação.

---

### `/suporte`

Módulo destinado às atividades de atendimento e suporte técnico.

| Arquivo                  | Função                                              |
| ------------------------ | --------------------------------------------------- |
| `cadastro_chamados.php`  | Cadastro/processamento de chamados                  |
| `cadastro_problemas.php` | Cadastro de problemas                               |
| `cadastro_usuario.php`   | Cadastro de usuários                                |
| `fila_chamados.php`      | Fila geral de atendimento                           |
| `historicos_pcs.php`     | Histórico de computadores                           |
| `hsitoricos_pcs.php`     | Arquivo adicional relacionado aos históricos de PCs |
| `ver_chamado.php`        | Atendimento e visualização do chamado               |

> **Observação:** existem atualmente dois arquivos relacionados ao histórico de PCs: `historicos_pcs.php` e `hsitoricos_pcs.php`. Recomenda-se verificar se o segundo é realmente necessário ou se trata-se de uma duplicidade decorrente de erro de nomenclatura.

---

### `/usuario`

Módulo destinado aos usuários solicitantes.

| Arquivo             | Função                                 |
| ------------------- | -------------------------------------- |
| `abrir_chamado.php` | Abertura de novos chamados             |
| `meus_chamados.php` | Listagem dos chamados do usuário       |
| `ver_chamado.php`   | Visualização e interação com o chamado |

---

### `/versoes-php`

Diretório destinado aos arquivos relacionados à versão do PHP utilizada no ambiente do projeto.

```text
versoes-php/
├── link-php.txt
└── php-7.4.33-Win32-vc15-x64.zip
```

A versão atualmente disponibilizada no projeto é:

**PHP 7.4.33 — Windows 64 bits — VC15**

---

## 🛠️ Tecnologias Utilizadas

### Back-end

* PHP 7.4+
* PDO
* MySQL / MariaDB

### Front-end

* HTML5
* CSS3
* JavaScript
* Bootstrap 5.3
* AJAX

### Servidor

O sistema pode ser executado em ambientes locais ou servidores compatíveis com PHP e Apache, incluindo:

* XAMPP
* WAMP
* Laragon
* Apache + PHP + MySQL/MariaDB

---

## 🗄️ Banco de Dados

O projeto possui o arquivo:

```text
helpdesk_prefeitura.sql
```

Esse arquivo contém a estrutura do banco de dados necessária para instalação da aplicação.

Recomenda-se importar o banco utilizando ferramentas como:

* phpMyAdmin
* MySQL Workbench
* MariaDB Client
* Linha de comando MySQL/MariaDB

Após a criação do banco, as credenciais devem ser configuradas em:

```text
config/conexao.php
```

---

## ⚙️ Instalação

### 1. Requisitos

Recomenda-se utilizar:

* PHP 7.4 ou superior compatível com o projeto.
* Apache.
* MySQL ou MariaDB.
* Extensão PDO habilitada.
* Navegador moderno.

### 2. Copiar o projeto

Coloque a pasta do projeto no diretório público do servidor Apache.

Exemplo no XAMPP:

```text
C:\xampp\htdocs\helpdesk_prefeitura
```

### 3. Criar o banco de dados

Crie um banco de dados para a aplicação e importe:

```text
helpdesk_prefeitura.sql
```

### 4. Configurar a conexão

Edite:

```text
config/conexao.php
```

e informe:

* Servidor do banco.
* Nome do banco.
* Usuário.
* Senha.
* Demais parâmetros necessários.

### 5. Acessar o sistema

Após iniciar Apache e MySQL/MariaDB, acesse pelo navegador o endereço correspondente à instalação.

Exemplo:

```text
http://localhost/helpdesk_prefeitura/
```

---

## 👑 Criação do Administrador

O projeto possui o arquivo:

```text
gerar_admin.php
```

Esse script é destinado à criação inicial de uma conta administrativa.

Após criar a conta administrativa, recomenda-se **remover ou proteger o arquivo `gerar_admin.php`**, evitando que terceiros possam utilizá-lo indevidamente.

---

## 🖥️ Atalho para o Sistema

O arquivo:

```text
Help_TI_Borborema.vbs
```

é um script destinado à criação/utilização de atalho para facilitar o acesso ao sistema no ambiente Windows.

---

## 🧪 Arquivo de Treinamento

O projeto possui:

```text
treino.php
```

Esse arquivo deve ser utilizado para funcionalidades de treinamento, testes ou desenvolvimento conforme a finalidade definida durante a evolução do projeto.

Não é recomendado disponibilizar funcionalidades experimentais em ambiente de produção sem revisão.

---

## 🔐 Segurança

Para utilização em ambiente de produção, recomenda-se:

* Utilizar PDO com prepared statements.
* Nunca armazenar senhas em texto puro.
* Utilizar `password_hash()` para armazenamento das senhas.
* Utilizar `password_verify()` durante a autenticação.
* Validar e sanitizar dados recebidos pelos formulários.
* Implementar proteção contra CSRF.
* Controlar permissões por perfil.
* Validar todas as requisições AJAX e API.
* Proteger endpoints administrativos.
* Não deixar scripts de instalação ou criação de administrador acessíveis publicamente.
* Manter o PHP e o banco de dados atualizados.
* Evitar exposição de arquivos de configuração.
* Registrar ações administrativas e alterações importantes.

---

## 👥 Perfis do Sistema

O sistema trabalha com diferentes níveis de acesso:

### Usuário

Pode:

* Abrir chamados.
* Consultar seus próprios chamados.
* Enviar réplicas.
* Consultar respostas.
* Gerenciar seus dados cadastrais.

### Técnico

Pode:

* Visualizar a fila de chamados.
* Atender chamados.
* Alterar status.
* Enviar respostas.
* Consultar informações dos solicitantes.
* Consultar históricos relacionados ao suporte.

### Administrador

Possui acesso aos recursos administrativos, incluindo:

* Gestão de técnicos.
* Gestão de setores.
* Gestão de dispositivos.
* Gestão de usuários.
* Relatórios.
* Fila geral.
* Recursos de administração do sistema.

---

## 📌 Status dos Chamados

Os chamados utilizam estados para representar seu andamento no atendimento.

### `Novo`

Chamado recém-aberto e aguardando atendimento.

### `Em Andamento`

Chamado que já está sendo analisado ou atendido pela equipe de TI.

### `Fechado`

Chamado cujo atendimento foi concluído.

---

## 🔢 Protocolo dos Chamados

Cada chamado recebe um protocolo único.

Formato:

```text
YYYYMMDD-XXXX
```

Exemplo:

```text
20260818-0001
```

O protocolo facilita a identificação e o acompanhamento das solicitações.

---

## 📈 Evolução do Projeto

O Help Desk Borborema está estruturado para evoluir de um sistema tradicional de abertura e atendimento de chamados para uma plataforma mais ampla de gerenciamento do ambiente de TI municipal.

A estrutura atual já contempla:

* Gestão de chamados.
* Atendimento técnico.
* Gestão de usuários.
* Gestão de setores.
* Gestão de técnicos.
* Gestão de dispositivos.
* Histórico de computadores.
* Relatórios.
* Comunicação AJAX.
* API de telemetria.
* Componentes reutilizáveis.
* Dashboards específicos por perfil.

Essa organização permite adicionar novos recursos sem concentrar toda a aplicação em um único módulo.

---

## 📂 Arquivos Principais

| Arquivo                   | Finalidade                            |
| ------------------------- | ------------------------------------- |
| `index.php`               | Entrada/redirecionamento da aplicação |
| `perfil.php`              | Gerenciamento do perfil do usuário    |
| `gerar_admin.php`         | Criação inicial de administrador      |
| `helpdesk_prefeitura.sql` | Banco de dados                        |
| `Help_TI_Borborema.vbs`   | Atalho/automação para acesso          |
| `treino.php`              | Recursos de treinamento/testes        |
| `README.md`               | Documentação do projeto               |

---

## 🏛️ Help Desk Borborema

**Sistema de Gestão e Atendimento de TI da Prefeitura de Borborema**

O projeto tem como objetivo centralizar as solicitações de suporte técnico, melhorar o acompanhamento dos atendimentos e fornecer à equipe de Tecnologia da Informação ferramentas para organização, controle e monitoramento do ambiente tecnológico municipal.
