# Payment Intent Workflow Implementation Using Xavante

## Project Overview
This documentation outlines the implementation of a Stripe-like payment intent mechanism using Xavante workflow engine. The system handles payment lifecycle management including authorization, risk checks, capture, and settlement operations while supporting compensation patterns.

### Stripe Payment Intent Reference
Key Stripe payment intent states include:
- Requires payment method
- Requires confirmation
- Requires action
- Processing
- Succeeded
- Canceled

## Xavante Implementation Strategy

### Workflow Components
1. **States**: Represent payment processing phases
2. **Transitions**: State changes governed by conditions
3. **Events**: External triggers for state progression
4. **Actions**: API calls to financial institutions and variable management

---

## Workflow Design

### States Definition

| State | Entry Actions | Exit Actions |
|-------|---------------|--------------|
| **Created** | - Initialize payment metadata<br>- Set default variables | - Log initialization |
| **RequiresPaymentMethod** | - Validate payment method requirements<br>- Start 7-day timeout | - Clear timeout on exit |
| **RequiresConfirmation** | - Initiate risk assessment API call<br>- Create authorization hold | - Release temporary resources |
| **Processing** | - Start settlement timer<br>- Lock payment variables | - Update audit trail |
| **Succeeded** | - Send confirmation webhook<br>- Archive payment data | - Clean temporary data |
| **Canceled** | - Trigger refunds if applicable<br>- Notify merchant system | - Mark timeline completion |

### Transitions & Conditions

| From State | To State | Condition | Actions |
|------------|-----------|-----------|---------|
| Created → RequiresPaymentMethod | Automatic | - Set payment_method_required flag |
| RequiresPaymentMethod → RequiresConfirmation | `payment_method.validated && risk_check.completed` | - Create authorization record |
| RequiresConfirmation → Processing | `authorization.approved && risk_score < threshold` | - Deduct funds from hold |
| Processing → Succeeded | `settlement.confirmed && !timeout_expired` | - Commit transaction |
| Any → Canceled | `user_cancellation || timeout_expired` | - Execute compensation pattern |

### Critical Events
1. **payment_method_attached**
2. **capture_requested**
3. **confirmation_received**
4. **settlement_failed**
5. **timeout_expired**

---

## Flow Scenarios

### Happy Path Flow
1. Customer initiates payment (Created)
2. Attaches valid payment method (→ RequiresConfirmation)
3. System confirms authorization (→ Processing)
4. Settlement completes (→ Succeeded)

### Alternative Flows

**Flow A: Payment Method Failure**
1. Invalid payment method detected
2. Transition to RequiresPaymentMethod with error code
3. Retry counter increments

**Flow B: Risk Check Failure**
1. High-risk score detected
2. Transition to RequiresAction state
3. Trigger manual review process

**Flow C: Settlement Retry**
1. Initial settlement failure
2. Retry counter increments
3. Fallback to alternate payment processor after 3 attempts

---

## Compensation Patterns

1. **Authorization Rollback**
   - Trigger: Settlement failure after authorization
   - Action: Release authorization hold through financial API

2. **Partial Refund**
   - Trigger: Partial cancellation request
   - Action: Calculate refundable amount based on business rules

3. **Timeout Recovery**
   - Trigger: Payment process timeout
   - Action: Restart workflow with preserved context

---

## Technical Implementation

### Key Variables
```php
Variables::create([
    'payment_method' => null,
    'authorization_hold_id' => '',
    'risk_score' => 0,
    'retry_count' => 0,
    'settlement_id' => ''
]);
```

### Operator Usage Examples
```php
// Check payment expiration
OperatorRegistry::get('dateBefore')->evaluate(
    $variables['expiration_date'], 
    new DateTime('now')
);

// Validate amount consistency
OperatorRegistry::get('equals')->evaluate(
    $variables['authorized_amount'],
    $variables['settlement_amount']
);
```

---

## Testing Strategy

1. **State Transition Validation**
   - Verify all legal transitions with mock events

2. **Compensation Flow Tests**
   - Simulate failed settlements with various error codes

3. **Timeout Handling**
   - Test automatic cancellation after T+7 days

4. **Idempotency Checks**
   - Verify duplicate event handling doesn't alter workflow state
