# API de Cotações Monetárias

## Visão Geral

Esta API permite o gerenciamento de cotações monetárias do dólar (USD) e do valor da tonelada do alumínio. O sistema inclui:

- CRUD completo para cotações
- Consulta de variações (diária, semanal, mensal)
- Busca automática de cotações via cronjob
- Filtros avançados para consultas

## Endpoints

### 1. Listar Cotações

```
GET /api/currency-rates
```

**Parâmetros de consulta:**
- `type` (opcional): Tipo da cotação (`USD` ou `ALUMINUM`)
- `date` (opcional): Data específica (formato: YYYY-MM-DD)
- `start_date` e `end_date` (opcional): Período específico
- `page` (opcional): Página para paginação

**Exemplo:**
```
GET /api/currency-rates?type=USD&start_date=2025-08-01&end_date=2025-08-14
```

### 2. Cotações Atuais

```
GET /api/currency-rates/current
```

Retorna as cotações mais recentes do USD e Alumínio.

**Resposta:**
```json
{
  "usd": {
    "id": 31,
    "currency_type": "USD",
    "rate": "5.2156",
    "rate_date": "2025-08-14"
  },
  "aluminum": {
    "id": 62,
    "currency_type": "ALUMINUM",
    "rate": "2089.45",
    "rate_date": "2025-08-14"
  }
}
```

### 3. Variações

```
GET /api/currency-rates/variations
```

**Parâmetros de consulta:**
- `type` (opcional): Tipo da cotação (`USD` ou `ALUMINUM`), padrão: `USD`
- `date` (opcional): Data de referência, padrão: hoje

**Resposta:**
```json
{
  "type": "USD",
  "date": "2025-08-14",
  "variations": {
    "daily": {
      "current": "5.2156",
      "previous": "5.1890",
      "variation": 0.51,
      "current_date": "2025-08-14",
      "previous_date": "2025-08-13"
    },
    "weekly": {
      "current": "5.2156",
      "previous": "5.0234",
      "variation": 3.83,
      "current_date": "2025-08-14",
      "previous_date": "2025-08-07"
    },
    "monthly": {
      "current": "5.2156",
      "previous": "5.3421",
      "variation": -2.37,
      "current_date": "2025-08-14",
      "previous_date": "2025-07-14"
    }
  }
}
```

### 4. Criar Cotação (Requer Autenticação)

```
POST /api/currency-rates
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "currency_type": "USD",
  "rate": 5.25,
  "rate_date": "2025-08-14"
}
```

### 5. Atualizar Cotação (Requer Autenticação)

```
PUT /api/currency-rates/{id}
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "rate": 5.30
}
```

### 6. Deletar Cotação (Requer Autenticação)

```
DELETE /api/currency-rates/{id}
```

**Headers:**
```
Authorization: Bearer {token}
```

### 7. Visualizar Cotação Específica

```
GET /api/currency-rates/{id}
```

## Comandos Artisan

### Buscar Cotações Manualmente

```bash
# Buscar todas as cotações
php artisan currency:fetch

# Buscar apenas USD
php artisan currency:fetch --type=usd

# Buscar apenas Alumínio
php artisan currency:fetch --type=aluminum
```

## Cronjob Automático

O sistema está configurado para buscar cotações automaticamente:

- **09:00 (UTC-3)**: Primeira tentativa de busca
- **15:00 (UTC-3)**: Segunda tentativa (backup)

### Configuração do Cron no Servidor

Para ativar o cronjob automático, adicione ao crontab do servidor:

```bash
# Editar crontab
crontab -e

# Adicionar linha (ajustar caminho conforme necessário)
* * * * * cd /caminho/para/o/projeto && php artisan schedule:run >> /dev/null 2>&1
```

## Estrutura de Dados

### Tabela: currency_rates

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | BIGINT | Chave primária |
| currency_type | VARCHAR | Tipo da cotação (`USD` ou `ALUMINUM`) |
| rate | DECIMAL(10,4) | Valor da cotação |
| rate_date | DATE | Data da cotação |
| created_at | TIMESTAMP | Data de criação |
| updated_at | TIMESTAMP | Data de atualização |

**Índices:**
- Índice único em `currency_type` + `rate_date` (evita duplicatas)

## Códigos de Status HTTP

- `200`: Sucesso
- `201`: Criado com sucesso
- `400`: Dados inválidos
- `401`: Não autorizado
- `404`: Não encontrado
- `422`: Erro de validação

## Logs

Os logs das operações de busca de cotações são salvos em:
- `storage/logs/currency-fetch.log`
- `storage/logs/laravel.log`

## Configuração de APIs Externas

Para usar APIs reais de cotações, configure no arquivo `.env`:

```env
# Para MetalsAPI (cotações de commodities)
METALS_API_KEY=sua_chave_aqui

# Para outras APIs, adicione conforme necessário
```

## Exemplo de Uso Completo

```javascript
// 1. Obter cotações atuais
const currentRates = await fetch('/api/currency-rates/current');
const rates = await currentRates.json();

// 2. Verificar variações do dólar
const variations = await fetch('/api/currency-rates/variations?type=USD');
const usdVariations = await variations.json();

// 3. Listar últimas 10 cotações do alumínio
const aluminumRates = await fetch('/api/currency-rates?type=ALUMINUM&per_page=10');
const aluminum = await aluminumRates.json();
```
