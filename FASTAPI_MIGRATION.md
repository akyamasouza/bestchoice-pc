# Migracao Para FastAPI

## Objetivo

Migrar este projeto para um backend em FastAPI com o menor esforco possivel, preservando o que ja provou valor:

- catalogo de CPUs
- ofertas por loja
- scrapers
- busca futura no Telegram
- MongoDB como banco principal

Este documento segue a ideia 80/20:

- manter so o que e essencial para o produto funcionar
- evitar recriar toda a estrutura do Laravel de uma vez
- priorizar API, coleta de dados e jobs

## O Que Ja Existe Hoje

O projeto atual ja tem estes blocos uteis:

- `Cpu` como catalogo tecnico
- `CpuOffer` como estado atual das ofertas por loja
- scrapers para `amazon`, `kabum`, `pichau` e `terabyteshop`
- seeders com CPUs e URLs de lojas
- tentativa inicial de integracao com Telegram
- MongoDB como banco principal

## Recomendacao De Estrategia

Nao migrar tudo de uma vez.

Fazer em 3 etapas:

1. Subir uma API FastAPI lendo o mesmo MongoDB
2. Migrar scrapers e comandos de sincronizacao
3. Desligar o backend Laravel quando a API nova cobrir o fluxo principal

## Escopo Minimo Da Migracao

Levar primeiro apenas isto:

- API para listar CPUs
- API para detalhar uma CPU
- API para listar ofertas de uma CPU
- comando/job para sincronizar ofertas
- base de scrapers funcionando

Nao migrar no inicio:

- auth
- painel administrativo
- frontend server-side
- integracao Telegram completa
- qualquer recurso cosmetico

## Stack Recomendada

- FastAPI
- Uvicorn
- Pydantic
- PyMongo ou Motor
- HTTPX
- BeautifulSoup ou selectolax
- APScheduler para jobs simples

Se quiser manter tudo simples no inicio:

- `pymongo` em vez de `motor`
- jobs via comando CLI + agendador do sistema

## Estrutura De Pastas Recomendada

```text
backend/
  app/
    main.py
    core/
      config.py
      database.py
    models/
      cpu.py
      cpu_offer.py
    schemas/
      cpu.py
      cpu_offer.py
    routes/
      cpus.py
      offers.py
      sync.py
    services/
      scrapers/
        base.py
        amazon.py
        kabum.py
        pichau.py
        terabyteshop.py
      offer_sync.py
      telegram_search.py
    repositories/
      cpu_repository.py
      cpu_offer_repository.py
    scripts/
      seed_cpus.py
      sync_offers.py
  tests/
  requirements.txt
```

## Mapeamento Laravel Para FastAPI

### Models

Hoje:

- `app/Models/Cpu.php`
- `app/Models/CpuOffer.php`

Em FastAPI:

- models simples para acesso ao Mongo
- schemas Pydantic para request/response

Exemplo de divisao:

- `models/` para estruturas internas
- `schemas/` para serializacao da API

### Routes E Controllers

Hoje:

- `routes/console.php`
- comandos Artisan

Em FastAPI:

- `routes/cpus.py`
- `routes/offers.py`
- `routes/sync.py`

Os "controllers" podem ser funcoes das rotas ou services chamados por elas.

### Services

Hoje:

- `app/Services/CpuOffers/*`
- `app/Services/Telegram/*`

Em FastAPI:

- manter a mesma ideia
- mover para `services/scrapers/` e `services/offer_sync.py`

Aqui esta a maior parte do valor do projeto.

## Banco De Dados

Continuar com MongoDB.

Colecoes principais:

- `cpus`
- `cpu_offers`

### Documento `cpus`

Campos minimos:

- `name`
- `sku`
- `other_names`
- `description`
- `class`
- `socket`
- `clockspeed_ghz`
- `turbo_speed_ghz`
- `cores`
- `threads`
- `typical_tdp_w`
- `cache`
- `benchmark`
- `first_seen`
- `store_urls`

### Documento `cpu_offers`

Campos minimos:

- `cpu_id`
- `cpu_name`
- `cpu_sku`
- `store`
- `url`
- `product_name`
- `seller`
- `status`
- `is_available`
- `currency`
- `price_pix`
- `price_card`
- `checked_at`
- `created_at`
- `updated_at`

## Endpoints Minimos

### 1. Listar CPUs

`GET /cpus`

Retorna lista simples:

- `id`
- `name`
- `sku`
- `socket`
- `cores`
- `threads`

### 2. Detalhar CPU

`GET /cpus/{sku}`

Retorna:

- dados tecnicos da CPU
- `store_urls`

### 3. Listar ofertas de uma CPU

`GET /cpus/{sku}/offers`

Retorna:

- loja
- preco
- status
- seller
- `checked_at`

### 4. Sincronizar ofertas

`POST /cpus/{sku}/sync-offers`

Uso inicial:

- endpoint interno
- ou CLI apenas

## Comandos Minimos

Mesmo usando FastAPI, vale manter CLI para operacao:

```bash
python -m app.scripts.seed_cpus
python -m app.scripts.sync_offers --sku 100-100000719WOF
```

No inicio, isso e suficiente.

Nao precisa criar fila ainda.

## Ordem Recomendada De Migracao

### Etapa 1. API Basica

Criar:

- conexao com Mongo
- rota `GET /cpus`
- rota `GET /cpus/{sku}`
- rota `GET /cpus/{sku}/offers`

Objetivo:

- provar que o FastAPI le o banco corretamente

### Etapa 2. Seeder Em Python

Mover o conteudo de `database/seeders/CpuSeeder.php` para:

- `scripts/seed_cpus.py`
- ou `data/cpus.py`

Recomendacao:

- usar `sku` como chave de upsert

### Etapa 3. Scrapers

Migrar estes arquivos primeiro:

- scraper base
- `AmazonScraper`
- `KabumScraper`
- `PichauScraper`
- `TerabyteShopScraper`

Objetivo:

- manter exatamente o mesmo comportamento
- nao reinventar regra de parsing na primeira passada

### Etapa 4. Sync De Ofertas

Criar um service equivalente ao `CpuOfferSyncService`.

Responsabilidades:

- carregar CPU
- percorrer `store_urls`
- chamar scraper por loja
- salvar em `cpu_offers`
- atualizar `checked_at`, `created_at`, `updated_at`

### Etapa 5. Telegram

So depois da API e scrapers estarem estaveis.

Aqui Python passa a ter vantagem forte:

- `Telethon`
- busca por mensagem em canal
- extracao de sinais de oferta

## O Que Nao Vale Migrar 1:1

Nao vale copiar a estrutura Laravel de forma literal.

Evite recriar:

- providers
- facades
- seeders no mesmo estilo do Artisan
- toda a separacao de framework se ela nao gerar valor

Em Python, prefira:

- rotas pequenas
- services claros
- repositories simples
- scripts diretos para operacao

## Exemplo De Roadmap Curto

Semana 1:

- FastAPI rodando
- conexao Mongo
- endpoints `GET /cpus` e `GET /cpus/{sku}`

Semana 2:

- endpoint `GET /cpus/{sku}/offers`
- seeder em Python
- primeiros testes

Semana 3:

- scrapers migrados
- comando de sync funcionando

Semana 4:

- Telegram search com `Telethon`
- enriquecimento de ofertas

## Critero De Sucesso

A migracao minima esta boa quando:

- o FastAPI lista CPUs do Mongo
- o FastAPI retorna ofertas de uma CPU
- um comando Python sincroniza precos das lojas
- o Laravel deixa de ser necessario para o fluxo principal

## Decisao Final Recomendada

Se a migracao for acontecer, comece por:

1. API de leitura
2. Seeder Python
3. Sync de ofertas
4. Telegram

Essa ordem reduz risco e entrega valor cedo.

O principal aqui nao e "migrar framework".

E migrar o nucleo do produto:

- catalogo
- ofertas
- coleta
- integracoes
