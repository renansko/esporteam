#!/usr/bin/env bash
# Bootstrap completo do ambiente Esporteam.
# Clona repositórios irmãos, gera chaves JWT, prepara .env e sobe os containers.
set -euo pipefail

ORG="Esporteam-Tech"

# repo:diretório
REPOS=(
    "esporteam-auth:esporteam-auth"
    "esporteam-workspace:esporteam-workspace"
    "esporteam-back:esporteam-back"
    "esporteam-front:esporteam-front"
)

bold() { printf '\033[1m%s\033[0m\n' "$*"; }
info() { printf '  → %s\n' "$*"; }

bold "1) Clonando repositórios irmãos"
for entry in "${REPOS[@]}"; do
    repo="${entry%%:*}"
    dir="${entry##*:}"
    if [ -d "../$dir/.git" ] || [ -d "../$dir" ]; then
        info "$dir já existe — pulando"
    else
        info "clonando $ORG/$repo → $dir"
        gh repo clone "$ORG/$repo" "../$dir" -- --quiet
    fi
done

bold "2) Chaves JWT (RS256)"
mkdir -p ../esporteam-auth/keys ../esporteam-workspace/keys ../esporteam-back/keys
if [ ! -s ../esporteam-auth/keys/private.pem ]; then
    info "gerando keypair"
    openssl genpkey -algorithm RSA -out ../esporteam-auth/keys/private.pem -pkeyopt rsa_keygen_bits:2048 2>/dev/null
    openssl rsa -pubout -in ../esporteam-auth/keys/private.pem -out /tmp/esporteam-pub.pem 2>/dev/null
    cp /tmp/esporteam-pub.pem ../esporteam-auth/keys/public.pem
    cp /tmp/esporteam-pub.pem ../esporteam-workspace/keys/public.pem
    cp /tmp/esporteam-pub.pem ../esporteam-back/keys/public.pem
    rm /tmp/esporteam-pub.pem
else
    info "keypair já existe — pulando"
fi

bold "3) Arquivos .env"
prepare_env() {
    local dir="$1" db_name="$2"
    if [ ! -f "$dir/.env" ] && [ -f "$dir/.env.example" ]; then
        cp "$dir/.env.example" "$dir/.env"
        info "$dir/.env criado a partir do .env.example"
    fi
    # Garante config compatível com docker-compose (host esporteam-postgres + DBs corretos)
    if [ -f "$dir/.env" ]; then
        sed -i.bak \
            -e "s|^DB_CONNECTION=.*|DB_CONNECTION=pgsql|" \
            -e "s|^# DB_HOST=.*|DB_HOST=esporteam-postgres|; s|^DB_HOST=.*|DB_HOST=esporteam-postgres|" \
            -e "s|^# DB_PORT=.*|DB_PORT=5432|;       s|^DB_PORT=.*|DB_PORT=5432|" \
            -e "s|^# DB_DATABASE=.*|DB_DATABASE=$db_name|; s|^DB_DATABASE=.*|DB_DATABASE=$db_name|" \
            -e "s|^# DB_USERNAME=.*|DB_USERNAME=esporteam|; s|^DB_USERNAME=.*|DB_USERNAME=esporteam|" \
            -e "s|^# DB_PASSWORD=.*|DB_PASSWORD=secret|; s|^DB_PASSWORD=.*|DB_PASSWORD=secret|" \
            -e "s|^CACHE_STORE=.*|CACHE_STORE=file|" \
            -e "s|^SESSION_DRIVER=.*|SESSION_DRIVER=file|" \
            -e "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=sync|" \
            "$dir/.env"
        rm -f "$dir/.env.bak"
    fi
}

prepare_env "../esporteam-auth" "esporteam_auth"
prepare_env "../esporteam-workspace" "esporteam_workspace"
prepare_env "../esporteam-back" "esporteam_back"

# esporteam-auth precisa do workspace pra validar membership em POST /api/workspace/select
if [ -f ../esporteam-auth/.env ] && ! grep -q '^WORKSPACE_SERVICE_URL=' ../esporteam-auth/.env; then
    echo "WORKSPACE_SERVICE_URL=http://esporteam-workspace:8000" >> ../esporteam-auth/.env
fi

# esporteam-workspace precisa apontar para o auth
if [ -f ../esporteam-workspace/.env ] && ! grep -q '^AUTH_SERVICE_URL=' ../esporteam-workspace/.env; then
    {
        echo "AUTH_SERVICE_URL=http://esporteam-auth:8000"
        echo "AUTH_SERVICE_TOKEN=dev-service-token"
    } >> ../esporteam-workspace/.env
fi

# esporteam-back idem
if [ -f ../esporteam-back/.env ] && ! grep -q '^AUTH_SERVICE_URL=' ../esporteam-back/.env; then
    {
        echo "AUTH_SERVICE_URL=http://esporteam-auth:8000"
        echo "WORKSPACE_SERVICE_URL=http://esporteam-workspace:8000"
    } >> ../esporteam-back/.env
fi

bold "4) Subindo containers (docker compose up -d --build)"
docker compose up -d --build

bold "Pronto."
echo ""
echo "Serviços:"
echo "  esporteam-postgres            → localhost:5433"
echo "  esporteam-auth               → http://127.0.0.1:8001"
echo "  esporteam-workspace          → http://127.0.0.1:8002"
echo "  esporteam-back (backend)  → http://127.0.0.1:8000"
echo "  esporteam-front      → http://127.0.0.1:5173"
echo ""
echo "Logs:  docker compose logs -f"
echo "Para:  docker compose down"
