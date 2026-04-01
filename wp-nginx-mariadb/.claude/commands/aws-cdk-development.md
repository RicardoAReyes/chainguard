---
name: aws-cdk-development
description: AWS Cloud Development Kit (CDK) expert for building cloud infrastructure with TypeScript/Python. Use when creating CDK stacks, defining CDK constructs, implementing infrastructure as code, or when the user mentions CDK, CloudFormation, IaC, cdk synth, cdk deploy, or wants to define AWS infrastructure programmatically. Covers CDK app structure, construct patterns, stack composition, and deployment workflows.
---

# AWS CDK Development

Comprehensive guidance for developing AWS infrastructure using the Cloud Development Kit (CDK) — write infrastructure as TypeScript or Python instead of HCL/YAML.

## When to Use

- Creating new CDK stacks or constructs
- Refactoring existing CDK infrastructure
- Implementing Lambda functions within CDK
- Following AWS CDK best practices
- Validating CDK stack configurations before deployment

## Core CDK Principles

### Resource Naming — CRITICAL

Do NOT explicitly specify resource names when they are optional. Let CDK generate unique names automatically.

```typescript
// ❌ BAD — prevents reusability and parallel deployments
new lambda.Function(this, 'MyFunction', {
  functionName: 'my-lambda',  // Avoid this
});

// ✅ GOOD — CDK generates: StackName-MyFunctionXXXXXX
new lambda.Function(this, 'MyFunction', {
  // No functionName — unique per stack automatically
});
```

### Lambda Functions

**TypeScript/JavaScript** — use `@aws-cdk/aws-lambda-nodejs`:
```typescript
import { NodejsFunction } from 'aws-cdk-lib/aws-lambda-nodejs';

new NodejsFunction(this, 'MyFunction', {
  entry: 'lambda/handler.ts',
  handler: 'handler',
  // Auto-handles bundling, deps, transpilation
});
```

**Python** — use `@aws-cdk/aws-lambda-python`:
```typescript
import { PythonFunction } from '@aws-cdk/aws-lambda-python-alpha';

new PythonFunction(this, 'MyFunction', {
  entry: 'lambda',
  index: 'handler.py',
  handler: 'handler',
});
```

## ECS/Fargate Stack Example

```typescript
import * as cdk from 'aws-cdk-lib';
import * as ec2 from 'aws-cdk-lib/aws-ec2';
import * as ecs from 'aws-cdk-lib/aws-ecs';
import * as ecr from 'aws-cdk-lib/aws-ecr';
import * as elbv2 from 'aws-cdk-lib/aws-elasticloadbalancingv2';

export class WordPressStack extends cdk.Stack {
  constructor(scope: cdk.App, id: string, props?: cdk.StackProps) {
    super(scope, id, props);

    const vpc = new ec2.Vpc(this, 'Vpc', { maxAzs: 2 });

    const cluster = new ecs.Cluster(this, 'Cluster', { vpc });

    const repo = ecr.Repository.fromRepositoryName(this, 'Repo', 'wordpress-custom');

    const taskDef = new ecs.FargateTaskDefinition(this, 'TaskDef', {
      cpu: 512,
      memoryLimitMiB: 1024,
    });

    taskDef.addContainer('wordpress', {
      image: ecs.ContainerImage.fromEcrRepository(repo, 'latest'),
      portMappings: [{ containerPort: 80 }],
      logging: ecs.LogDrivers.awsLogs({ streamPrefix: 'wordpress' }),
    });

    const service = new ecs.FargateService(this, 'Service', {
      cluster,
      taskDefinition: taskDef,
      desiredCount: 2,
    });

    const lb = new elbv2.ApplicationLoadBalancer(this, 'ALB', {
      vpc,
      internetFacing: true,
    });

    const listener = lb.addListener('Listener', { port: 80 });
    listener.addTargets('ECS', {
      port: 80,
      targets: [service],
      healthCheck: { path: '/wp-login.php' },
    });

    new cdk.CfnOutput(this, 'LoadBalancerDNS', {
      value: lb.loadBalancerDnsName,
    });
  }
}
```

## Project Structure

```
infrastructure/
├── bin/
│   └── app.ts          # CDK app entry point
├── lib/
│   ├── vpc-stack.ts    # VPC + networking
│   ├── ecr-stack.ts    # Container registry
│   └── ecs-stack.ts    # ECS cluster + services
├── test/
│   └── app.test.ts     # Stack unit tests
├── cdk.json
└── package.json
```

## Workflow Commands

```bash
# Bootstrap AWS account (once per account/region)
cdk bootstrap aws://ACCOUNT-ID/REGION

# Preview changes
cdk diff

# Synthesize CloudFormation templates
cdk synth

# Deploy specific stack
cdk deploy WordPressStack

# Deploy all stacks
cdk deploy --all

# Destroy stack
cdk destroy WordPressStack
```

## Validation

```bash
# Install cdk-nag for synthesis-time security checks
npm install --save-dev cdk-nag
```

```typescript
import { Aspects } from 'aws-cdk-lib';
import { AwsSolutionsChecks } from 'cdk-nag';

const app = new App();
Aspects.of(app).add(new AwsSolutionsChecks());
// cdk synth now enforces AWS Solutions security rules
```

## Best Practices

1. **Separate stacks** per concern: VPC, ECR, ECS, RDS
2. **Use environment-specific context** in `cdk.json` for dev/staging/prod
3. **Never hardcode account IDs** — use `cdk.Aws.ACCOUNT_ID`
4. **Use CDK Pipelines** for self-mutating CI/CD pipelines
5. **Separate AWS accounts** per environment for strongest isolation
6. **Snapshot test** synthesized CloudFormation templates in CI

## GitHub Actions Integration

```yaml
- name: CDK Diff
  run: |
    npm ci
    npx cdk diff
  env:
    AWS_REGION: us-east-1

- name: CDK Deploy
  run: npx cdk deploy --all --require-approval never
  env:
    AWS_REGION: us-east-1
```

## Related Skills

- `infrastructure` — Terraform alternative for the same AWS resources
- `deploy-aws-ecs` — ECS/Fargate deployment details (CLI-based)
- `github-actions-templates` — CI/CD pipeline patterns
