# SPEC.md - CarneShop (Sistema SaaS de Delivery de Carnes)

## 1. Visão Geral do Sistema

**Nome:** CarneShop
**Tipo:** E-commerce SaaS Multi-Tenant (múltiplas lojas)
**Resumo:** Plataforma para açougues e churrascarias venderem carnes online com entrega gerenciada pelo WhatsApp.
**Usuários:**
- **Super Admin** (dono da plataforma)
- **Dono de Loja** (cliente que cria sua própria loja)
- **Clientes** (compradores das lojas)

---

## 2. Módulo SaaS (Multi-Tenant)

### 2.1 Gestão de Lojas (Super Admin)

| RN # | Regra | Prioridade |
|-------|-------|------------|
| SAAS-001 | O Super Admin pode criar novas lojas (tenants) | Obrigatória |
| SAAS-002 | Cada loja tem: subdomínio único (ex: minha-loja.carneshop.com.br), nome, WhatsApp, configurações | Obrigatória |
| SAAS-003 | O Super Admin pode pausar/suspender uma loja | Obrigatória |
| SAAS-004 | O Super Admin pode excluir uma loja | Obrigatória |
| SAAS-005 | O Super Admin define planos de assinatura (Free, Basic, Premium) | Obrigatória |
| SAAS-006 | Cada plano tem limites: número de produtos, categorias, funcionalidades | Obrigatória |
| SAAS-007 | O sistema controla uso de recursos por loja | Obrigatória |
| SAAS-008 | Super Admin tem acesso a todas as lojas | Obrigatória |

### 2.2 Planos e Assinatura

| RN # | Regra | Prioridade |
|-------|-------|------------|
| SAAS-009 | Free: até 50 produtos, 3 categorias, sem domínio próprio | - |
| SAAS-010 | Basic: até 200 produtos, categorias ilimitadas, domínio próprio | - |
| SAAS-011 | Premium: produtos ilimitados, todas funcionalidades, API acesso | - |
| SAAS-012 | O sistema avisa quando a loja atinge limite do plano | Obrigatória |
| SAAS-013 | O dono da loja pode fazer upgrade/downgrade de plano | Obrigatória |

### 2.3 Cobrança

| RN # | Regra | Prioridade |
|-------|-------|------------|
| SAAS-014 | O sistema registra data de início e próximo vencimento | Obrigatória |
| SAAS-015 | O sistema envia lembretes de pagamento (email/WhatsApp) | Opcional |
| SAAS-016 | O sistema bloqueia loja inadimplente após X dias | Obrigatória |
| SAAS-017 | Suporte a pagamento via PIX, cartão (Mercado Pago, Stripe) | Opcional |

### 2.4 Onboarding de Lojas

| RN # | Regra | Prioridade |
|-------|-------|------------|
| SAAS-018 | O cliente pode se cadastrar e criar uma loja automaticamente | Obrigatória |
| SAAS-019 | O sistema verifica disponibilidade do subdomínio | Obrigatória |
| SAAS-020 | O sistema pode verificar email do dono da loja | Obrigatória |
| SAAS-021 | Nova loja começa com template/demo de produtos | Opcional |

---

## 3. Módulo Loja (Dono do Estabelecimento)

### 3.1 Gestão de Produtos

| RN # | Regra | Prioridade |
|-------|-------|------------|
| RN-001 | O administrador pode cadastrar produtos com: nome, descrição, preço/kg, imagem, categoria | Obrigatória |
| RN-002 | O produto pode ter variações de corte (bife, moído, cubos, inteiro, fatiado) | Obrigatória |
| RN-003 | O preço é calculado por kg, mas o cliente escolhe a gramatura (ex: 500g, 1kg, 1.5kg) | Obrigatória |
| RN-004 | O peso pode variar na pesagem real - o sistema deve permitir ajuste posterior | Obrigatória |
| RN-005 | O produto pode ter observação opcional (ex: "mais gordo", "mais fino", "separar em pacotes de 300g") | Obrigatória |
| RN-006 | O produto pode ter desconto percentual aplicado | Opcional |
| RN-007 | O produto pode ter preço promocional (de/por) | Opcional |
| RN-008 | O produto pode ter estoque limitado ou quantidade disponível | Opcional |
| RN-009 | O administrador pode ativar/desativar produtos sem excluir | Obrigatória |
| RN-009B | O sistema limita número de produtos conforme plano | Obrigatória |

### 3.2 Gestão de Categorias

| RN # | Regra | Prioridade |
|-------|-------|------------|
| RN-010 | O administrador pode criar categorias (ex: Bovino, Suíno, Frango, Kits) | Obrigatória |
| RN-011 | Categorias podem ter ordem de exibição | Obrigatória |
| RN-012 | Categorias podem ser ativadas/desativadas | Obrigatória |
| RN-013 | Cada produto pertence a uma categoria | Obrigatória |

### 3.3 Gestão de Kits e Combos

| RN # | Regra | Prioridade |
|-------|-------|------------|
| RN-014 | O admin pode criar kits com múltiplos produtos | Obrigatória |
| RN-015 | Kits podem ter preço por pessoa (ex: R$ 25/pessoa para 4 pessoas = R$ 100) | Obrigatória |
| RN-016 | Kits podem ter peso total estimado | Opcional |
| RN-017 | Kits podem ter descrição com lista de itens inclusos | Obrigatória |

### 3.4 Personalização da Loja

| RN # | Regra | Prioridade |
|-------|-------|------------|
| LOJA-001 | O dono pode alterar: nome da loja, descrição | Obrigatória |
| LOJA-002 | O dono pode fazer upload do logo | Obrigatória |
| LOJA-003 | O dono pode escolher cores principais do site | Obrigatória |
| LOJA-004 | O dono pode alterar WhatsApp de atendimento | Obrigatória |
| LOJA-005 | O dono pode configurar horário de funcionamento | Opcional |
| LOJA-006 | O dono pode configurar mensagem de boas-vindas | Opcional |
| LOJA-007 | O dono pode adicionar links para redes sociais | Opcional |
| LOJA-008 | O dono pode configurar banner carousel na home | Opcional |
| LOJA-009 | Domínio próprio (Premium): o dono pode usar seu próprio domínio | Obrigatória |

### 3.5 Carrinho de Compras

| RN # | Regra | Prioridade |
|-------|-------|------------|
| RN-018 | O cliente pode adicionar produtos ao carrinho | Obrigatória |
| RN-019 | Cada item no carrinho deve ter: produto, variação, gramatura, observações | Obrigatória |
| RN-020 | O cliente pode alterar quantidade de cada item | Obrigatória |
| RN-021 | O cliente pode remover itens do carrinho | Obrigatória |
| RN-022 | O carrinho deve mostrar subtotal por item e total geral | Obrigatória |
| RN-023 | O carrinho deve persistir durante a sessão | Obrigatória |

### 3.6 Checkout e Pedidos

| RN # | Regra | Prioridade |
|-------|-------|------------|
| RN-024 | O cliente informa: nome, telefone, endereço de entrega | Obrigatória |
| RN-025 | O cliente pode escolher forma de pagamento (dinheiro, pix, cartão) | Obrigatória |
| RN-026 | O cliente pode informar troco se pagar em dinheiro | Obrigatória |
| RN-027 | O sistema calcula taxa de entrega baseada no bairro/distância | Obrigatória |
| RN-028 | O sistema define pedido mínimo para entrega (ex: R$ 50) | Obrigatória |
| RN-029 | O pedido é enviado para o WhatsApp do estabelecimento | Obrigatória |
| RN-030 | O pedido deve ter formato legível para o atendente | Obrigatória |
| RN-031 | O admin pode ajustar peso e valor final do pedido antes de confirmar | Obrigatória |

### 3.7 Fluxo do Cliente

```
1. Cliente acessa a loja (site/app)
2. Navega pelas categorias ou usa busca
3. Visualiza produtos com preços e imagens
4. Clica em "Adicionar" → seleciona gramatura e variação
5. Continua comprando ou vai para o carrinho
6. Revisa itens e vai para checkout
7. Preenche dados de entrega
8. Escolhe forma de pagamento
9. Confirma o pedido
10. Recebe confirmação (opcional via WhatsApp)
11. Estabelecimento recebe pedido no WhatsApp
```

### 3.8 Administração

| RN # | Regra | Prioridade |
|-------|-------|------------|
| RN-032 | Painel admin para gerenciar produtos, categorias, pedidos | Obrigatória |
| RN-033 | Admin pode customizar: logo, cores, nome da loja | Obrigatória |
| RN-034 | Admin pode configurar: pedido mínimo, taxa de entrega por bairro | Obrigatória |
| RN-035 | Admin pode ver histórico de pedidos | Opcional |
| RN-036 | Admin pode alterar status do pedido (recebido, em preparo, enviado) | Opcional |

### 3.9 Integração WhatsApp

| RN # | Regra | Prioridade |
|-------|-------|------------|
| RN-037 | Pedidos são enviados automaticamente para o WhatsApp do estabelecimento | Obrigatória |
| RN-038 | Formato do mensagem deve incluir: itens, total, dados do cliente, endereço | Obrigatória |
| RN-039 | O cliente pode iniciar conversa via WhatsApp direto do site | Obrigatória |

### 3.10 Busca e Filtros

| RN # | Regra | Prioridade |
|-------|-------|------------|
| RN-040 | O cliente pode buscar produtos por nome | Obrigatória |
| RN-041 | O cliente pode filtrar por categoria | Obrigatória |

---

## 4. Arquitetura Técnica

### 4.1 Stack Tecnológico

| Camada | Tecnologia |
|--------|------------|
| **Backend API** | Laravel 11 (PHP 8.3+) |
| **Frontend Web** | React Native Web (Expo Router) |
| **Frontend Mobile** | React Native (Expo) - Same codebase |
| **Banco de Dados** | PostgreSQL 15+ |
| **Cache/Queue** | Redis |
| **Autenticação** | Laravel Sanctum (JWT) |
| **Storage** | AWS S3 ou Cloudflare R2 |
| **Hospedagem API** | Railway / AWS ECS / DigitalOcean |
| **Hospedagem Frontend** | Vercel / Netlify |
| **CDN** | Cloudflare |

### 4.2 Arquitetura Multi-Tenant

```
┌─────────────────────────────────────────────────────────────┐
│                        FRONTEND                              │
│         (React Native - Web + iOS + Android)                │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                     LARAVEL API                              │
│              (Authentication, Business Logic)               │
│  ┌─────────────┬─────────────┬─────────────┐              │
│  │   Store 1   │   Store 2   │   Store N   │  ← Tenants   │
│  │  (schema)   │  (schema)   │  (schema)   │              │
│  └─────────────┴─────────────┴─────────────┘              │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│      PostgreSQL         │     │         Redis           │
│  (Dados + Schemas)      │     │   (Cache + Queue)       │
└─────────────────────────┘     └─────────────────────────┘
              │
              ▼
┌─────────────────────────┐
│        AWS S3           │
│    (Imagens/Arquivos)  │
└─────────────────────────┘
```

### 4.3 Estratégia Multi-Tenant

| Abordagem | Descrição |
|-----------|-----------|
| **Database por Tenant** | Cada loja tem seu próprio schema no PostgreSQL |
| **Tenant ID em todas tabelas** | UUID da loja em cada registro |
| **Middleware de Tenant** | Identifica loja pela URL (subdomínio) |
| **Isolamento total** | Dados de uma loja não vazam para outra |

### 4.4 API Design (REST)

**Base URL:** `https://api.carneshop.com.br/v1`

#### Endpoints de Autenticação

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/auth/register` | Cadastrar novo usuário |
| POST | `/auth/login` | Login (retorna token) |
| POST | `/auth/logout` | Logout |
| POST | `/auth/forgot-password` | Esqueci senha |
| POST | `/auth/reset-password` | Resetar senha |
| GET | `/auth/me` | Dados do usuário logado |

#### Endpoints de Lojas (Super Admin)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/admin/stores` | Listar todas lojas |
| POST | `/admin/stores` | Criar loja |
| GET | `/admin/stores/{id}` | Ver loja |
| PUT | `/admin/stores/{id}` | Atualizar loja |
| DELETE | `/admin/stores/{id}` | Deletar loja |
| POST | `/admin/stores/{id}/pause` | Pausar loja |
| POST | `/admin/stores/{id}/resume` | Ativar loja |

#### Endpoints de Lojas (Dono)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/stores/me` | Minha loja |
| PUT | `/stores/me` | Atualizar configurações |
| PUT | `/stores/me/theme` | Personalizar tema |
| PUT | `/stores/me/config` | Configurações (whatsapp, entrega) |

#### Endpoints de Categorias

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/stores/{id}/categories` | Listar categorias |
| POST | `/stores/{id}/categories` | Criar categoria |
| GET | `/stores/{id}/categories/{cat_id}` | Ver categoria |
| PUT | `/stores/{id}/categories/{cat_id}` | Atualizar categoria |
| DELETE | `/stores/{id}/categories/{cat_id}` | Deletar categoria |

#### Endpoints de Produtos

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/stores/{id}/products` | Listar produtos |
| POST | `/stores/{id}/products` | Criar produto |
| GET | `/stores/{id}/products/{prod_id}` | Ver produto |
| PUT | `/stores/{id}/products/{prod_id}` | Atualizar produto |
| DELETE | `/stores/{id}/products/{prod_id}` | Deletar produto |
| PUT | `/stores/{id}/products/{prod_id}/toggle` | Ativar/desativar |

#### Endpoints de Variações

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/stores/{id}/products/{prod_id}/variations` | Listar variações |
| POST | `/stores/{id}/products/{prod_id}/variations` | Criar variação |
| PUT | `/stores/{id}/variations/{var_id}` | Atualizar variação |
| DELETE | `/stores/{id}/variations/{var_id}` | Deletar variação |

#### Endpoints de Kits

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/stores/{id}/kits` | Listar kits |
| POST | `/stores/{id}/kits` | Criar kit |
| GET | `/stores/{id}/kits/{kit_id}` | Ver kit |
| PUT | `/stores/{id}/kits/{kit_id}` | Atualizar kit |
| DELETE | `/stores/{id}/kits/{kit_id}` | Deletar kit |

#### Endpoints de Pedidos

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/stores/{id}/orders` | Listar pedidos |
| GET | `/stores/{id}/orders/{order_id}` | Ver pedido |
| PUT | `/stores/{id}/orders/{order_id}/status` | Atualizar status |

#### Endpoints Públicos (Cliente)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/public/stores/{subdomain}` | Dados da loja |
| GET | `/public/stores/{subdomain}/categories` | Categorias |
| GET | `/public/stores/{subdomain}/products` | Produtos |
| GET | `/public/stores/{subdomain}/products/{slug}` | Produto detalhe |
| POST | `/public/stores/{subdomain}/checkout` | Enviar pedido |

#### Endpoints de Planos (Super Admin)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/admin/plans` | Listar planos |
| POST | `/admin/plans` | Criar plano |
| PUT | `/admin/plans/{id}` | Atualizar plano |
| DELETE | `/admin/plans/{id}` | Deletar plano |

### 4.5 Padrões de API

**Response Success:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operação realizada com sucesso"
}
```

**Response Error:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Erro de validação",
    "details": [
      { "field": "email", "message": "Email inválido" }
    ]
  }
}
```

**Paginação:**
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

### 4.6 Banco de Dados - Esquema Principal

```
┌─────────────────────────────────────────────────────────────┐
│                    SUPER ADMIN (Sistema)                    │
├─────────────────────────────────────────────────────────────┤
│ users (id, name, email, password, role, created_at...)    │
│ plans (id, name, price, limits_products, limits_categories│
│        is_active, created_at...)                          │
│ subscriptions (id, store_id, plan_id, starts_at, expires_ │
│               status, payment_method...)                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      TENANT (Cada Loja)                     │
├─────────────────────────────────────────────────────────────┤
│ store_settings (id, store_id, name, description, logo,    │
│                 primary_color, whatsapp, address,          │
│                 delivery_config...)                        │
│ categories (id, store_id, name, slug, order, is_active...)│
│ products (id, store_id, category_id, name, slug, desc,    │
│           price, discount_price, image, is_active...)    │
│ product_variations (id, product_id, name, price_adjust)   │
│ observations (id, store_id, name, is_active)              │
│ kits (id, store_id, name, description, price_per_person, │
│       items_count, is_active...)                          │
│ kit_items (id, kit_id, product_id, quantity)             │
│ orders (id, store_id, customer_name, customer_phone,      │
│         customer_address, total, status, payment_method,  │
│         change_for, delivery_fee, notes...)               │
│ order_items (id, order_id, product_id, variation_id,      │
│              quantity, gramage, unit_price, subtotal,     │
│              observations...)                              │
│ banners (id, store_id, image, link, order, is_active)     │
│ neighborhoods (id, store_id, name, delivery_fee,          │
│                minimum_order, is_active)                  │
└─────────────────────────────────────────────────────────────┘
```

### 4.7 Estrutura de Pastas

#### Backend (Laravel)
```
/backend
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── Admin/
│   │   │   │   ├── Store/
│   │   │   │   └── Public/
│   │   │   └── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Services/
│   ├── Repositories/
│   └── Traits/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php
│   └── console.php
├── config/
├── tests/
└── .env.example
```

#### Frontend (React Native)
```
/frontend
├── src/
│   ├── app/                    # Expo Router (file-based routing)
│   │   ├── (auth)/            # Login, Register, Forgot Password
│   │   ├── (store)/          # Lojas públicas
│   │   ├── (admin)/          # Painel admin
│   │   └── _layout.tsx
│   ├── components/
│   │   ├── ui/               # Componentes base
│   │   ├── product/
│   │   ├── cart/
│   │   └── checkout/
│   ├── services/              # API calls
│   ├── stores/                # Zustand stores
│   ├── hooks/
│   ├── constants/
│   ├── types/                 # TypeScript types
│   └── utils/
├── app.json
├── babel.config.js
├── metro.config.js
└── package.json
```

### 4.8 Autenticação e Autorização

| Camada | Método |
|--------|--------|
| API | Laravel Sanctum (Token JWT) |
| Frontend | AsyncStorage + Context |
| Expiração | 7 dias (refresh token) |
| Permissões | Roles: super_admin, store_owner, customer |

### 4.9 Upload de Imagens

| Item | Storage |
|------|---------|
| Logo da loja | AWS S3 / R2 |
| Imagens produtos | AWS S3 / R2 |
| Banners | AWS S3 / R2 |
| Otimização | Cloudflare Images ou similar |

### 4.10 Build e Deploy

| Plataforma | Comando | Output |
|------------|---------|--------|
| Web | `expo export --platform web` | `dist/` |
| Android | `expo run:android` | `.apk` / `.aab` |
| iOS | `expo run:ios` | `.ipa` (requer Mac) |

---

## 5. Glossário

| Termo | Definição |
|-------|-----------|
| **Tenant/Loja** | Cada estabelecimento que usa a plataforma |
| **Super Admin** | Administrador da plataforma SaaS |
| **Variação** | Forma de preparo do corte (bife, moído, cubos, etc) |
| **Gramatura** | Peso escolhido pelo cliente (ex: 500g, 1kg) |
| **Kit/Combo** | Pacote com múltiplos produtos (ex: Kit Churrasco) |
| **Taxa de Entrega** | Valor adicional baseado na distância/bairro |
| **Pedido Mínimo** | Valor mínimo para realizar entrega |
| **Multi-tenant** | Arquitetura que permite múltiplos clientes na mesma instância |

---

_Documento gerado em: 2026-03-15_
