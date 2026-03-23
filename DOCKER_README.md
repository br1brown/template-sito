# template-sito - Docker Setup

## Quick Start

```bash
docker compose up --build
```

Apri il browser su **http://localhost:8008**

## Comandi Utili

```bash
# Avvia in background
docker compose up --build -d

# Ferma
docker compose down

# Logs in tempo reale
docker compose logs -f

# Accedi al container
docker compose exec template-sito bash

# Ricostruisci dopo cambio Dockerfile
docker compose up --build
```

## Production

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

## Development vs Production

| | Dev (default) | Prod |
|---|---|---|
| Codice | Bind mount (modifiche live) | Baked nell'immagine |
| Restart | `unless-stopped` | `always` |
| Logging | Standard | json-file (max 10mb) |
