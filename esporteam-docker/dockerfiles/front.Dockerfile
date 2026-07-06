# Imagem dev para esporteam-front (Vite + Vue)
FROM node:22-alpine

WORKDIR /app

EXPOSE 5173

CMD ["sh", "-c", "[ -x node_modules/.bin/vite ] || npm install --no-audit --no-fund; npm run dev -- --host 0.0.0.0"]
