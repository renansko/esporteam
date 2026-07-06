.PHONY: setup up down logs ps rebuild shell-auth shell-workspace shell-back keys clean pull db-refresh db-refresh-auth db-refresh-workspace db-refresh-back

setup:           ## Clona repos, gera chaves, prepara .env e sobe tudo
	./setup.sh

up:              ## Sobe os containers
	docker compose up -d

down:            ## Para os containers
	docker compose down

logs:            ## Tail dos logs
	docker compose logs -f

ps:              ## Status dos containers
	docker compose ps

rebuild:         ## Rebuild + up
	docker compose up -d --build

pull:            ## git pull --ff-only origin <main|branch padrão> em todos os repos irmãos
	@for d in esporteam-back esporteam-auth esporteam-workspace esporteam-front; do \
		echo "-> $$d"; \
		branch=$$(git -C ../$$d ls-remote --heads origin main | grep -q . && echo main || git -C ../$$d ls-remote --symref origin HEAD | awk '/^ref:/ {sub("refs/heads/","",$$2); print $$2; exit}'); \
		echo "  branch: $$branch"; \
		git -C ../$$d fetch origin $$branch && git -C ../$$d pull --ff-only origin $$branch || exit 1; \
	done

shell-auth:
	docker exec -it esporteam-auth sh

shell-workspace:
	docker exec -it esporteam-workspace sh

shell-back:
	docker exec -it esporteam-back sh

keys:            ## (Re)gera o keypair RS256 e distribui o public para os serviços
	mkdir -p ../esporteam-auth/keys ../esporteam-workspace/keys ../esporteam-back/keys
	openssl genpkey -algorithm RSA -out ../esporteam-auth/keys/private.pem -pkeyopt rsa_keygen_bits:2048
	openssl rsa -pubout -in ../esporteam-auth/keys/private.pem -out /tmp/esporteam-pub.pem
	cp /tmp/esporteam-pub.pem ../esporteam-auth/keys/public.pem
	cp /tmp/esporteam-pub.pem ../esporteam-workspace/keys/public.pem
	cp /tmp/esporteam-pub.pem ../esporteam-back/keys/public.pem
	rm /tmp/esporteam-pub.pem

clean:           ## Para containers e remove volumes (perde dados!)
	docker compose down -v

db-refresh-auth:        ## migrate:fresh no esporteam-auth (sem seeders)
	docker exec esporteam-auth php artisan migrate:fresh --force

db-refresh-workspace:   ## migrate:fresh no esporteam-workspace (sem seeders)
	docker exec esporteam-workspace php artisan migrate:fresh --force

db-refresh-back:     ## migrate:fresh no esporteam-back (sem seeders)
	docker exec esporteam-back php artisan migrate:fresh --force

db-refresh: db-refresh-auth db-refresh-workspace db-refresh-back   ## Refresh em todos os bancos (sem seeders)
