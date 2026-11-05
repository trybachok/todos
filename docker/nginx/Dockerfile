FROM node:24-alpine3.23 AS spa
WORKDIR /app/frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --ignore-scripts --no-audit --no-fund
COPY docs/openapi.yaml /app/docs/openapi.yaml
COPY frontend ./
RUN npm run build

FROM nginxinc/nginx-unprivileged:1.28-alpine3.23
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/security-headers.conf /etc/nginx/snippets/security-headers.conf
COPY --from=spa /app/frontend/dist /srv/notes
COPY --from=spa /app/frontend/node_modules/swagger-ui-dist/swagger-ui.css /app/frontend/node_modules/swagger-ui-dist/swagger-ui-bundle.js /srv/notes/docs/
COPY docker/nginx/docs /srv/notes/docs
COPY docs/openapi.yaml /srv/notes/docs/openapi.yaml
