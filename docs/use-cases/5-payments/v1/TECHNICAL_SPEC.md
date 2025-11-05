# Payment Intent Workflow - Technical Specification

## Overview

This technical specification defines the implementation of a comprehensive payment processing workflow using the Xavante workflow engine. The workflow handles the complete lifecycle of payment intents, from creation through final settlement or cancellation, with support for 3D Secure authentication, risk evaluation, and both manual and automatic capture modes.

## Architecture

### State Machine Design

The payment intent workflow is implemented as a deterministic finite state machine with the following characteristics:

- **11 states** (1 initial, 3 final, 7 intermediate)
- **24 transitions** with conditional guards and actions
- **Event-driven progression** with external API integration
- **Comprehensive timeout handling** for all asynchronous operations
- **Retry logic** with configurable attempt limits
- **Audit trail** with complete transaction history

### Core Components

#### 1. States

| State | Type | Purpose | Timeout |
|-------|------|---------|---------|
| Created | Initial | Payment intent initialized | - |
| RequiresPaymentMethod | Intermediate | Awaiting payment method attachment | - |
| RequiresConfirmation | Intermediate | Ready for payment confirmation | - |
| RiskEvaluation | Intermediate | Risk assessment in progress | 30s |
| RequiresAction | Intermediate | Customer action required (3DS) | 15min |
| Processing | Intermediate | Authorization in progress | 5min |
| RequiresCapture | Intermediate | Manual capture pending | 7 days |
| Capturing | Intermediate | Capture execution in progress | 60s |
| Succeeded | Final | Payment completed successfully | - |
| Canceled | Final | Payment canceled or voided | - |
| Failed | Final | Payment failed after max attempts | - |

#### 2. Events

**External Events** (triggered by API calls):
- `create_intent` - Initialize new payment intent
- `attach_payment_method` - Associate payment method
- `confirm_intent` - Start payment processing
- `capture` - Request manual capture
- `cancel` - Cancel payment intent

**Internal Events** (triggered by API responses):
- `risk_approved` - Risk evaluation passed
- `risk_approved_with_3ds` - Risk evaluation passed, 3DS required
- `risk_blocked` - Risk evaluation blocked transaction
- `authentication_succeeded` - 3DS authentication successful
- `authentication_failed` - 3DS authentication failed
- `authorization_succeeded` - Payment authorization successful
- `authorization_failed` - Payment authorization failed
- `capture_succeeded` - Capture completed successfully
- `capture_failed` - Capture request failed
- `timeout` - Various timeout events

#### 3. Variables Schema

```json
{
  "id": "string (required)",
  "amount": "integer (required, cents)",
  "currency": "string (required, ISO 4217)",
  "customer_id": "string",
  "payment_method_id": "string",
  "payment_method_type": "string (card|bank_account|wallet)",
  "capture_method": "string (automatic|manual)",
  "status": "string",
  "client_secret": "string",
  "latest_charge_id": "string",
  "amount_capturable": "integer",
  "amount_captured": "integer",
  "attempt_count": "integer",
  "max_attempts": "integer (default: 3)",
  "risk_level": "string (low|medium|high)",
  "requires_3ds": "boolean",
  "next_action_type": "string",
  "next_action_payload": "object",
  "error_code": "string",
  "error_message": "string",
  "cancellation_reason": "string",
  "created_at": "string (ISO 8601)",
  "updated_at": "string (ISO 8601)"
}
```

## API Integration Points

### 1. Risk Evaluation API

**Endpoint**: `POST /api/risk/evaluate`

**Request**:
```json
{
  "amount": 12500,
  "currency": "USD",
  "customer_id": "cus_abc123",
  "payment_method_type": "card",
  "transaction_context": {
    "ip_address": "192.168.1.1",
    "device_fingerprint": "fp_xyz789",
    "merchant_category": "retail"
  }
}
```

**Response**:
```json
{
  "risk_level": "medium",
  "risk_score": 65,
  "requires_3ds": true,
  "risk_factors": ["high_amount", "new_card"],
  "recommendation": "authenticate",
  "evaluation_time_ms": 150
}
```

**Error Response**:
```json
{
  "error": {
    "code": "risk_evaluation_failed",
    "message": "Risk service temporarily unavailable",
    "retry_after": 30
  }
}
```

### 2. Authorization API

**Endpoint**: `POST /api/payments/authorize`

**Request**:
```json
{
  "payment_intent_id": "pi_1234567890",
  "amount": 12500,
  "currency": "USD",
  "payment_method_id": "pm_card_xyz",
  "capture_method": "automatic",
  "customer_authentication": {
    "3ds_result": "authenticated",
    "3ds_transaction_id": "3ds_abc123"
  },
  "idempotency_key": "idem_key_123"
}
```

**Success Response**:
```json
{
  "charge_id": "ch_1234567890",
  "status": "succeeded",
  "amount_authorized": 12500,
  "amount_captured": 12500,
  "authorization_code": "123456",
  "processor_response": {
    "code": "00",
    "message": "Approved"
  }
}
```

**3DS Required Response**:
```json
{
  "status": "requires_action",
  "next_action": {
    "type": "3ds_redirect",
    "redirect_url": "https://3ds.example.com/challenge",
    "return_url": "https://merchant.com/return"
  }
}
```

**Failure Response**:
```json
{
  "status": "failed",
  "error": {
    "code": "card_declined",
    "message": "Your card was declined.",
    "decline_code": "insufficient_funds"
  }
}
```

### 3. Capture API

**Endpoint**: `POST /api/payments/capture`

**Request**:
```json
{
  "charge_id": "ch_1234567890",
  "amount": 10000,
  "idempotency_key": "capture_idem_123"
}
```

**Success Response**:
```json
{
  "charge_id": "ch_1234567890",
  "status": "succeeded",
  "amount_captured": 10000,
  "settlement_batch_id": "batch_789",
  "captured_at": "2024-11-04T15:30:00Z"
}
```

**Failure Response**:
```json
{
  "status": "failed",
  "error": {
    "code": "authorization_expired",
    "message": "The authorization has expired and cannot be captured"
  }
}
```

## State Transition Logic

### 1. Guards and Conditions

The workflow uses Xavante's operator system for conditional transitions:

**Basic Operators**:
- `equals` / `not_equals` - Value equality/inequality
- `greater_than` / `less_than` - Numeric comparisons
- `greater_than_or_equal` / `less_than_or_equal` - Numeric comparisons with equality
- `is_null` / `is_not_null` - Null checks
- `is_empty` / `is_not_empty` - Empty value checks

**Common Guard Patterns**:
```json
{
  "payment_method_exists": {
    "variable": "payment_method_id",
    "operator": "is_not_null"
  },
  "retry_allowed": {
    "variable": "attempt_count",
    "operator": "less_than",
    "value": "max_attempts"
  },
  "manual_capture": {
    "variable": "capture_method",
    "operator": "equals",
    "value": "manual"
  }
}
```

### 2. Action Execution

Actions are executed at three points in the state machine:

**Entry Actions** (on state entry):
- Variable updates (status, timestamps)
- API calls (risk evaluation, authorization, capture)
- Timeout scheduling
- Webhook publishing

**Exit Actions** (on state exit):
- Cleanup operations (timeout clearing)
- Validation logic
- Audit logging

**Transition Actions** (during transition):
- Data transformation
- Event propagation
- Error handling

### 3. Error Handling Strategy

**Retryable Errors**:
- Network timeouts → Exponential backoff retry
- Rate limits → Delayed retry with backoff
- Temporary service unavailability → Retry with circuit breaker

**Non-Retryable Errors**:
- Invalid payment method data → Immediate failure
- Fraud detection triggers → Immediate cancellation
- Compliance violations → Immediate blocking

**Retry Configuration**:
```json
{
  "max_attempts": 3,
  "base_delay_ms": 1000,
  "max_delay_ms": 30000,
  "backoff_multiplier": 2.0,
  "jitter": true
}
```

## Timeout Management

### Timeout Configuration

| Operation | Timeout | Retry Strategy |
|-----------|---------|----------------|
| Risk Evaluation | 30s | 3 attempts, exponential backoff |
| Customer Action (3DS) | 15min | No retry, customer abandonment |
| Authorization | 5min | 2 attempts, linear backoff |
| Capture Execution | 60s | 3 attempts, exponential backoff |
| Capture Window | 7 days | No retry, authorization expires |

### Timeout Implementation

```php
// Schedule timeout
$workflow->scheduleTimeout('risk_timeout', 30); // 30 seconds

// Handle timeout event
if ($event['timeout_type'] === 'risk_timeout') {
    $this->transitionTo('canceled');
    $this->setCancellationReason('risk_evaluation_timeout');
}
```

## Webhook Events

All state changes emit webhooks for external system integration:

### Event Types

| Event | Trigger | Payload Fields |
|-------|---------|----------------|
| `payment_intent.created` | Payment intent initialized | id, amount, currency, status |
| `payment_intent.requires_payment_method` | Awaiting payment method | id, status, client_secret |
| `payment_intent.payment_method_attached` | Payment method added | id, status, payment_method_id |
| `payment_intent.requires_action` | Customer action needed | id, status, next_action |
| `payment_intent.processing` | Authorization started | id, status, attempt_count |
| `payment_intent.requires_capture` | Manual capture needed | id, status, amount_capturable |
| `payment_intent.succeeded` | Payment completed | id, status, amount_captured, charge_id |
| `payment_intent.canceled` | Payment canceled | id, status, cancellation_reason |
| `payment_intent.payment_failed` | Payment failed | id, status, error_code, attempt_count |

### Webhook Payload Example

```json
{
  "id": "pi_1234567890",
  "object": "payment_intent",
  "type": "payment_intent.succeeded",
  "data": {
    "id": "pi_1234567890",
    "amount": 12500,
    "currency": "usd",
    "status": "succeeded",
    "amount_captured": 12500,
    "latest_charge_id": "ch_1234567890",
    "payment_method_id": "pm_card_xyz",
    "created_at": "2024-11-04T10:00:00Z",
    "updated_at": "2024-11-04T10:01:30Z"
  },
  "created": 1699097490,
  "livemode": false
}
```

## Security Considerations

### Data Protection
- **PCI Compliance**: No sensitive card data stored in workflow variables
- **Client Secrets**: Time-limited, unique tokens for client authentication
- **Idempotency Keys**: Prevent duplicate processing of requests
- **Audit Trail**: Complete transaction history with tamper-proof logging

### Access Control
```json
{
  "permissions": {
    "create_payment_intent": ["merchant", "platform"],
    "confirm_payment": ["merchant", "customer"],
    "capture_payment": ["merchant"],
    "cancel_payment": ["merchant", "customer"],
    "force_finalize": ["admin", "support"]
  }
}
```

### Rate Limiting
```json
{
  "rate_limits": {
    "per_merchant": "1000/hour",
    "per_customer": "100/hour",
    "per_payment_method": "10/minute"
  }
}
```

## Performance Characteristics

### Throughput Benchmarks
- **Workflow Creation**: ~500 workflows/second
- **State Transitions**: ~1,000 transitions/second  
- **JSON Serialization**: ~2,000 serializations/second
- **Event Processing**: ~800 events/second

### Memory Usage
- **Base Workflow**: ~2KB per instance
- **With Full Context**: ~5KB per instance
- **Peak Memory**: <10MB per 1,000 concurrent workflows

### Latency Targets
- **State Transition**: <10ms (99th percentile)
- **Webhook Delivery**: <100ms (95th percentile)
- **API Integration**: <2s (including network calls)

## Monitoring and Observability

### Key Metrics

**Success Metrics**:
- Payment success rate by payment method type
- Average time to completion
- 3DS completion rate
- Manual capture conversion rate

**Error Metrics**:
- Authorization failure rate by error code
- Timeout frequency by operation type
- Retry attempt distribution
- Risk evaluation blocking rate

**Performance Metrics**:
- State transition latency (P50, P95, P99)
- API call duration and success rate
- Webhook delivery success rate
- Memory and CPU utilization

### Alerting Rules

```yaml
alerts:
  - name: HighAuthorizationFailureRate
    condition: authorization_failure_rate > 10% for 5m
    severity: warning
    
  - name: RiskAPITimeout
    condition: risk_api_timeout_rate > 5% for 2m
    severity: critical
    
  - name: ManualCaptureExpiration
    condition: capture_expiry_approaching
    severity: warning
    schedule: daily
```

### Logging Strategy

**Structured Logging**:
```json
{
  "timestamp": "2024-11-04T10:00:00Z",
  "level": "INFO",
  "component": "payment_workflow",
  "payment_intent_id": "pi_1234567890",
  "event": "state_transition",
  "from_state": "processing",
  "to_state": "succeeded",
  "event_name": "authorization_succeeded",
  "duration_ms": 1250,
  "metadata": {
    "charge_id": "ch_1234567890",
    "amount": 12500,
    "payment_method_type": "card"
  }
}
```

## Testing Strategy

### Test Categories

**Unit Tests**:
- State transition logic validation
- Guard condition evaluation
- Action execution verification
- Error handling scenarios

**Integration Tests**:
- API integration with mocked services
- Webhook delivery verification
- Timeout handling validation
- End-to-end flow testing

**Load Tests**:
- Concurrent payment processing
- High-throughput scenarios
- Memory leak detection
- Performance regression testing

**Chaos Tests**:
- API failure simulation
- Network partition handling
- Service degradation scenarios
- Data corruption recovery

### Test Scenarios

1. **Happy Path Scenarios** (60% coverage)
   - Auto capture without 3DS
   - Auto capture with 3DS
   - Manual capture flow
   - Partial capture

2. **Error Scenarios** (30% coverage)
   - Authorization failures
   - Risk blocking
   - Timeout handling
   - Network failures

3. **Edge Cases** (10% coverage)
   - Concurrent modifications
   - Race conditions
   - Data inconsistencies
   - System recovery

## Deployment and Operations

### Configuration Management

```yaml
payment_workflow:
  timeouts:
    risk_evaluation: 30s
    customer_action: 900s
    authorization: 300s
    capture_execution: 60s
    capture_window: 7d
    
  retry_policy:
    max_attempts: 3
    base_delay: 1s
    max_delay: 30s
    multiplier: 2.0
    
  feature_flags:
    enable_3ds: true
    enable_risk_evaluation: true
    enable_partial_capture: false
    skip_webhook_verification: false
```

### Health Checks

```http
GET /health/payment-workflow
{
  "status": "healthy",
  "checks": {
    "workflow_engine": "ok",
    "risk_api": "ok", 
    "auth_api": "ok",
    "capture_api": "degraded",
    "webhook_delivery": "ok"
  },
  "metrics": {
    "active_workflows": 1247,
    "processing_rate": "450/min",
    "error_rate": "0.2%"
  }
}
```

### Rollback Strategy

1. **Blue-Green Deployment**: Zero-downtime workflow updates
2. **Feature Flags**: Gradual rollout of new functionality
3. **Version Compatibility**: Backward compatibility for in-flight workflows
4. **State Migration**: Safe migration of workflow instances

This technical specification provides a comprehensive foundation for implementing and operating a production-grade payment processing workflow using the Xavante engine.