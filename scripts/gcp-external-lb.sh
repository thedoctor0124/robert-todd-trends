#!/usr/bin/env bash
# Global external Application Load Balancer → Cloud Run (serverless NEG).
# Docs: https://cloud.google.com/load-balancing/docs/https/setup-global-ext-https-serverless
#
# Usage:
#   export GCP_PROJECT=looptrends
#   export GCP_REGION=europe-west2
#   export CLOUD_RUN_SERVICE=looptrends
#
# 1) Core resources (idempotent):
#    ./scripts/gcp-external-lb.sh core
#
# 2) After core exists, add HTTPS (requires real DNS names you control):
#    LB_DOMAINS='www.example.com,example.com' ./scripts/gcp-external-lb.sh https
#
# 3) Point DNS at the reserved global IP (see `core` output), wait for the
#    managed cert to become ACTIVE, then test https://your-domain/
#
# 4) Lock Cloud Run so only the load balancer can reach it (recommended):
#    ./scripts/gcp-external-lb.sh lock-run-ingress
#
# 5) Set Cloud Run env APP_URL=https://your-primary-domain/ and redeploy.

set -euo pipefail

PROJECT="${GCP_PROJECT:-$(gcloud config get-value project 2>/dev/null)}"
REGION="${GCP_REGION:-europe-west2}"
SERVICE="${CLOUD_RUN_SERVICE:-looptrends}"

NEG_NAME="${LB_NEG_NAME:-looptrends-cr-neg}"
BACKEND_NAME="${LB_BACKEND_NAME:-looptrends-ext-backend}"
URL_MAP_NAME="${LB_URL_MAP_NAME:-looptrends-ext-map}"
IP_NAME="${LB_IP_NAME:-looptrends-ext-lb-ip}"
CERT_NAME="${LB_CERT_NAME:-looptrends-ext-cert}"
HTTPS_PROXY_NAME="${LB_HTTPS_PROXY_NAME:-looptrends-ext-https-proxy}"
HTTPS_RULE_NAME="${LB_HTTPS_RULE_NAME:-looptrends-ext-https-rule}"

cmd="${1:-}"

gcloud config set project "$PROJECT" >/dev/null

ensure_compute_api() {
  gcloud services enable compute.googleapis.com --project="$PROJECT" --quiet
}

create_core() {
  ensure_compute_api

  if ! gcloud compute network-endpoint-groups describe "$NEG_NAME" --region="$REGION" >/dev/null 2>&1; then
    gcloud compute network-endpoint-groups create "$NEG_NAME" \
      --region="$REGION" \
      --network-endpoint-type=serverless \
      --cloud-run-service="$SERVICE"
  fi

  if ! gcloud compute backend-services describe "$BACKEND_NAME" --global >/dev/null 2>&1; then
    gcloud compute backend-services create "$BACKEND_NAME" \
      --load-balancing-scheme=EXTERNAL_MANAGED \
      --global
  fi

  if ! gcloud compute backend-services describe "$BACKEND_NAME" --global --format='value(backends[].group)' 2>/dev/null | grep -q "$NEG_NAME"; then
    gcloud compute backend-services add-backend "$BACKEND_NAME" \
      --global \
      --network-endpoint-group="$NEG_NAME" \
      --network-endpoint-group-region="$REGION"
  fi

  if ! gcloud compute url-maps describe "$URL_MAP_NAME" --global >/dev/null 2>&1; then
    gcloud compute url-maps create "$URL_MAP_NAME" \
      --default-service="$BACKEND_NAME" \
      --global
  fi

  if ! gcloud compute addresses describe "$IP_NAME" --global >/dev/null 2>&1; then
    gcloud compute addresses create "$IP_NAME" \
      --network-tier=PREMIUM \
      --ip-version=IPV4 \
      --global
  fi

  IP="$(gcloud compute addresses describe "$IP_NAME" --global --format='get(address)')"
  echo ""
  echo "Core load balancer backend is ready."
  echo "Reserved global IP (use for DNS A/AAAA records): ${IP}"
  echo ""
  echo "Next: LB_DOMAINS='www.yourdomain.com,yourdomain.com' $0 https"
  echo "Then in Cloudflare (or any DNS): point those hostnames at ${IP}"
  echo "      Use DNS only (grey cloud) for simplest Google-managed cert provisioning,"
  echo "      or ensure orange-cloud SSL mode works with your Google-managed cert on the LB."
}

create_https() {
  local domains="${LB_DOMAINS:-}"
  if [[ -z "$domains" ]]; then
    echo "Set LB_DOMAINS to a comma-separated list, e.g. LB_DOMAINS='www.example.com,example.com'" >&2
    exit 1
  fi

  ensure_compute_api

  if ! gcloud compute ssl-certificates describe "$CERT_NAME" --global >/dev/null 2>&1; then
    gcloud compute ssl-certificates create "$CERT_NAME" \
      --domains="$domains" \
      --global
  else
    echo "Certificate ${CERT_NAME} already exists; delete it first to change domains:" >&2
    echo "  gcloud compute ssl-certificates delete ${CERT_NAME} --global" >&2
  fi

  if ! gcloud compute target-https-proxies describe "$HTTPS_PROXY_NAME" --global >/dev/null 2>&1; then
    gcloud compute target-https-proxies create "$HTTPS_PROXY_NAME" \
      --ssl-certificates="$CERT_NAME" \
      --url-map="$URL_MAP_NAME" \
      --global
  else
    gcloud compute target-https-proxies update "$HTTPS_PROXY_NAME" \
      --ssl-certificates="$CERT_NAME" \
      --global-ssl-certificates \
      --global
  fi

  local ip_reservation
  ip_reservation="$(gcloud compute addresses describe "$IP_NAME" --global --format='value(name)')"

  if ! gcloud compute forwarding-rules describe "$HTTPS_RULE_NAME" --global >/dev/null 2>&1; then
    gcloud compute forwarding-rules create "$HTTPS_RULE_NAME" \
      --load-balancing-scheme=EXTERNAL_MANAGED \
      --network-tier=PREMIUM \
      --address="$ip_reservation" \
      --target-https-proxy="$HTTPS_PROXY_NAME" \
      --global \
      --ports=443
  fi

  echo ""
  echo "HTTPS forwarding rule created. Check certificate status (must be ACTIVE):"
  echo "  gcloud compute ssl-certificates describe ${CERT_NAME} --global --format='yaml(managed.status,managed.domainStatus)'"
}

lock_run_ingress() {
  gcloud run services update "$SERVICE" \
    --region="$REGION" \
    --ingress=internal-and-cloud-load-balancing \
    --quiet
  echo "Cloud Run ingress set to internal-and-cloud-load-balancing (only LB and internal)."
}

case "$cmd" in
  core) create_core ;;
  https) create_https ;;
  lock-run-ingress) lock_run_ingress ;;
  *)
    echo "Usage: $0 core|https|lock-run-ingress" >&2
    exit 1
    ;;
esac
