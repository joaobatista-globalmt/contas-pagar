# Manual de Uso — Sistema Contas a Pagar

> **Versão:** 1.0.0
> **Data:** 26/06/2026
> **Sistema:** Gestão multi-empresa de Contas a Pagar

---

## 📖 Índice

1. [Visão Geral](#1-visão-geral)
2. [Acesso ao Sistema](#2-acesso-ao-sistema)
3. [Dashboard](#3-dashboard)
4. [Cadastros Base](#4-cadastros-base)
5. [Contas a Pagar — CRUD Completo](#5-contas-a-pagar--crud-completo)
6. [Fluxo de Aprovação e Pagamento](#6-fluxo-de-aprovação-e-pagamento)
7. [Parcelamento](#7-parcelamento)
8. [Recorrências](#8-recorrências)
9. [Anexos (PDF)](#9-anexos-pdf)
10. [Relatórios](#10-relatórios)
11. [Exportação CSV/PDF](#11-exportação-csvpdf)
12. [Gestão de Usuários e Empresas](#12-gestão-de-usuários-e-empresas)
13. [Perfis de Permissão](#13-perfis-de-permissão)
14. [FAQ — Dúvidas Frequentes](#14-faq--dúvidas-frequentes)
15. [Suporte](#15-suporte)

---

## 1. Visão Geral

O **Sistema Contas a Pagar** é uma plataforma web multi-empresa para gestão financeira. Permite:

- ✅ Cadastrar **fornecedores, categorias e empresas**
- ✅ **Lançar contas a pagar** (com parcelamento e recorrência)
- ✅ Controlar **aprovação e pagamento** (fluxo PENDENTE → APROVADA → PAGA)
- ✅ Anexar **PDFs da Nota Fiscal**
- ✅ Gerar **5 tipos de relatórios** com exportação CSV/PDF
- ✅ Acesso **multi-empresa** com troca via dropdown

### 1.1 Stack Técnica

- **Backend:** PHP 8.2 + MariaDB 10.11 + PHP-FPM
- **Frontend:** HTML5 + CSS3 + Vanilla JS (sem framework pesado)
- **Servidor:** Linux Debian 12
- **Geração de PDF:** `wkhtmltopdf`

### 1.2 Glossário Rápido

| Termo | Significado |
|---|---|
| **Conta a pagar** | Um boleto/fatura/compra que sua empresa precisa pagar |
| **Fornecedor** | Quem você paga (locadora, fornecedor de energia, contador, etc) |
| **Categoria** | Agrupamento da despesa (Aluguel, Energia, Internet, Impostos...) |
| **Recorrência** | Conta que se repete todo mês (aluguel, internet, contador) |
| **Parcelamento** | Compra dividida em N vezes com vencimentos mensais |
| **Multi-empresa** | Sistema atende várias empresas (Globalmt, Teste, etc) num único login |

---

## 2. Acesso ao Sistema

### 2.1 URL de Acesso

```
http://192.168.70.45/contas/
```

### 2.2 Primeiro Login

1. Abra a URL no navegador
2. Digite **e-mail** e **senha**
3. Clique **Entrar**

![Tela de login](screenshots/01-login.png)

> **Esqueceu a senha?** Procure o administrador do sistema. Não há auto-recuperação por segurança.

### 2.3 Trocar de Empresa

Se você tem acesso a mais de uma empresa, use o **dropdown no canto superior direito**:

```
┌─────────────────────────────────┐
│  Empresa: [Globalmt        ▼]   │  ← clique aqui
└─────────────────────────────────┘
```

A troca é **automática** (a tela recarrega). Todas as listas, cards e relatórios passam a mostrar dados da nova empresa.

### 2.4 Logout

Clique no botão **"Sair"** (canto superior direito). A sessão é encerrada imediatamente.

---

## 3. Dashboard

Ao logar, você vê o **Dashboard** com 4 cards principais:

![Dashboard](screenshots/02-dashboard.png)

| Card | Cor | Significado |
|---|---|---|
| **Vencem hoje** | 🟦 Azul | Valor total das contas que vencem **hoje** |
| **Próximos 7 dias** | 🟨 Amarelo | Contas que vencem nos **próximos 7 dias** |
| **Total no mês** | 🟩 Verde | Soma de todas as contas pendentes/aprovadas do mês |
| **Atrasadas** | 🟥 Vermelho | Contas que **já venceram** e não foram pagas |

### 3.1 Abaixo dos Cards

Duas tabelas:

- **⚠️ Contas Atrasadas** — listadas com dias de atraso em destaque
- **📅 Próximos Vencimentos** — próximas 10 contas a vencer

Clique no **nome da conta** pra abrir os detalhes completos.

---

## 4. Cadastros Base

### 4.1 Cadastrar Empresa (só admin)

> **Quando usar:** Ao começar a usar o sistema, ou quando abrir nova empresa.

1. Menu lateral → **Empresas** → **+ Nova Empresa**
2. Preencha:
   - **Razão Social*** (obrigatório)
   - **Nome Fantasia**
   - **CNPJ** (validado matematicamente)
   - Endereço, Cidade, UF, CEP, Telefone, E-mail
3. Clique **Salvar**

![Formulário de empresa](screenshots/15-empresas.png)

> 💡 **Dica:** O CNPJ é validado pelo algoritmo real (DV). Se aparecer "CNPJ inválido", confira os dígitos verificadores.

### 4.2 Cadastrar Fornecedor

> **Quando usar:** Ao começar a usar, ou ao pagar um novo fornecedor pela primeira vez.

1. Menu lateral → **Fornecedores** → **+ Novo Fornecedor**
2. Preencha:
   - **Nome*** (obrigatório)
   - **Tipo de Pessoa** (PF = CPF ou PJ = CNPJ)
   - **CPF/CNPJ** (validado conforme tipo)
   - **E-mail**, **Telefone**
   - **Dados Bancários**: Banco, Agência, Conta, **Chave PIX**
   - **Observações**
3. Clique **Salvar**

![Lista de fornecedores](screenshots/06-fornecedores.png)

> 💡 **Boa prática:** Preencha a chave PIX mesmo que hoje não use — facilita pagamentos futuros via PIX.

### 4.3 Cadastrar Categoria

> **Quando usar:** No início, pra organizar suas despesas.

1. Menu lateral → **Categorias** → **+ Nova Categoria**
2. Preencha:
   - **Nome*** (ex: "Aluguel", "Energia Elétrica", "Internet")
   - **Tipo**: DESPESA, IMPOSTO, SERVIÇO, PRODUTO, OUTROS
   - **Cor**: picker de cor (aparece nos relatórios)
3. Clique **Salvar**

> 💡 **Sugestão de categorias iniciais:** Aluguel, Energia Elétrica, Internet/Telefonia, Material de Escritório, Impostos, Honorários Contábeis, Marketing, Fornecedores de Produtos.

### 4.4 Cadastrar Usuário (só admin)

1. Menu lateral → **Usuários** → **+ Novo Usuário**
2. Preencha:
   - **Nome*** e **E-mail***
   - **Senha** (mínimo 6 caracteres)
   - **Perfil padrão** (admin/operador/aprovador/pagador/visualizador)
3. **Vincule a empresas** marcando as checkboxes na tabela
4. Escolha o **perfil por empresa** (pode ser diferente do padrão)
5. Clique **Salvar**

![Formulário de usuário](screenshots/16-usuarios.png)

> ⚠️ **Importante:** Cada usuário pode ter perfis diferentes em empresas diferentes. Ex: Carlos pode ser `aprovador` na Globalmt e `visualizador` em outra.

---

## 5. Contas a Pagar — CRUD Completo

### 5.1 Listar Contas

Menu → **Contas a Pagar**. Lista completa com filtros:

- **Status** (PENDENTE / APROVADA / PAGA / ATRASADA / CANCELADA)
- **Período** (data início / fim)
- **Fornecedor** (dropdown)
- **Categoria** (dropdown)
- **Busca** textual (descrição ou número do documento)

![Lista de contas](screenshots/03-contas-lista.png)

No rodapé da tabela, **Total** = soma de todas as contas filtradas.

### 5.2 Lançar Nova Conta

Menu → **Contas a Pagar** → **+ Nova Conta** (ou no formulário)

Preencha:
- **Descrição*** (ex: "Aluguel julho/2026")
- **Nº Documento / NF** (opcional, ex: "NF-12345")
- **Fornecedor** (opcional, dropdown)
- **Categoria** (opcional)
- **Valor Total*** (com máscara R$)
- **Número de Parcelas** (1 = à vista; N = gera N parcelas mensais)
- **Data de Vencimento***
- **Forma de Pagamento** (prevista)
- **Observações**

Clique **Salvar**. A conta vai para status **PENDENTE**.

![Formulário de conta](screenshots/04-conta-nova.png)

### 5.3 Ver Detalhes de uma Conta

Clique no **nome da conta** na lista. A tela de detalhe mostra:

- **Status** com badge colorido
- **Valor** e **Valor Pago**
- Descrição, documento, fornecedor, categoria
- Datas (emissão, vencimento, pagamento)
- Quem criou, aprovou, pagou
- **Lista de anexos** (PDFs da NF)
- **Histórico de ações** (logs)

![Detalhe da conta](screenshots/05-conta-detalhe.png)

### 5.4 Editar Conta

> Só contas com status **PENDENTE** ou **APROVADA** podem ser editadas.

Na tela de detalhe → **Editar** → ajuste os campos → **Salvar**.

### 5.5 Excluir Conta

> Só contas com status **PENDENTE** podem ser excluídas (após aprovação, faça Cancelar).

Na tela de detalhe → **Excluir** → confirme.

---

## 6. Fluxo de Aprovação e Pagamento

Toda conta passa por **3 estados**:

```
┌───────────┐    ┌──────────┐    ┌──────┐
│ PENDENTE  │ →  │ APROVADA │ →  │ PAGA │
└───────────┘    └──────────┘    └──────┘
```

### 6.1 Aprovar uma Conta

> **Quem pode:** Usuários com perfil `aprovador` ou `admin`.

Na tela de detalhe → **✓ Aprovar** → pronto. Status muda pra **APROVADA** com data/hora.

### 6.2 Pagar uma Conta

> **Quem pode:** Usuários com perfil `pagador` ou `admin`.

1. Na tela de detalhe → **💰 Pagar**
2. Abre um modal. Preencha:
   - **Data do Pagamento*** (default: hoje)
   - **Valor Pago*** (default: valor da conta)
   - **Forma de Pagamento*** (PIX, Boleto, Transferência, etc)
3. **Confirmar Pagamento**

Status muda pra **PAGA** com data, valor pago, forma, e quem pagou.

### 6.3 Cancelar uma Conta

> **Quem pode:** Perfis `aprovador`, `pagador` ou `admin`.

Na tela de detalhe → **✗ Cancelar** → confirme.

Útil para: contas duplicadas, erros de lançamento, contas antigas que não vão mais ser pagas.

---

## 7. Parcelamento

### 7.1 Quando Usar

Compra dividida em N vezes (cartão de crédito, financiamento).

### 7.2 Como Lançar

1. Menu → **Contas a Pagar** → **+ Nova Conta**
2. Preencha normalmente
3. Em **Número de Parcelas**, coloque `3` (ou quantas forem)
4. **Data de Vencimento** = data da **primeira** parcela
5. Clique **Salvar**

### 7.3 O que Acontece

- É criada **1 conta "pai"** (agrupadora, valor 0, status CANCELADA)
- São geradas **N contas "filhas"** (cada uma é uma parcela real)
- Cada filha tem vencimento **+1 mês** da anterior
- O valor total é dividido igualmente (primeira parcela absorve arredondamento)

**Exemplo: Notebook R$ 1.500 em 3x**
| ID | Parcela | Vencimento | Valor |
|---|---|---|---|
| 32 | (pai) | — | R$ 0,00 |
| 33 | 1/3 | 15/07/2026 | R$ 500,00 |
| 34 | 2/3 | 15/08/2026 | R$ 500,00 |
| 35 | 3/3 | 15/09/2026 | R$ 500,00 |

### 7.4 Cada Parcela é Independente

Você pode aprovar/pagar/cancelar **cada parcela separadamente**.

---

## 8. Recorrências

### 8.1 Quando Usar

Contas que se repetem automaticamente (ex: aluguel mensal, internet, contador).

### 8.2 Cadastrar Template

1. Menu → **Recorrências** → **+ Nova Recorrência**
2. Preencha:
   - **Descrição*** (ex: "Aluguel sala comercial")
   - Fornecedor, Categoria
   - **Valor***
   - **Dia de Vencimento*** (1-31; se mês não tem o dia, usa o último dia)
   - **Periodicidade**: MENSAL / BIMESTRAL / TRIMESTRAL / SEMESTRAL / ANUAL
   - **Data de Início***
   - **Data de Fim** (opcional — quando parar)
   - Status (Ativa/Inativa)
3. Clique **Salvar**

### 8.3 Gerar Contas do Mês

> **Quem pode:** Qualquer operador ou acima.

**NÃO é automático.** Você precisa clicar no botão pra gerar:

1. Menu → **Recorrências**
2. No card azul no topo da página, escolha o **Mês** (AAAA-MM)
3. Clique **⚡ Gerar Contas do Mês**

O sistema:
- ✅ Percorre todas as recorrências ativas
- ✅ Verifica a periodicidade
- ✅ Verifica se já gerou neste mês (evita duplicar)
- ✅ Cria 1 conta por recorrência qualificada
- ✅ Atualiza `ultima_geracao` no template

**Saída típica:**
```
✅ Geração do mês 2026-07 concluída:
• 3 conta(s) gerada(s)
• 1 pulada(s)

Detalhes:
  • Aluguel sala comercial: ✓ gerada (R$ 2500.00, vence 2026-07-05)
  • Internet fibra: ✓ gerada (R$ 199.90, vence 2026-07-15)
  • Energia: ✓ gerada (R$ 850.00, vence 2026-07-20)
  • IPVA bimestral: → pulada (Periodicidade não inclui este mês)
```

![Tela de recorrências](screenshots/09-recorrencia.png)

> 💡 **Boa prática:** Gere as contas no **começo de cada mês** (ou no último dia útil do mês anterior) pra ter tempo de aprovar/pagar antes do vencimento.

---

## 9. Anexos (PDF)

### 9.1 Upload de NF

1. Abra a **conta** (tela de detalhe)
2. Na seção **📎 Anexos**, escolha o arquivo PDF (até 10MB)
3. Clique **📤 Enviar PDF**

O arquivo é salvo em `anexos/{empresa}/{conta}/` e fica disponível pra download.

### 9.2 Validações Automáticas

O sistema rejeita:
- ❌ Arquivos não-PDF (mesmo com extensão `.pdf`)
- ❌ Arquivos maiores que 10MB
- ❌ PDFs corrompidos (validação de magic bytes `%PDF`)

### 9.3 Download e Exclusão

- **Ver/Baixar:** clique no nome do arquivo
- **Excluir:** clique no 🗑️ (só admin ou quem fez upload)

---

## 10. Relatórios

Menu → **Relatórios** → escolha o tipo:

### 10.1 Por Período

Cards com Total / Pago / A Pagar / Atrasado + tabela detalhada.

Filtros: data início/fim, status.

![Relatório por período](screenshots/11-relatorio-periodo.png)

### 10.2 Por Categoria

Ranking de categorias por valor gasto. Inclui **barras de progresso coloridas**.

Útil pra responder: *"onde nosso dinheiro está indo?"*

### 10.3 Por Fornecedor

Ranking de fornecedores (igual categoria, mas por fornecedor).

Útil pra: *"gastamos mais com quem?"*

### 10.4 Fluxo de Caixa

Projeção dos **próximos 30/60/90/180 dias**. Visão **mensal** e **semanal**.

![Fluxo de caixa](screenshots/13-relatorio-fluxo.png)

Útil pra: *"quanto vai sair do caixa nos próximos meses?"*

### 10.5 Atrasadas

Lista completa de contas vencidas **com dados de contato do fornecedor** (e-mail, telefone).

Útil pra: **ligar/cobrar** fornecedores atrasados.

![Contas atrasadas](screenshots/14-relatorio-atrasadas.png)

---

## 11. Exportação CSV/PDF

Em qualquer relatório, no topo da página tem os botões:

```
┌──────────────────┐  ┌──────────────────┐
│ 📥 Baixar CSV    │  │ 📄 Baixar PDF    │
└──────────────────┘  └──────────────────┘
```

- **CSV** abre direto em Excel/LibreOffice. Formato BR (separador `;`, datas `dd/mm/yyyy`, valores `R$ 1.234,56`)
- **PDF** gera arquivo imprimível via `wkhtmltopdf` com layout profissional

> 💡 Use CSV pra manipular dados em Excel. Use PDF pra apresentar/enviar por e-mail.

---

## 12. Gestão de Usuários e Empresas

> **Permissão:** Apenas usuários com perfil **admin** (naquela empresa) veem os menus **Usuários** e **Empresas**.

### 12.1 Criar Nova Empresa

Útil quando o grupo econômico tem nova razão social (CNPJ).

1. **Empresas** → **+ Nova Empresa**
2. Preencha (veja seção 4.1)
3. **Próximo passo:** vincule usuários existentes à nova empresa (ou crie novos usuários com vínculo)

### 12.2 Vincular Usuário a Nova Empresa

1. **Usuários** → Editar (no usuário)
2. Role até a seção **🏭 Vínculos com Empresas**
3. Marque as empresas + escolha perfil
4. Salvar

### 12.3 Resetar Senha de Usuário

1. **Usuários** → Editar
2. Em **Nova Senha**, digite a nova senha (min 6 caracteres)
3. Salvar

> 💡 **Dica:** Não anote a senha em lugar acessível ao usuário — envie por canal seguro (SMS, telefone, pessoalmente).

### 12.4 Desativar vs Excluir

- **Desativar:** marca como inativo, mas mantém histórico. Recomendado.
- **Excluir:** remove definitivamente. Só faça se foi cadastro errado.

---

## 13. Perfis de Permissão

Cada usuário tem **1 perfil por empresa** (pode ser diferente em cada).

| Perfil | Pode Fazer |
|---|---|
| **Admin** | Tudo: cadastros, usuários, empresas, aprovar, pagar |
| **Operador** | Criar/editar contas, ver relatórios |
| **Aprovador** | Tudo do operador **+ aprovar contas** |
| **Pagador** | Tudo do operador **+ pagar contas** |
| **Visualizador** | Só leitura — vê relatórios e dashboards, não altera nada |

### 13.1 Quem Pode Fazer O Quê

| Ação | Admin | Operador | Aprovador | Pagador | Visualizador |
|---|:-:|:-:|:-:|:-:|:-:|
| Ver dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| Criar conta | ✅ | ✅ | ✅ | ✅ | ❌ |
| Editar conta | ✅ | ✅ | ✅ | ✅ | ❌ |
| Aprovar conta | ✅ | ❌ | ✅ | ❌ | ❌ |
| Pagar conta | ✅ | ❌ | ❌ | ✅ | ❌ |
| Cancelar conta | ✅ | ✅ | ✅ | ✅ | ❌ |
| Anexar PDF | ✅ | ✅ | ✅ | ✅ | ❌ |
| Ver relatórios | ✅ | ✅ | ✅ | ✅ | ✅ |
| Exportar CSV/PDF | ✅ | ✅ | ✅ | ✅ | ✅ |
| Cadastros (fornecedores/categorias) | ✅ | ❌ | ❌ | ❌ | ❌ |
| Gerenciar usuários | ✅ | ❌ | ❌ | ❌ | ❌ |
| Gerenciar empresas | ✅ | ❌ | ❌ | ❌ | ❌ |
| Gerar recorrências | ✅ | ✅ | ✅ | ✅ | ❌ |

### 13.2 Tentando Ação sem Permissão

Se você clicar numa ação que não tem permissão, recebe **HTTP 403** com a mensagem:

```
{"erro": "Sem permissão para esta ação"}
```

Apenas usuários com permissão veem os botões correspondentes.

---

## 14. FAQ — Dúvidas Frequentes

### 14.1 Como eu sei se uma conta já foi gerada pela recorrência?

Na tela de detalhe da conta, na seção **Observações** (se preenchida) ou veja o campo `recorrencia_id` (visível só pra admin via banco).

**Na prática:** se a descrição termina com `(jul/26)` ou similar, foi gerada por recorrência.

### 14.2 Posso editar uma conta PAGA?

❌ **Não.** Contas PAGA são imutáveis por design (rastreabilidade financeira).

Se errou o valor pago, faça:
1. Cancele a conta (se for PENDENTE/APROVADA)
2. Ou, se PAGA, crie uma nova conta com a diferença

### 14.3 Como funciona o parcelamento se eu cancelar uma parcela?

Só a parcela cancelada fica CANCELADA. As outras continuam normalmente.

### 14.4 Posso excluir uma recorrência com contas já geradas?

Sim, mas as contas já geradas **não são excluídas** (continuam na sua lista, independentes).

Se quiser parar de gerar novas: marque como **Inativa** (em vez de excluir).

### 14.5 Como exportar pra contabilidade?

Use o relatório **Por Período** → escolha o período (mês cheio) → **📥 Baixar CSV** → abra no Excel → importe no sistema contábil.

### 14.6 O que acontece se eu trocar de empresa no meio de uma operação?

Cada operação é vinculada à empresa ativa na hora. Se você criar uma conta na empresa A e trocar pra B, a conta fica salva em A.

### 14.7 Minha sessão expirou, perdi o que estava fazendo?

A sessão expira por inatividade. Dados já salvos no banco estão preservados. Você precisa logar de novo.

### 14.8 Posso editar o valor de uma conta parcelada?

❌ Não diretamente. Cada parcela é editada individualmente. O pai é imutável (já tem valor 0).

### 14.9 Como descobrir quem aprovou/pagou uma conta?

Na tela de detalhe, nas linhas **"Aprovada por"** e **"Pago por"** aparece o nome do usuário + data/hora.

### 14.10 O backup é automático?

✅ Sim. Backup diário às 03:30 + backup semanal (domingo). Verifique em `/home/sistema/backups/contas_pagar/`.

---

## 15. Suporte

### 15.1 Problemas Técnicos

| Sintoma | O que fazer |
|---|---|
| Não consigo logar | Confira email/senha. Se esqueceu, procure o admin |
| Página em branco | Limpe cookies do navegador ou tente outro browser |
| Erro "Sem permissão" | Você não tem perfil pra essa ação. Procure o admin |
| Botão de download não funciona | Verifique se o pop-up não está bloqueado |
| Upload de PDF falha | Confira se é PDF real (não `.exe` renomeado) e tem menos de 10MB |

### 15.2 Contato

- **Administrador do sistema:** [definir contato]
- **Desenvolvedor:** João Batista (joao@globalmt.com.br)
- **Issues no GitHub:** https://github.com/joaobatista-globalmt/contas-pagar/issues

### 15.3 Logs do Sistema (técnico)

- **Aplicação:** `/var/log/php8.2-fpm.log`
- **Erros PHP:** capturado em `bolinha-error.log`
- **Nginx:** `/var/log/nginx/error.log`
- **Banco:** `SELECT * FROM log_operacoes WHERE usuario_id = X`

---

## 📝 Versão do Manual

| Versão | Data | Mudanças |
|---|---|---|
| 1.0.0 | 26/06/2026 | Versão inicial |

---

**💡 Sugestões pra melhorar este manual?** Mande pro admin ou abra uma issue no GitHub.

*Manual gerado automaticamente pelo assistente em 26/06/2026.*
