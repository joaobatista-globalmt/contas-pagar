# Checkpoint 2026-06-26 — Sistema Contas a Pagar

> **Data do checkpoint:** 26/06/2026
> **Status:** Sistema completo em produção (sistema antigo) + novo sistema Contas a Pagar construído do zero
> **Próxima sessão:** semana que vem (acesso remoto via internet)

---

## 📊 Resumo do que foi feito hoje

### Sistemas em produção

1. **Clube do Bolinha** (legado) — http://192.168.70.45/bolinha/ — Node.js + MariaDB
2. **Contas a Pagar** (novo) — http://192.168.70.45/contas/ — PHP 8.2 + MariaDB

### Estatísticas finais

| Métrica | Valor |
|---|---|
| **Sistema Contas a Pagar** | |
| Tabelas no banco | 10 + 1 migration |
| Controllers PHP | 7 |
| Libs PHP | 4 |
| Páginas públicas | 25+ |
| CSS files | 7 |
| Linhas de código (estimado) | ~7.000 |
| Commits Git | **11** (último `c482b32`) |
| Screenshots no manual | 15 |

### Banco `contas_pagar`

- 3 empresas (Globalmt, Teste, GLOBALMT TELECON)
- 4 usuários ativos (joao.batista/admin, eduardo/admin, secretaria/operador, carlos/aprovador)
- 30 contas (PENDENTE/APROVADA/PAGA/ATRASADA/CANCELADA)
- 11 fornecedores, 8 categorias

---

## 🕐 Cronograma de hoje (do mais antigo pro mais recente)

| Hora | Evento |
|---|---|
| 09:27 | Bom dia |
| 14:02 | João pede projeto Contas a Pagar |
| 14:05 | Decisões validadas (PHP, multi-empresa, multi-usuário, 20+ empresas, etc) |
| 14:09 | **Fase 1:** schema + seed + login + dashboard + layout |
| 14:25 | **Fase 2:** CRUD empresas + usuários + permissões |
| 14:28 | **Fase 3:** CRUD fornecedores + categorias |
| 14:37 | **Fase 4:** CRUD contas + aprovação + pagamento |
| 14:45 | Troca de senhas dos usuários para Glb@2026-* (únicas) |
| 14:50 | **Fase 5:** parcelamento |
| 14:57 | **Fase 6:** recorrência com geração do mês |
| 15:09 | **Fase 8:** relatórios (5 tipos) |
| 15:13 | **Fase 9:** exportação CSV/PDF |
| 15:17 | **Fase 10:** upload de anexos PDF |
| 15:25 | Push para GitHub (10 commits) via SSH deploy key |
| 15:31 | MEMORY.md limpo (sem senhas) |
| 15:34 | Criação do MANUAL.md (20 KB) |
| 15:45 | 15 screenshots no GitHub |
| 15:47 | Pergunta sobre usuário/senha do admin → bug encontrado |
| 15:52 | **Bugfix Nginx ERR_TOO_MANY_REDIRECTS** resolvido (commit c482b32) |
| 16:49 | Discussão sobre acesso remoto (DNS/VPN) |
| 16:50 | Plano de VPN WireGuard definido |
| 16:51 | Decisão: deixar VPN pra semana que vem |
| 16:53 | **Checkpoint criado** ← você está aqui |

---

## 🔑 Credenciais atuais (em gerenciador seguro, NÃO aqui)

| Usuário | E-mail | Senha (resumo) |
|---|---|---|
| Admin | joao.batista@globalmt.com.br | Glb@2026-JB |
| Admin | eduardo@globalmt.com.br | Glb@2026-ES |
| Operador | secretaria@globalmt.com.br | Glb@2026-TS |
| Aprovador | carlos@globalmt.com.br | Glb@2026-CA |

**MySQL root:** `root0712` (EM USO EM SCRIPTS, AINDA NÃO TROCADO — tarefa pendente)

---

## 🛠️ Decisões técnicas aplicadas

### Stack do Contas a Pagar

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.2 (MVC leve, sem framework) |
| Banco | MariaDB 10.11 (instância compartilhada) |
| Server | PHP-FPM 8.2 (master + pool www) |
| Web server | Nginx 1.22.1 (proxy reverso) |
| PDF | wkhtmltopdf 0.12.6 |
| Backup | mysqldump via cron (diário 03:30 + semanal domingo) |

### Estrutura de pastas

```
/home/sistema/contas-pagar/
├── public/                # Document root do Nginx
│   ├── assets/css/        # 7 CSS files
│   ├── assets/js/         # app.js (máscaras, helpers)
│   ├── *.php              # 25+ entry points
├── src/
│   ├── config/            # app.php, database.php
│   ├── controllers/       # 7 controllers
│   ├── lib/               # 4 libs (Auth, Permissao, Validator, Uploader, CsvExporter)
│   └── views/             # templates
├── database/              # schema.sql, seed.sql, migracao-parcela.php
├── uploads/anexos/        # PDFs enviados (chmod 750, dono www-data)
├── logs/                  # logs do app
├── MANUAL.md              # Manual de uso (20 KB)
└── docs/screenshots/      # 15 screenshots
```

### Multi-tenancy

- Cada usuário vinculado a 1+ empresas via `usuarios_empresas`
- Cada empresa tem perfil próprio (admin/operador/aprovador/pagador/visualizador)
- Toda query filtra por `empresa_id` automaticamente
- Dropdown no header permite trocar de empresa em 1 click

### Fluxo de Contas a Pagar

```
PENDENTE → APROVADA → PAGA
   ↓          ↓
CANCELADA  CANCELADA
```

- Apenas **PENDENTE/APROVADA** podem ser editadas
- Apenas **PENDENTE** pode ser aprovada
- Apenas **PENDENTE/APROVADA** podem ser pagas
- Apenas **PENDENTE** pode ser excluída
- **PAGA** e **CANCELADA** são imutáveis

### Segurança implementada

- ✅ Bcrypt para senhas (custo 10)
- ✅ Senhas únicas por usuário (Glb@2026-*)
- ✅ Multi-tenant com filtro automático por empresa_id
- ✅ 5 perfis de permissão granulares
- ✅ Validação CNPJ/CPF/email/UF
- ✅ Validação MIME + magic bytes em uploads
- ✅ Permissão granular de exclusão (só admin ou dono)
- ✅ Audit log em log_operacoes
- ✅ X-Frame-Options, X-Content-Type-Options (básico)

### Git

- Repositório: https://github.com/joaobatista-globalmt/contas-pagar
- Branch: `main`
- Deploy key SSH: `id_ed25519_contas` (escopo só nesse repo)
- 11 commits, sem tag formal

### Nginx config (fix do loop)

```nginx
location = /contas/ {
    alias /home/sistema/contas-pagar/public/index.php;
    fastcgi_pass unix:/run/php/php-fpm.sock;
    # ... → PHP decide autenticado/não
}
```

---

## 🐛 Bugs encontrados e corrigidos

### 1. CNPJs do seed eram matematicamente inválidos
- **Sintoma:** Validador rejeitava `11.222.333/0001-44` no seed
- **Causa:** Script de geração de CNPJ estava com bug no cálculo de DV
- **Fix:** Script PHP `corrigir-cnpjs-seed.php` atualizou 11 CNPJs
- **Prevenção:** Novo `gerar-cnpj-valido.php` valida antes de usar

### 2. Loop infinito de redirect no Nginx
- **Sintoma:** ERR_TOO_MANY_REDIRECTS ao acessar `/contas/`
- **Causa:** `location = /contas/ { return 301 ... /contas/login.php; }` sempre redirecionava
- **Fix:** Apontar `location = /contas/` pro `index.php` via PHP-FPM
- **Commit:** `c482b32`

### 3. Sessão PHP vazia em tentativas de login
- **Sintoma:** Login OK mas dashboard redirecionava pra login
- **Causa:** Cache do navegador + bug anterior (Nginx)
- **Fix:** Hard reload (Ctrl+Shift+R) após correção do Nginx

### 4. Permissão genérica de excluir anexo
- **Sintoma:** Thainara (operadora) podia excluir anexo do João
- **Fix:** Refinar regra — admin pode tudo, outros só os próprios
- **Método:** Uploader::excluir() checa `uploaded_by`

---

## 📦 Backup deste checkpoint

Localização: `/home/sistema/backups/checkpoint-2026-06-26/`

| Arquivo | Tamanho | Conteúdo |
|---|---|---|
| `contas-pagar-codigo.tar.gz` | 3.3 MB | Código completo (481 arquivos) |
| `contas-pagar-db.sql.gz` | 7.6 KB | Dump do banco `contas_pagar` |
| `clube-bolinha-db.sql.gz` | 15 KB | Dump do banco `clube_bolinha` (sistema antigo) |
| `servidor/nginx-contas.conf` | 1.4 KB | Configuração Nginx |
| `servidor/crontab.txt` | 462 B | Cron jobs |
| `servidor/logrotate-php-contas.txt` | 284 B | Configuração logrotate |
| `servidor/backup-contas-pagar` | 1.6 KB | Script de backup |

### Como restaurar (se necessário)

```bash
# Restaurar código
cd /home/sistema/
tar -xzf /home/sistema/backups/checkpoint-2026-06-26/contas-pagar-codigo.tar.gz

# Restaurar banco (cuidado: sobrescreve!)
gunzip < /home/sistema/backups/checkpoint-2026-06-26/contas-pagar-db.sql.gz | mysql -u contas_app -pcontas_app_2026 contas_pagar

# Restaurar configs
sudo cp /home/sistema/backups/checkpoint-2026-06-26/servidor/nginx-contas.conf /etc/nginx/snippets/contas.conf
sudo nginx -t && sudo systemctl reload nginx
crontab /home/sistema/backups/checkpoint-2026-06-26/servidor/crontab.txt
sudo cp /home/sistema/backups/checkpoint-2026-06-26/servidor/logrotate-php-contas.txt /etc/logrotate.d/php-contas-pagar
sudo cp /home/sistema/backups/checkpoint-2026-06-26/servidor/backup-contas-pagar /usr/local/bin/ && sudo chmod 755 /usr/local/bin/backup-contas-pagar
```

---

## 📋 Pendências para próxima sessão (semana que vem)

### VPN / Acesso Remoto (João vai decidir)

Você quer **acesso seguro pela internet** pra equipe. Opções:

1. **WireGuard VPN** (recomendado):
   - Invisível na internet (servidor não responde a scanners)
   - 1 porta UDP aberta (51820)
   - Funciona em celular/PC/Mac/Linux
   - Mais simples de operar
   - Precisa: app no cliente, config de chave por usuário

2. **HTTPS público** (alternativa se VPN não servir):
   - Mais complexo de proteger
   - Requer: Let's Encrypt, rate limit, 2FA, WAF

**Antes de começar, preciso saber:**
1. Você tem acesso ao roteador da rede? (pra abrir porta)
2. IP público é fixo ou dinâmico?
3. Equipe tem celular/PC pra app WireGuard?

### Segurança pendente (URGENTE)

- ⚠️ **CRÍTICO:** Trocar senha `root0712` do MySQL (usada em scripts)
- Criar user MySQL dedicado pra backup (em vez de root)
- Atualizar scripts que usam `MYSQL_PWD=root0712`

### Melhorias opcionais

- Testes de stress (1000+ contas)
- Containerização (Docker)
- Mobile-first CSS (mais responsividade)
- API REST pra integração com ERP
- Notificações por e-mail/SMS de contas próximas do vencimento
- Exportador OFX pra conciliação bancária
- Integração com Open Finance

---

## 📞 Contatos e referências

- **Servidor:** 192.168.70.45 (Debian 12)
- **Admin:** João Batista (joao@globalmt.com.br)
- **GitHub:** https://github.com/joaobatista-globalmt
- **Repos:**
  - clube-bolinha: https://github.com/joaobatista-globalmt/clube-bolinha
  - contas-pagar: https://github.com/joaobatista-globalmt/contas-pagar

---

## ✅ Estado do checkpoint

- ✅ Todos os sistemas operacionais
- ✅ Banco de dados íntegro
- ✅ Código commitado no GitHub (2 repos)
- ✅ Backups locais + remotos (GitHub)
- ✅ Backup completo deste checkpoint criado
- ✅ Manual de uso escrito
- ✅ MEMORY.md atualizado

**Pode parar tranquilo — tudo salvo.** 💾
