# 🗳️ Sistema de Votação Institucional - Câmara

Sistema completo de votação para Câmara Municipal, desenvolvido em PHP + MySQL, compatível com hospedagem compartilhada (Hostinger).

## 📋 Características

- ✅ **PHP 8+** (sem frameworks)
- ✅ **MySQL** com PDO e Prepared Statements
- ✅ **Tailwind CSS** via CDN
- ✅ **Heroicons** via CDN
- ✅ **JavaScript puro** (Fetch API)
- ✅ **Compatível com Hostinger** (sem Node.js, sem WebSockets)
- ✅ **Design responsivo e moderno**
- ✅ **Segurança**: Validação, sanitização, proteção contra SQL Injection

## 🚀 Funcionalidades

### 🔐 Autenticação Admin
- Login com sessão PHP
- Proteção de rotas administrativas
- Logout seguro

### 📝 Gestão de Votação
- Criar nova votação
- Abrir/Encerrar votação
- Apenas uma votação ativa por vez
- Bloquear votos quando encerrada

### 🙋‍♂️ Registro de Voto
- Nome do votante (obrigatório)
- CPF com validação (obrigatório)
- Cargo (opcional)
- Upload de foto (opcional, JPG/PNG/GIF, máx. 2MB)
- Opções: **SIM** ou **NÃO**
- Prevenção de voto duplicado por CPF
- Registro de data, hora e IP

### 🖥️ Painéis

#### Painel de Votação (Público)
- Status da votação (Aberta/Encerrada)
- Botões grandes "SIM" e "NÃO"
- Confirmação visual do voto
- Interface responsiva

#### Painel de Resultados (Tempo Real)
- Atualização automática a cada 3 segundos
- Exibe:
  - Total de votos
  - Total SIM e NÃO
  - Percentuais
- Layout otimizado para TV/Telão
- Barra de progresso visual

#### Painel Administrativo
- Listagem de todas as votações
- Listagem de votantes com foto
- Estatísticas em tempo real
- Ações:
  - Abrir votação
  - Encerrar votação
  - Resetar votos
  - Criar nova votação

## 📁 Estrutura do Projeto

```
SISTEMA DE VOTAÇÃO/
├── config/
│   ├── database.php          # Configuração do banco de dados
│   └── functions.php         # Funções auxiliares
├── admin/
│   ├── login.php            # Página de login
│   ├── dashboard.php        # Painel administrativo
│   └── logout.php           # Logout
├── votacao/
│   ├── index.php            # Página pública de votação
│   └── votar.php            # Processamento do voto
├── painel/
│   ├── resultados.php       # Painel de resultados (público)
│   └── api_resultados.php   # API JSON para atualização
├── uploads/                 # Fotos dos votantes
│   ├── .htaccess           # Proteção do diretório
│   └── index.php           # Prevenir listagem
├── sql/
│   └── schema.sql          # Script de criação do banco
├── index.php               # Redirecionamento
└── README.md               # Este arquivo
```

## 🔧 Instalação na Hostinger

### Passo 1: Upload dos Arquivos

1. Acesse o **File Manager** no painel da Hostinger
2. Navegue até a pasta `public_html` (ou `htdocs`)
3. Faça upload de **todos os arquivos** do projeto
4. Mantenha a estrutura de pastas intacta

### Passo 2: Criar Banco de Dados

1. No painel da Hostinger, acesse **MySQL Databases**
2. Clique em **Create Database**
3. Anote o nome do banco criado (ex: `u123456789_votacao`)
4. Crie um usuário MySQL e anote:
   - Nome do usuário
   - Senha
   - Host (geralmente `localhost`)

### Passo 3: Importar Schema

1. No painel da Hostinger, acesse **phpMyAdmin**
2. Selecione o banco de dados criado
3. Vá na aba **Importar**
4. Escolha o arquivo `sql/schema.sql`
5. Clique em **Executar**

**OU** execute o SQL manualmente copiando o conteúdo de `sql/schema.sql`

### Passo 4: Configurar Conexão

1. Abra o arquivo `config/database.php`
2. Atualize as constantes:

```php
define('DB_HOST', 'localhost');           // Geralmente 'localhost'
define('DB_NAME', 'u123456789_votacao');  // Nome do seu banco
define('DB_USER', 'u123456789_admin');    // Seu usuário MySQL
define('DB_PASS', 'sua_senha_aqui');      // Sua senha MySQL
```

### Passo 5: Configurar Permissões

1. No **File Manager**, navegue até a pasta `uploads`
2. Clique com botão direito → **Change Permissions**
3. Defina como **755** (ou **rwxr-xr-x**)
4. Salve

### Passo 6: Acessar o Sistema

1. **Painel Admin**: `https://seudominio.com/admin/login.php`
   - Usuário padrão: `admin`
   - Senha padrão: `admin123`
   - ⚠️ **IMPORTANTE**: Altere a senha após o primeiro acesso!

2. **Votação Pública**: `https://seudominio.com/votacao/index.php`

3. **Resultados**: `https://seudominio.com/painel/resultados.php`

## 🔐 Segurança

### Alterar Senha do Admin

Para alterar a senha padrão, execute no phpMyAdmin:

```sql
UPDATE administradores 
SET senha = '$2y$10$SUA_NOVA_SENHA_HASH_AQUI' 
WHERE usuario = 'admin';
```

Para gerar o hash da senha, use:

```php
<?php
echo password_hash('sua_nova_senha', PASSWORD_BCRYPT);
?>
```

Execute este código em um arquivo PHP temporário e depois delete-o.

### Recomendações

- ✅ Altere a senha padrão do admin
- ✅ Use HTTPS (SSL) se disponível
- ✅ Mantenha o PHP atualizado
- ✅ Faça backups regulares do banco de dados
- ✅ Não compartilhe credenciais de acesso

## 📱 Uso do Sistema

### 1. Criar uma Votação

1. Acesse o painel admin
2. Preencha o formulário "Criar Nova Votação"
3. Informe título e descrição (opcional)
4. Clique em "Criar Votação"

### 2. Abrir Votação

1. Na lista de votações, clique em **"Abrir"** na votação desejada
2. A votação ficará ativa e disponível para votação pública
3. Qualquer outra votação ativa será automaticamente encerrada

### 3. Votar

1. Acesse a página pública de votação
2. Preencha:
   - Nome completo
   - CPF (será validado)
   - Cargo (opcional)
   - Foto (opcional)
3. Escolha **SIM** ou **NÃO**
4. Clique em "Confirmar Voto"

### 4. Acompanhar Resultados

1. Acesse o painel de resultados
2. Os dados são atualizados automaticamente a cada 3 segundos
3. Ideal para exibição em TV/Telão

### 5. Encerrar Votação

1. No painel admin, clique em **"Encerrar Votação"**
2. Após encerrada, novos votos não serão aceitos
3. Os resultados permanecem disponíveis

## 🎨 Personalização

### Cores

O sistema usa Tailwind CSS via CDN. Para personalizar cores, edite as classes nos arquivos PHP:

- **Azul primário**: `bg-blue-600`, `text-blue-600`
- **Verde (SIM)**: `bg-green-500`, `text-green-600`
- **Vermelho (NÃO)**: `bg-red-500`, `text-red-600`

### Logo/Header

Edite os arquivos `admin/dashboard.php`, `votacao/index.php` e `painel/resultados.php` para adicionar seu logo.

## 🐛 Troubleshooting

### Erro de Conexão com Banco

- Verifique as credenciais em `config/database.php`
- Confirme que o banco foi criado
- Verifique se o usuário tem permissões

### Upload de Fotos Não Funciona

- Verifique permissões da pasta `uploads` (755)
- Confirme que a pasta existe
- Verifique limite de upload no PHP (php.ini)

### Votos Não Aparecem

- Verifique se a votação está com status "aberta"
- Confirme que não há erro de JavaScript no console
- Verifique logs de erro do PHP

### Página em Branco

- Ative exibição de erros no PHP (apenas em desenvolvimento)
- Verifique logs de erro no painel da Hostinger
- Confirme que todos os arquivos foram enviados

## 📞 Suporte

Para problemas ou dúvidas:
1. Verifique os logs de erro do PHP
2. Confirme que todas as etapas de instalação foram seguidas
3. Verifique a documentação do PHP e MySQL

## 📄 Licença

Este sistema foi desenvolvido para uso institucional.

---

**Desenvolvido com ❤️ para Câmaras Municipais**
