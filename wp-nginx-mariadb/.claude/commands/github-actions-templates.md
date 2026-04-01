---
name: github-actions-templates
description: "Production-ready GitHub Actions workflow patterns for testing, building, and deploying applications."
risk: critical
source: community
date_added: "2026-02-27"
---

# GitHub Actions Templates

Production-ready GitHub Actions workflow patterns for testing, building, and deploying applications.

## Use this skill when

- Automate testing and deployment
- Build Docker images and push to registries
- Deploy to AWS ECS or Kubernetes clusters
- Run security scans
- Implement matrix builds for multiple environments

## Pattern 1: Test Workflow

```yaml
name: Test

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        node-version: [18.x, 20.x]
    steps:
    - uses: actions/checkout@v4
    - name: Use Node.js ${{ matrix.node-version }}
      uses: actions/setup-node@v4
      with:
        node-version: ${{ matrix.node-version }}
        cache: 'npm'
    - run: npm ci
    - run: npm run lint
    - run: npm test
    - uses: codecov/codecov-action@v3
      with:
        files: ./coverage/lcov.info
```

## Pattern 2: Build and Push Docker Image to ECR

```yaml
name: Build and Push to ECR

on:
  push:
    branches: [ main ]
    tags: [ 'v*' ]

env:
  AWS_REGION: us-east-1
  ECR_REPOSITORY: my-app

jobs:
  build:
    runs-on: ubuntu-latest
    permissions:
      id-token: write   # OIDC
      contents: read

    steps:
    - uses: actions/checkout@v4

    - name: Configure AWS credentials (OIDC)
      uses: aws-actions/configure-aws-credentials@v4
      with:
        role-to-assume: arn:aws:iam::${{ secrets.AWS_ACCOUNT_ID }}:role/github-actions-role
        aws-region: ${{ env.AWS_REGION }}

    - name: Login to Amazon ECR
      id: login-ecr
      uses: aws-actions/amazon-ecr-login@v2

    - name: Extract metadata
      id: meta
      uses: docker/metadata-action@v5
      with:
        images: ${{ steps.login-ecr.outputs.registry }}/${{ env.ECR_REPOSITORY }}
        tags: |
          type=sha
          type=semver,pattern={{version}}

    - name: Build and push
      uses: docker/build-push-action@v5
      with:
        context: .
        push: true
        tags: ${{ steps.meta.outputs.tags }}
        cache-from: type=gha
        cache-to: type=gha,mode=max
```

## Pattern 3: Deploy to AWS ECS

```yaml
name: Deploy to ECS

on:
  push:
    branches: [ main ]

env:
  AWS_REGION: us-east-1
  ECS_CLUSTER: my-cluster
  ECS_SERVICE: my-service
  CONTAINER_NAME: my-app

jobs:
  deploy:
    runs-on: ubuntu-latest
    environment: production
    permissions:
      id-token: write
      contents: read

    steps:
    - uses: actions/checkout@v4

    - name: Configure AWS credentials (OIDC)
      uses: aws-actions/configure-aws-credentials@v4
      with:
        role-to-assume: arn:aws:iam::${{ secrets.AWS_ACCOUNT_ID }}:role/github-actions-role
        aws-region: ${{ env.AWS_REGION }}

    - name: Login to Amazon ECR
      id: login-ecr
      uses: aws-actions/amazon-ecr-login@v2

    - name: Build, tag, and push image
      id: build-image
      env:
        ECR_REGISTRY: ${{ steps.login-ecr.outputs.registry }}
        IMAGE_TAG: ${{ github.sha }}
      run: |
        docker build -t $ECR_REGISTRY/my-app:$IMAGE_TAG .
        docker push $ECR_REGISTRY/my-app:$IMAGE_TAG
        echo "image=$ECR_REGISTRY/my-app:$IMAGE_TAG" >> $GITHUB_OUTPUT

    - name: Download task definition
      run: |
        aws ecs describe-task-definition --task-definition my-task \
          --query taskDefinition > task-definition.json

    - name: Update ECS task definition with new image
      id: task-def
      uses: aws-actions/amazon-ecs-render-task-definition@v1
      with:
        task-definition: task-definition.json
        container-name: ${{ env.CONTAINER_NAME }}
        image: ${{ steps.build-image.outputs.image }}

    - name: Deploy to ECS
      uses: aws-actions/amazon-ecs-deploy-task-definition@v1
      with:
        task-definition: ${{ steps.task-def.outputs.task-definition }}
        service: ${{ env.ECS_SERVICE }}
        cluster: ${{ env.ECS_CLUSTER }}
        wait-for-service-stability: true
```

## Pattern 4: Security Scanning with Grype

```yaml
name: Security Scan

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v4

    - name: Run Grype vulnerability scanner
      uses: anchore/scan-action@v3
      with:
        image: "my-app:latest"
        fail-build: true
        severity-cutoff: critical

    - name: Run Trivy vulnerability scanner
      uses: aquasecurity/trivy-action@master
      with:
        scan-type: 'fs'
        format: 'sarif'
        output: 'trivy-results.sarif'

    - name: Upload results to GitHub Security
      uses: github/codeql-action/upload-sarif@v2
      with:
        sarif_file: 'trivy-results.sarif'
```

## Pattern 5: Reusable Workflow

```yaml
# .github/workflows/reusable-deploy.yml
name: Reusable Deploy

on:
  workflow_call:
    inputs:
      environment:
        required: true
        type: string
      image-tag:
        required: true
        type: string
    secrets:
      AWS_ACCOUNT_ID:
        required: true

jobs:
  deploy:
    runs-on: ubuntu-latest
    environment: ${{ inputs.environment }}
    steps:
    - uses: actions/checkout@v4
    - name: Configure AWS credentials
      uses: aws-actions/configure-aws-credentials@v4
      with:
        role-to-assume: arn:aws:iam::${{ secrets.AWS_ACCOUNT_ID }}:role/github-actions-role
        aws-region: us-east-1
    - name: Deploy
      run: |
        aws ecs update-service \
          --cluster ${{ inputs.environment }}-cluster \
          --service my-service \
          --force-new-deployment
```

## Best Practices

1. **Use OIDC instead of long-lived AWS keys** — `id-token: write` permission + IAM role
2. **Use specific action versions** (`@v4`, not `@latest`)
3. **Cache dependencies** to speed up builds (`cache-from: type=gha`)
4. **Use environments** for production approval gates
5. **Set least-privilege permissions** per job
6. **Use reusable workflows** for common deploy patterns
7. **Always `wait-for-service-stability`** on ECS deploys

## Related Skills

- `github-actions-docs` — Official GitHub Actions documentation reference
- `deploy-aws-ecs` — ECS/Fargate deployment details
- `infrastructure` — Terraform for provisioning the AWS resources
