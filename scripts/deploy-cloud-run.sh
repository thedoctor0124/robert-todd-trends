#!/usr/bin/env bash
# Run after: gcloud builds submit --config cloudbuild.yaml .
# Deploys the image tagged :latest in Artifact Registry to Cloud Run (new revision).
#
# Always set GCP_PROJECT to your *production* Cloud Run project (not a random default gcloud project).
# Build and registry must use the same project unless you set ARTIFACT_REGISTRY_IMAGE explicitly.
set -euo pipefail

if [[ -z "${GCP_PROJECT:-}" ]]; then
  echo "Set GCP_PROJECT to the Google Cloud project where Cloud Run and Artifact Registry live." >&2
  echo "Example:  export GCP_PROJECT=your-production-project-id" >&2
  echo "Then run this script again." >&2
  exit 1
fi

PROJECT="$GCP_PROJECT"
REGION="${GCP_REGION:-europe-west2}"
SERVICE="${CLOUD_RUN_SERVICE:-looptrends}"
IMAGE="${ARTIFACT_REGISTRY_IMAGE:-europe-west2-docker.pkg.dev/${PROJECT}/looptrends/app:latest}"

echo "Deploying ${IMAGE} to Cloud Run service '${SERVICE}' (${REGION}, project ${PROJECT})..."

gcloud run deploy "$SERVICE" \
  --project="$PROJECT" \
  --region="$REGION" \
  --image="$IMAGE" \
  --platform=managed \
  --allow-unauthenticated

echo "Done. New revision should be live in ~30s. Hard-refresh the site (Cmd+Shift+R) to bypass cache."
