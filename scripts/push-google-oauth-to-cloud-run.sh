#!/usr/bin/env bash
# After you create a Web OAuth 2.0 Client in Google Cloud Console (same project as Cloud Run),
# export the values and run this script to store them in Secret Manager and attach to Cloud Run.
#
# Console path: Google Cloud Console → APIs & Services → Credentials → Create credentials →
#   OAuth client ID → Application type: Web application
#
# Authorized JavaScript origins (add both if you use local HTTPS):
#   https://trends.roberttodds.com
#   https://127.0.0.1:8443
#
# Authorized redirect URIs (must match exactly):
#   https://trends.roberttodds.com/auth/google/callback
#   https://127.0.0.1:8443/auth/google/callback
#
# Usage:
#   export GOOGLE_CLIENT_ID="123456789-xxxx.apps.googleusercontent.com"
#   export GOOGLE_CLIENT_SECRET="GOCSPX-..."
#   ./scripts/push-google-oauth-to-cloud-run.sh

set -euo pipefail

PROJECT="${GCP_PROJECT:-$(gcloud config get-value project 2>/dev/null)}"
REGION="${GCP_REGION:-europe-west2}"
SERVICE="${CLOUD_RUN_SERVICE:-looptrends}"
PROJECT_NUM="$(gcloud projects describe "$PROJECT" --format='value(projectNumber)')"
SA="${PROJECT_NUM}-compute@developer.gserviceaccount.com"

ID_SECRET_NAME="${GOOGLE_OAUTH_ID_SECRET:-looptrends-google-oauth-client-id}"
SECRET_SECRET_NAME="${GOOGLE_OAUTH_SECRET_SECRET:-looptrends-google-oauth-client-secret}"

if [[ -z "${GOOGLE_CLIENT_ID:-}" || -z "${GOOGLE_CLIENT_SECRET:-}" ]]; then
  echo "Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET (from APIs & Services → Credentials)." >&2
  exit 1
fi

gcloud config set project "$PROJECT" >/dev/null

store_secret() {
  local name="$1"
  local value="$2"
  if gcloud secrets describe "$name" --project="$PROJECT" >/dev/null 2>&1; then
    printf '%s' "$value" | gcloud secrets versions add "$name" --data-file=- --project="$PROJECT"
  else
    printf '%s' "$value" | gcloud secrets create "$name" --data-file=- --replication-policy=automatic --project="$PROJECT"
  fi
  gcloud secrets add-iam-policy-binding "$name" \
    --member="serviceAccount:${SA}" \
    --role="roles/secretmanager.secretAccessor" \
    --project="$PROJECT" \
    --quiet >/dev/null 2>&1 || true
}

store_secret "$ID_SECRET_NAME" "$GOOGLE_CLIENT_ID"
store_secret "$SECRET_SECRET_NAME" "$GOOGLE_CLIENT_SECRET"

gcloud run services update "$SERVICE" \
  --region="$REGION" \
  --quiet \
  --update-env-vars "GOOGLE_REDIRECT_URI=/auth/google/callback" \
  --set-secrets "DB_PASSWORD=looptrends-db-password:latest,APP_KEY=looptrends-app-key:latest,GOOGLE_CLIENT_ID=${ID_SECRET_NAME}:latest,GOOGLE_CLIENT_SECRET=${SECRET_SECRET_NAME}:latest"

echo "Cloud Run updated. OAuth uses APP_URL + GOOGLE_REDIRECT_URI (relative path is fine)."
echo "Ensure APP_URL on Cloud Run matches your public site (e.g. https://trends.roberttodds.com)."
