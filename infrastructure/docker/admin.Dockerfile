# The operator panel.
#
# A build stage that produces files, and a serving stage that is a web server
# with no application runtime in it. There is deliberately no Node in the final
# image: the panel is static (ADR-0013), so shipping a JavaScript runtime would
# add an attack surface that serves nothing.

# --- build -------------------------------------------------------------------
FROM node:22-alpine AS build

# Pinned, because "whatever the registry has today" is not a reproducible build.
RUN corepack enable && corepack prepare pnpm@10.33.0 --activate

WORKDIR /build

# Manifests first, so a dependency install is cached until a manifest changes
# rather than on every source edit.
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./
COPY apps/admin/package.json apps/admin/
COPY packages/ts-api-client/package.json packages/ts-api-client/
RUN pnpm install --frozen-lockfile

COPY packages/ ./packages/
COPY apps/admin/ ./apps/admin/

# The contracts are the source of truth, so the image is built from what they
# currently say. A drift between the committed client and the specs fails here
# as well as in CI.
COPY packages/api-contracts/ ./packages/api-contracts/
RUN pnpm contracts:verify

# `pnpm build` type-checks before it bundles. A panel that compiles but does
# not type-check is a panel whose next change breaks silently.
RUN pnpm --filter @kms/admin build

# --- serve -------------------------------------------------------------------
FROM nginx:1-alpine AS production

COPY --from=build /build/apps/admin/dist /usr/share/nginx/html
COPY infrastructure/nginx/admin.conf /etc/nginx/conf.d/default.conf

# Nothing here needs to write anywhere except nginx's own cache and pid.
USER nginx

EXPOSE 8080
