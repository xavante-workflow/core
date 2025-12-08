# README — Payment Intent Workflow

## Story: The fragmented payment that left money in limbo
Sarah, an e-commerce manager, processed a high-value order through their payment system. The customer's card was charged, but a network glitch prevented the capture confirmation from reaching their system. The authorization expired after 7 days, the funds were automatically released, but the order had already shipped. Sarah spent hours coordinating with payment processors, banks, and accounting to reconcile the lost transaction and manually capture a new payment.

## Solution: A deterministic payment state machine with comprehensive tracking
This workflow introduces a robust state machine for payment processing that handles the complete payment lifecycle:
- **Risk Evaluation** → **3DS Authentication** → **Authorization** → **Capture** → **Settlement**
- Every step is tracked with timeouts, retries, and clear audit trails
- Automatic handling of 3D Secure (SCA) flows and manual capture windows
- Integration with internal APIs for risk checks, authorization, and settlement
- Comprehensive error handling with retry logic and fallback strategies

## When to use it
- Payment processing systems requiring comprehensive state tracking
- Applications needing 3D Secure (Strong Customer Authentication) support
- Systems with manual capture workflows (marketplace, pre-authorization)
- Payment flows requiring risk evaluation and fraud prevention
- Integration with multiple payment service providers (PSPs) or banks

## Core Architecture

### Internal API Integration Points
This workflow integrates with three core internal APIs:

1. **Risk API** (`/api/risk/evaluate`)
   - Evaluates transaction risk based on amount, customer history, device fingerprinting
   - Returns risk level: `low`, `medium`, `high`
   - Determines if 3DS authentication is required

2. **Authorization API** (`/api/payments/authorize`)
   - Communicates with financial institutions for payment authorization
   - Handles 3DS redirects and authentication challenges
   - Returns authorization status and charge details

3. **Settlement API** (`/api/payments/capture`)
   - Captures authorized funds when ready
   - Supports full and partial capture amounts
   - Handles settlement with acquiring banks

## State Model

### States
- **Created** (initial): Payment intent initialized with amount and currency
- **RequiresPaymentMethod**: Waiting for customer to provide payment method
- **RequiresConfirmation**: Payment method attached, ready for confirmation
- **RiskEvaluation**: Assessing transaction risk and fraud indicators
- **RequiresAction**: Customer must complete 3DS authentication or other action
- **Processing**: Authorization in progress with payment processor
- **RequiresCapture**: Authorization succeeded, waiting for manual capture
- **Capturing**: Capture request in progress
- **Succeeded** (final): Payment completed successfully
- **Canceled** (final): Payment canceled or voided
- **Failed** (final): Payment failed after max attempts

### Key Variables
```json
{
  "id": "pi_1234567890",
  "amount": 12500,
  "currency": "USD",
  "customer_id": "cus_abc123",
  "payment_method_id": "pm_card_xyz",
  "payment_method_type": "card",
  "capture_method": "manual|automatic",
  "status": "requires_payment_method",
  "client_secret": "pi_1234567890_secret_abc",
  "latest_charge_id": "ch_1234567890",
  "amount_capturable": 12500,
  "amount_captured": 0,
  "attempt_count": 0,
  "max_attempts": 3,
  "risk_level": "low|medium|high",
  "requires_3ds": false,
  "next_action_type": null,
  "next_action_payload": null,
  "error_code": null,
  "error_message": null,
  "created_at": "2024-11-04T10:00:00Z",
  "updated_at": "2024-11-04T10:00:00Z"
}
```

### Events
- **create_intent**: Initialize payment with amount, currency, capture method
- **attach_payment_method**: Associate payment method (card, bank account, etc.)
- **confirm_intent**: Start the payment authorization process
- **risk_approved**: Risk evaluation passed, proceed to authorization
- **risk_approved_with_3ds**: Risk evaluation passed but requires 3DS
- **risk_blocked**: Risk evaluation failed, block transaction
- **authentication_succeeded**: Customer completed 3DS challenge successfully
- **authentication_failed**: Customer failed or canceled 3DS challenge
- **authorization_succeeded**: Payment processor authorized the payment
- **authorization_failed**: Authorization declined by processor or bank
- **capture**: Request to capture authorized funds (manual capture only)
- **capture_succeeded**: Capture completed successfully
- **capture_failed**: Capture request failed
- **cancel**: Cancel the payment intent
- **timeout**: Various timeout events (risk, action, auth, capture)

### Guards and Conditions
- **Payment method validation**: `payment_method_id exists AND payment_method_type in ['card', 'bank_account']`
- **Risk level checks**: `risk_level == 'low'` or `risk_level == 'high'`
- **3DS requirements**: `requires_3ds == true`
- **Capture method**: `capture_method == 'manual'` or `capture_method == 'automatic'`
- **Retry limits**: `attempt_count < max_attempts`
- **Amount validation**: `capture_amount > 0 AND capture_amount <= amount_capturable`
- **Error categories**: `error_code in ['card_declined', 'insufficient_funds', 'do_not_honor']`

## Actions and Tasks

### State Entry/Exit Actions

**Created**
- Entry: `generateClientSecret()`, `setStatus('created')`, `publishWebhook('payment_intent.created')`

**RequiresPaymentMethod**
- Entry: `setStatus('requires_payment_method')`, `clearNextAction()`, `publishWebhook('payment_intent.requires_payment_method')`
- Exit: `validatePaymentMethod()`

**RequiresConfirmation**
- Entry: `setStatus('requires_confirmation')`, `publishWebhook('payment_intent.payment_method_attached')`
- Exit: `prepareAuthRequest()`

**RiskEvaluation**
- Entry: `setStatus('risk_evaluation')`, `callRiskAPI()`, `scheduleTimeout('risk_timeout', 30)`
- Exit: `clearTimeout('risk_timeout')`

**RequiresAction**
- Entry: `setStatus('requires_action')`, `setNextAction('3ds_redirect')`, `scheduleTimeout('action_timeout', 900)`, `publishWebhook('payment_intent.requires_action')`
- Exit: `clearTimeout('action_timeout')`, `clearNextAction()`

**Processing**
- Entry: `setStatus('processing')`, `incrementAttempt()`, `callAuthAPI()`, `scheduleTimeout('auth_timeout', 300)`, `publishWebhook('payment_intent.processing')`
- Exit: `clearTimeout('auth_timeout')`

**RequiresCapture**
- Entry: `setStatus('requires_capture')`, `setAmountCapturable()`, `scheduleTimeout('capture_timeout', 604800)`, `publishWebhook('payment_intent.requires_capture')`
- Exit: `clearTimeout('capture_timeout')`

**Capturing**
- Entry: `setStatus('capturing')`, `callCaptureAPI()`, `scheduleTimeout('capture_exec_timeout', 60)`
- Exit: `clearTimeout('capture_exec_timeout')`

**Succeeded**
- Entry: `setStatus('succeeded')`, `recordLedgerEntry()`, `publishWebhook('payment_intent.succeeded')`, `notifyMerchant('payment_success')`

**Canceled**
- Entry: `setStatus('canceled')`, `voidAuthorization()`, `publishWebhook('payment_intent.canceled')`, `notifyMerchant('payment_canceled')`

**Failed**
- Entry: `setStatus('failed')`, `setErrorCode('max_attempts_exceeded')`, `publishWebhook('payment_intent.payment_failed')`, `notifyMerchant('payment_failed')`

## Flow Examples

### Happy Path: Simple Card Payment (Auto Capture)
```
Created → RequiresPaymentMethod → RequiresConfirmation → RiskEvaluation → Processing → Succeeded
```

1. Customer provides card details → `attach_payment_method`
2. Merchant confirms payment → `confirm_intent`
3. Risk API approves (low risk) → `risk_approved`
4. Authorization API approves → `authorization_succeeded`
5. Auto-capture succeeds → Payment complete

### 3DS Authentication Flow
```
Created → RequiresPaymentMethod → RequiresConfirmation → RiskEvaluation → RequiresAction → Processing → Succeeded
```

1. Risk evaluation requires 3DS → `risk_approved_with_3ds`
2. Customer redirected to 3DS challenge → `next_action_type: '3ds_redirect'`
3. Customer completes authentication → `authentication_succeeded`
4. Authorization proceeds → `authorization_succeeded`
5. Payment completes

### Manual Capture Flow
```
Created → RequiresPaymentMethod → RequiresConfirmation → RiskEvaluation → Processing → RequiresCapture → Capturing → Succeeded
```

1. Authorization succeeds with manual capture → `authorization_succeeded`
2. Funds held for manual capture → State: `RequiresCapture`
3. Merchant captures when ready → `capture`
4. Capture API succeeds → `capture_succeeded`

### Failure and Retry Flow
```
Processing → RequiresPaymentMethod → RequiresConfirmation → RiskEvaluation → Processing → Failed
```

1. First authorization fails (card declined) → `authorization_failed`
2. Customer provides new payment method → `attach_payment_method`
3. Second attempt fails → `authorization_failed`
4. Max attempts reached → Final state: `Failed`

## Risk Evaluation Integration

The workflow includes comprehensive risk evaluation through internal APIs:

### Risk Assessment Factors
- Transaction amount vs. customer history
- Geographic location and velocity checks
- Device fingerprinting and behavioral analysis
- Payment method risk scoring
- Merchant category and risk profile

### Risk Levels and Actions
- **Low Risk**: Direct authorization without 3DS
- **Medium Risk**: Require 3DS authentication
- **High Risk**: Block transaction immediately

### Risk API Response Format
```json
{
  "risk_level": "medium",
  "risk_score": 65,
  "requires_3ds": true,
  "risk_factors": ["high_amount", "new_card"],
  "recommendation": "authenticate"
}
```

## Timeout Configuration

| Timeout Type | Duration | Purpose |
|--------------|----------|---------|
| Risk Evaluation | 30 seconds | Risk API response timeout |
| Customer Action | 15 minutes | 3DS authentication window |
| Authorization | 5 minutes | Payment processor response |
| Capture Window | 7 days | Manual capture expiration |
| Capture Execution | 60 seconds | Capture API response |

## Error Handling and Retry Logic

### Retryable Errors
- `card_declined`: Customer can try different payment method
- `insufficient_funds`: Customer can try again later
- `network_error`: Automatic retry with backoff
- `temporary_failure`: Retry with exponential backoff

### Non-Retryable Errors
- `do_not_honor`: Terminal decline from bank
- `invalid_card`: Malformed card data
- `stolen_card`: Fraud indicator
- `authentication_failed`: Customer failed 3DS

### Retry Strategy
- Maximum 3 attempts per payment intent
- Exponential backoff: 1s, 2s, 4s delays
- Different payment methods reset attempt count
- Network errors don't count toward attempt limit

## Webhook Events

All state transitions emit webhooks for external system integration:

- `payment_intent.created`
- `payment_intent.requires_payment_method`
- `payment_intent.payment_method_attached`
- `payment_intent.requires_action`
- `payment_intent.processing`
- `payment_intent.requires_capture`
- `payment_intent.succeeded`
- `payment_intent.canceled`
- `payment_intent.payment_failed`

## Security Considerations

- **PCI Compliance**: No sensitive card data stored in workflow variables
- **Client Secrets**: Unique, time-limited tokens for client authentication
- **Idempotency**: All API calls include idempotency keys
- **Audit Trail**: Complete transaction history with timestamps
- **Access Control**: Role-based permissions for capture and cancel operations

## Testing Strategy

### Unit Tests
- State transition logic with various event combinations
- Guard condition evaluation with edge cases
- Action execution with mocked API responses
- Timeout handling and cleanup

### Integration Tests
- End-to-end payment flows with real API calls
- 3DS authentication scenarios
- Manual capture workflows
- Error handling and retry mechanisms

### Load Tests
- Concurrent payment processing
- API rate limiting behavior
- Timeout and circuit breaker functionality

## Operational Monitoring

### Key Metrics
- Payment success rate by state transition
- Average authorization time
- 3DS completion rates
- Manual capture conversion rates
- Error distribution by type and retry count

### Alerting
- High failure rates in authorization
- Risk API timeout spikes
- Manual capture expiration warnings
- Unusual error patterns

## Reference Implementation

See the accompanying files:
- `payment-intent.dot` - Visual state diagram
- `payment-workflow.php` - Xavante implementation
- `workflow.json` - JSON workflow definition
- `test-scenarios.php` - Test case examples

---

This workflow provides a production-ready foundation for payment processing that handles the complexity of modern payment systems while maintaining clear, deterministic state management through Xavante's workflow engine.