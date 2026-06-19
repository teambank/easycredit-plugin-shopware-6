#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT}"

SHOPWARE_66_VERSION="${SHOPWARE_66_VERSION:-6.6.10.6}"
SHOPWARE_67_VERSION="${SHOPWARE_67_VERSION:-6.7.8.0}"
CONFIG_FILE=".shopware-extension.yml"
CONFIG_BACKUP="$(mktemp)"
STOREFRONT_MAIN="${ROOT}/src/Resources/app/storefront/src/main.js"
STOREFRONT_MAIN_HIDDEN="${ROOT}/src/Resources/app/storefront/src/.main.js.build-hidden"

if [ -f "${CONFIG_FILE}" ]; then
  cp "${CONFIG_FILE}" "${CONFIG_BACKUP}"
fi

restore_config() {
  if [ -f "${CONFIG_BACKUP}" ]; then
    cp "${CONFIG_BACKUP}" "${CONFIG_FILE}"
  else
    rm -f "${CONFIG_FILE}"
  fi
}

hide_storefront_entrypoint() {
  if [ -f "${STOREFRONT_MAIN}" ]; then
    mv "${STOREFRONT_MAIN}" "${STOREFRONT_MAIN_HIDDEN}"
  fi
}

restore_storefront_entrypoint() {
  if [ -f "${STOREFRONT_MAIN_HIDDEN}" ]; then
    mv "${STOREFRONT_MAIN_HIDDEN}" "${STOREFRONT_MAIN}"
  fi
}

cleanup() {
  restore_storefront_entrypoint
  restore_config
}

trap cleanup EXIT

write_build_config() {
  local version="$1"

  cat > "${CONFIG_FILE}" <<EOF
build:
  shopwareVersionConstraint: '${version}'
EOF
}

run_build() {
  if command -v shopware-cli >/dev/null 2>&1; then
    shopware-cli extension build .
  else
    docker run --rm -v "$(pwd)":/ext shopware/shopware-cli extension build /ext
  fi
}

echo "Building administration (6.7) and storefront for Shopware ${SHOPWARE_67_VERSION}..."
write_build_config "${SHOPWARE_67_VERSION}"
run_build

echo "Building administration assets for Shopware ${SHOPWARE_66_VERSION} (storefront skipped)..."
hide_storefront_entrypoint
write_build_config "${SHOPWARE_66_VERSION}"
run_build

echo "Asset build completed (admin 6.7 + 6.6, storefront once)."
