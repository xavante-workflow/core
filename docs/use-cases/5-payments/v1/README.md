# Xavante Payment Intent Workflow (Stripe-Compatible)

## 1) Overview

This document describes a state-machine-based workflow, implemented with the Xavante PHP workflow engine, to emulate the core functionality of Stripe’s Payment Intents. The objectives are:

- Provide a deterministic, JSON-first workflow template modeling the lifecycle of a payment intent.
- Mirror the core PaymentIntent statuses and flows: creating, confirming, authenticating, processing, capturing, canceling, and succeeding.
- Support manual vs automatic capture, on-session vs off-session confirmations, and customer authentication (3DS/SCA) where required.
- Enable event-driven orchestration and compensations (Saga style), with well-defined actions, timeouts, retries, and idempotency controls.
- Offer a robust testing and serialization story leveraging Xavante’s factories, operators, and Processor.

This design prioritizes clarity, determinism, and composability. It is intentionally verbose to double as implementation documentation and a reference for developers integrating payments with downstream systems (order, inventory, fulfillment, accounting, webhooks).

## 2) Primer: How Stripe Payment Intents Work (Conceptual)

Stripe’s PaymentIntent models the lifecycle of a single attempt to collect payment from a customer. It encapsulates details such as amount, currency, payment method, capture strategy (manual or automatic), customer authentication requirements (SCA/3DS), and the final result.

Key conceptual statuses you’ll emulate:

- requires_payment_method — The intent exists but needs a viable payment method attached.
- requires_confirmation — A payment method is present; the merchant or system must confirm the intent.
- requires_action — Additional customer action is required (for example, 3DS authentication or a redirect for certain methods).
- processing — The intent is in-flight (authorization, third-party settlement, or async processing).
- requires_capture — Authorization is successful but capture is pending (manual capture flows).
- canceled — The intent was canceled, expired, or voided (pre-capture).
- succeeded — Funds captured successfully and the intent is terminal.

Activities that move the intent along include attaching payment methods, confirming, handling authentication, authorizing, capturing (if manual), and canceling. Some steps may be async and depend on webhook events from payment networks.

Note: Refunds of captured charges are a separate concern in Stripe; this design mentions compensations but focuses on the PaymentIntent itself.

## 3) Xavante in Brief

Xavante is a PHP library for deterministic, versioned workflows built as state machines with a JSON-first design. It supports:

- Immutable workflow templates and stateful runtime instances
- Deterministic transitions guarded by conditions and roles
- Entry/exit/transition actions with retry policies
- Rich operator library for guards and conditions
- Event-driven orchestration (publish/subscribe style)
- Useful built-in actions (like SetVariableValue, triggerEvent) and the special forceMoveAllStatesToFinalState for admin overrides

Core API elements you’ll use:

- WorkflowFactory, StateFactory, TransitionFactory to construct templates and instances.
- Processor to apply events and progress the workflow deterministically.
- OperatorRegistry to evaluate guards (equals, and, or, not, exists, greater_than, in, etc.).

## 4) Domain Variables (Workflow Variables)

Define these top-level variables for the Payment Intent instance. They are set or updated by actions and events and evaluated by guards:

- id: string (payment_intent_id)
- amount: integer (minor units, e.g., cents)
- currency: string (e.g., \"usd\")
- customer_id: string | null
- payment_method_id: string | null
- status: string (mirrors state name or mapped status)
- capture_method: string (\"automatic\" | \"manual\")
- confirmation_method: string (\"automatic\" | \"manual\")
- off_session: bool (true if the payment is confirmed without the customer actively in session)
- client_secret: string (opaque token for client-side interactions)
- latest_charge_id: string | null
- amount_capturable: integer (for manual capture)
- amount_captured: integer
- next_action: string | null (e.g., \"redirect_to_url\", \"use_sdk\")
- requires_action_reason: string | null (e.g., \"sca_required\", \"redirect_required\")
- risk_level: string | null (e.g., \"normal\", \"elevated\", \"blocked\")
- attempt_count: int (number of payment attempts)
- max_attempts: int (default 3)
- idempotency_key: string | null (for confirm/capture/cancel operations)
- error_code: string | null (e.g., \"card_declined\", \"insufficient_funds\", \"authentication_failed\")
- payment_method_type: string | null (e.g., \"card\", \"card_3ds\", \"bank_redirect\", \"wallet\")
- async_method: bool (true if awaiting asynchronous confirmation from provider)
- timeouts: object (e.g., {\"auth_timeout_s\": 900, \"customer_action_timeout_s\": 1800})
- feature_flags: object (e.g., {\"skip_risk\": false, \"simulate_webhook\": false})
- created_at, updated_at: timestamps

These variables are typically validated by a Factory, persisted per instance, and serialized to JSON.

## 5) External Events (Triggers)

These are events your application emits to the Processor to drive state transitions:

- create_intent(payload): Initializes the intent (amount, currency, capture_method, etc.). Usually called once when the workflow instance is created.
- attach_payment_method({payment_method_id, payment_method_type}): Provide or update the payment method.
- confirm({off_session?, idempotency_key?}): Confirm the intent. May initiate auth or async flows.
- authentication_succeeded({auth_data}): Customer completed 3DS or required action.
- authentication_failed({reason, error_code}): Customer failed or canceled required action.
- authorization_succeeded({charge_id, amount_authorized}): Gateway authorized funds.
- authorization_failed({error_code}): Authorization attempt failed.
- capture({amount_to_capture?, idempotency_key?}): Capture authorized funds (manual capture).
- capture_succeeded({amount_captured}): Capture completed successfully.
- capture_failed({error_code}): Capture failed.
- cancel({reason, idempotency_key?}): Cancel the intent (void if authorized but not captured).
- timeout({type}): Fired by scheduled timers (e.g., \"customer_action_timeout\" or \"auth_timeout\").
- risk_blocked({reason?}): A risk engine blocked the transaction.
- webhook_settled({provider_status, charge_id?}): Async settlement callback (for async methods).
- override_force_finalize({target}): Administrative override to force final state (rare).

All events are deterministic inputs; they can be replayed to reconstruct state consistently.

## 6) Actions (Reusable Tasks)

You will implement a set of actions that can be used on state entry/exit or transition execution. Examples:

- SetVariableValue(name, value): Built-in; set a variable deterministically.
- GenerateClientSecret(): Generate a new client_secret.
- ValidatePaymentMethod(): Verify PM structure/availability.
- PrepareAuthorizationRequest(): Normalize fields for gateway request.
- CallPaymentGatewayAuthorize(): Make an idempotent auth call.
- CallPaymentGatewayCapture(): Make an idempotent capture call.
- PublishWebhookEvent(name, payload): Publish domain events outward.
- ScheduleTimeout(name, seconds): Schedule a timeout event into your system.
- ClearTimeout(name): Cancel a previously scheduled timeout.
- IncrementAttempt(): attempt_count++
- RecordLedgerEntry(type, amount): Write accounting entries.
- ReserveInventory() / ReleaseInventory(): Optional compensations.
- SendCustomerEmail(template, params): Notify stakeholders.
- triggerEvent(name, payload): Built-in; chain internal events deterministically.
- forceMoveAllStatesToFinalState(target): Built-in; administrative finalization.
- AuditLog(message, meta): Persist an audit trail.

Each action should be pure with respect to workflow determinism. For external side effects, use idempotency keys and predictable outcomes (store outcomes in variables).

## 7) States and Their Entry/Exit Actions

We will model states to match Stripe-like statuses:

1) RequiresPaymentMethod
- Meaning: Intent exists, needs a valid payment method.
- onEnter:
  - GenerateClientSecret()
  - SetVariableValue(\"status\", \"requires_payment_method\")
  - PublishWebhookEvent(\"payment_intent.created\", {...})
- onExit:
  - ValidatePaymentMethod()
  - Clear error_code, next_action, requires_action_reason
- Typical incoming events: attach_payment_method
- Typical outgoing transitions: to RequiresConfirmation

2) RequiresConfirmation
- Meaning: PM present; awaits confirm.
- onEnter:
  - SetVariableValue(\"status\", \"requires_confirmation\")
  - PublishWebhookEvent(\"payment_intent.payment_method_attached\", {...})
- onExit:
  - PrepareAuthorizationRequest()
  - Optional: Risk screening pre-authorization (if not skipped by feature_flags)
- Typical incoming events: confirm
- Outgoing transitions:
  - to RequiresAction (if SCA or redirect required)
  - to Processing (if no additional customer action)
  - to Canceled (if risk_blocked)

3) RequiresAction
- Meaning: Customer must perform an action (e.g., 3DS challenge).
- onEnter:
  - SetVariableValue(\"status\", \"requires_action\")
  - ScheduleTimeout(\"customer_action_timeout\", timeouts.customer_action_timeout_s)
  - PublishWebhookEvent(\"payment_intent.requires_action\", {next_action,...})
- onExit:
  - ClearTimeout(\"customer_action_timeout\")
- Incoming events: authentication_succeeded, authentication_failed, timeout(\"customer_action_timeout\"), cancel
- Outgoing transitions:
  - to Processing (on authentication_succeeded)
  - to RequiresPaymentMethod (on authentication_failed recoverable)
  - to Canceled (on timeout or cancel)

4) Processing
- Meaning: In-flight authorization/capture or awaiting async provider callback.
- onEnter:
  - SetVariableValue(\"status\", \"processing\")
  - IncrementAttempt()
  - CallPaymentGatewayAuthorize() when appropriate (idempotent)
  - ScheduleTimeout(\"auth_timeout\", timeouts.auth_timeout_s)
  - PublishWebhookEvent(\"payment_intent.processing\", {...})
- onExit:
  - ClearTimeout(\"auth_timeout\")
- Incoming events: authorization_succeeded, authorization_failed, webhook_settled, timeout(\"auth_timeout\"), cancel
- Outgoing transitions:
  - to RequiresCapture (if capture_method = manual and auth success)
  - to Succeeded (if capture_method = automatic and authorization+capture succeed or provider indicates settled)
  - to RequiresPaymentMethod (on authorization_failed recoverable and attempts left)
  - to Canceled (on cancel, risk block, or terminal failure)

5) RequiresCapture
- Meaning: Authorization succeeded; merchant must capture funds manually.
- onEnter:
  - SetVariableValue(\"status\", \"requires_capture\")
  - SetVariableValue(\"amount_capturable\", amount)
  - PublishWebhookEvent(\"payment_intent.requires_capture\", {...})
- onExit:
  - none, or bookkeeping like ClearTimeouts if defined
- Incoming events: capture, cancel
- Outgoing transitions:
  - to Succeeded (on capture_succeeded)
  - to Canceled (on cancel/void)

6) Succeeded (Final)
- Meaning: Payment captured successfully.
- onEnter:
  - SetVariableValue(\"status\", \"succeeded\")
  - RecordLedgerEntry(\"payment_captured\", amount_captured or amount)
  - PublishWebhookEvent(\"payment_intent.succeeded\", {...})
  - SendCustomerEmail(\"receipt\", {...})
  - ReleaseInventory() if reserved and now final fulfillment can proceed (optional; some workflows reserve only after capture)
- onExit: n/a (terminal)

7) Canceled (Final)
- Meaning: Intent canceled, expired, or voided (no capture).
- onEnter:
  - SetVariableValue(\"status\", \"canceled\")
  - ReleaseInventory()
  - PublishWebhookEvent(\"payment_intent.canceled\", {reason})
  - SendCustomerEmail(\"payment_canceled\", {...})
- onExit: n/a (terminal)

Optional terminal state:
8) Failed (Final, optional)
- You can choose to roll failures back to requires_payment_method (to allow retries), mimicking Stripe.
- If you prefer a terminal failure distinct from canceled, add Failed:
  - onEnter:
    - SetVariableValue(\"status\", \"failed\")
    - PublishWebhookEvent(\"payment_intent.failed\", {error_code})
- In this design we primarily recycle failures to requires_payment_method if attempts remain, consistent with Stripe’s retriable posture.

## 8) Transitions and Guards

Below are the key transitions, with guard conditions using OperatorRegistry-style pseudocode.

A) RequiresPaymentMethod -> RequiresConfirmation
- Trigger: attach_payment_method
- Guards:
  - exists(payment_method_id) AND exists(payment_method_type)
- Actions (transition):
  - SetVariableValue(\"payment_method_id\", event.payment_method_id)
  - SetVariableValue(\"payment_method_type\", event.payment_method_type)
  - ValidatePaymentMethod()
  - PublishWebhookEvent(\"payment_intent.payment_method_updated\", {...})

B) RequiresConfirmation -> RequiresAction
- Trigger: confirm
- Guards:
  - equals(require_sca(payment_method_type, off_session), true) OR equals(payment_method_type, \"bank_redirect\")
- Actions:
  - SetVariableValue(\"next_action\", derivedNextAction(...))
  - SetVariableValue(\"requires_action_reason\", computed_reason)
  - PublishWebhookEvent(\"payment_intent.requires_action\", {...})

C) RequiresConfirmation -> Processing
- Trigger: confirm
- Guards:
  - equals(require_sca(payment_method_type, off_session), false)
  - not equals(risk_level, \"blocked\")
- Actions:
  - PrepareAuthorizationRequest()
  - CallPaymentGatewayAuthorize()
  - If async_method: SetVariableValue(\"async_method\", true)

D) RequiresConfirmation -> Canceled
- Trigger: risk_blocked
- Guards:
  - equals(risk_level, \"blocked\")
- Actions:
  - SetVariableValue(\"error_code\", \"risk_blocked\")
  - PublishWebhookEvent(\"payment_intent.canceled\", {reason: \"risk_blocked\"})

E) RequiresAction -> Processing
- Trigger: authentication_succeeded
- Guards:
  - equals(status, \"requires_action\")
- Actions:
  - Clear requires_action_reason, next_action
  - PrepareAuthorizationRequest()
  - CallPaymentGatewayAuthorize()

F) RequiresAction -> RequiresPaymentMethod
- Trigger: authentication_failed
- Guards:
  - equals(status, \"requires_action\")
- Actions:
  - SetVariableValue(\"error_code\", event.error_code or \"authentication_failed\")

G) RequiresAction -> Canceled
- Trigger: timeout({type: \"customer_action_timeout\"}) OR cancel
- Guards: always
- Actions:
  - SetVariableValue(\"error_code\", \"customer_action_timeout\" or \"canceled\")
  - PublishWebhookEvent(\"payment_intent.canceled\", {...})

H) Processing -> RequiresCapture
- Trigger: authorization_succeeded
- Guards:
  - equals(capture_method, \"manual\")
- Actions:
  - SetVariableValue(\"latest_charge_id\", event.charge_id)
  - SetVariableValue(\"amount_capturable\", event.amount_authorized)
  - PublishWebhookEvent(\"payment_intent.amount_capturable_updated\", {...})

I) Processing -> Succeeded
- Trigger: authorization_succeeded and capture_method = \"automatic\" and provider indicates captured
- Or: webhook_settled with provider_status = \"captured\" (async)
- Guards:
  - equals(capture_method, \"automatic\")
- Actions:
  - SetVariableValue(\"latest_charge_id\", event.charge_id)
  - SetVariableValue(\"amount_captured\", amount)
  - RecordLedgerEntry(\"payment_captured\", amount)

J) Processing -> RequiresPaymentMethod
- Trigger: authorization_failed
- Guards:
  - in(error_code, [\"card_declined\", \"insufficient_funds\", \"do_not_honor\"]) AND attempt_count < max_attempts
- Actions:
  - SetVariableValue(\"error_code\", event.error_code)
  - PublishWebhookEvent(\"payment_intent.payment_failed\", {...})

K) Processing -> Canceled
- Triggers:
  - timeout({type: \"auth_timeout\"})
  - cancel()
  - authorization_failed with non-recoverable error or no attempts left
- Guards: always
- Actions:
  - SetVariableValue(\"error_code\", derived)
  - PublishWebhookEvent(\"payment_intent.canceled\", {...})

L) RequiresCapture -> Succeeded
- Trigger: capture_succeeded (after capture({amount_to_capture}) request)
- Guards: amount_to_capture <= amount_capturable
- Actions:
  - SetVariableValue(\"amount_captured\", event.amount_captured)
  - RecordLedgerEntry(\"payment_captured\", event.amount_captured)

M) RequiresCapture -> Canceled
- Trigger: cancel (void authorization)
- Guards: always
- Actions:
  - PublishWebhookEvent(\"payment_intent.canceled\", {reason: \"voided\"})

N) Any Non-Final -> Canceled (Global Cancel)
- Trigger: cancel (merchant cancels)
- Guards: not in(status, [\"succeeded\", \"canceled\"])
- Actions:
  - ReleaseInventory()
  - PublishWebhookEvent(\"payment_intent.canceled\", {...})

O) Admin Override (Optional)
- Trigger: override_force_finalize({target: \"succeeded\"|\"canceled\"})
- Guards: role == \"admin\"
- Actions:
  - forceMoveAllStatesToFinalState(target)
  - PublishWebhookEvent(\"payment_intent.finalized_by_override\", {...})

Note on determinism: If your external action might produce variable outcomes, record those outcomes in variables so replays produce the same state.

## 9) Example Template Snippets (JSON-First)

Below are illustrative excerpts of a template you can create using Xavante’s factories. They are intentionally simplified; adapt field names to your project.

States (excerpt):
```json
{
  \"states\": [
    {
      \"name\": \"RequiresPaymentMethod\",
      \"onEnter\": [
        {\"type\": \"GenerateClientSecret\"},
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"status\", \"value\": \"requires_payment_method\"}},
        {\"type\": \"PublishWebhookEvent\", \"params\": {\"name\": \"payment_intent.created\"}}
      ],
      \"onExit\": [
        {\"type\": \"ValidatePaymentMethod\"},
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"error_code\", \"value\": null}},
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"next_action\", \"value\": null}},
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"requires_action_reason\", \"value\": null}}
      ]
    },
    {
      \"name\": \"RequiresConfirmation\",
      \"onEnter\": [
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"status\", \"value\": \"requires_confirmation\"}},
        {\"type\": \"PublishWebhookEvent\", \"params\": {\"name\": \"payment_intent.payment_method_attached\"}}
      ],
      \"onExit\": [
        {\"type\": \"PrepareAuthorizationRequest\"}
      ]
    },
    {
      \"name\": \"RequiresAction\",
      \"onEnter\": [
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"status\", \"value\": \"requires_action\"}},
        {\"type\": \"ScheduleTimeout\", \"params\": {\"name\": \"customer_action_timeout\", \"seconds\": 1800}},
        {\"type\": \"PublishWebhookEvent\", \"params\": {\"name\": \"payment_intent.requires_action\"}}
      ],
      \"onExit\": [
        {\"type\": \"ClearTimeout\", \"params\": {\"name\": \"customer_action_timeout\"}}
      ]
    },
    {
      \"name\": \"Processing\",
      \"onEnter\": [
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"status\", \"value\": \"processing\"}},
        {\"type\": \"IncrementAttempt\"},
        {\"type\": \"CallPaymentGatewayAuthorize\"},
        {\"type\": \"ScheduleTimeout\", \"params\": {\"name\": \"auth_timeout\", \"seconds\": 900}},
        {\"type\": \"PublishWebhookEvent\", \"params\": {\"name\": \"payment_intent.processing\"}}
      ],
      \"onExit\": [
        {\"type\": \"ClearTimeout\", \"params\": {\"name\": \"auth_timeout\"}}
      ]
    },
    {
      \"name\": \"RequiresCapture\",
      \"onEnter\": [
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"status\", \"value\": \"requires_capture\"}},
        {\"type\": \"PublishWebhookEvent\", \"params\": {\"name\": \"payment_intent.requires_capture\"}}
      ]
    },
    {
      \"name\": \"Succeeded\",
      \"final\": true,
      \"onEnter\": [
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"status\", \"value\": \"succeeded\"}},
        {\"type\": \"RecordLedgerEntry\", \"params\": {\"type\": \"payment_captured\", \"amountVar\": \"amount_captured\"}},
        {\"type\": \"PublishWebhookEvent\", \"params\": {\"name\": \"payment_intent.succeeded\"}}
      ]
    },
    {
      \"name\": \"Canceled\",
      \"final\": true,
      \"onEnter\": [
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"status\", \"value\": \"canceled\"}},
        {\"type\": \"ReleaseInventory\"},
        {\"type\": \"PublishWebhookEvent\", \"params\": {\"name\": \"payment_intent.canceled\"}}
      ]
    }
  ]
}
```

Transitions (excerpt):
```json
{
  \"transitions\": [
    {
      \"name\": \"AttachPaymentMethod\",
      \"from\": \"RequiresPaymentMethod\",
      \"to\": \"RequiresConfirmation\",
      \"event\": \"attach_payment_method\",
      \"guard\": {\"op\": \"and\", \"args\": [
        {\"op\": \"exists\", \"args\": [\"event.payment_method_id\"]},
        {\"op\": \"exists\", \"args\": [\"event.payment_method_type\"]}
      ]},
      \"actions\": [
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"payment_method_id\", \"value\": \"event.payment_method_id\"}},
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"payment_method_type\", \"value\": \"event.payment_method_type\"}},
        {\"type\": \"PublishWebhookEvent\", \"params\": {\"name\": \"payment_intent.payment_method_updated\"}}
      ]
    },
    {
      \"name\": \"ConfirmNeedsAction\",
      \"from\": \"RequiresConfirmation\",
      \"to\": \"RequiresAction\",
      \"event\": \"confirm\",
      \"guard\": {\"op\": \"or\", \"args\": [
        {\"op\": \"equals\", \"args\": [\"computed.require_sca\", true]},
        {\"op\": \"equals\", \"args\": [\"variables.payment_method_type\", \"bank_redirect\"]}
      ]},
      \"actions\": [
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"next_action\", \"value\": \"computed.next_action\"}},
        {\"type\": \"SetVariableValue\", \"params\": {\"name\": \"requires_action_reason\", \"value\": \"computed.reason\"}}
      ]
    },
    {
      \"name\": \"ConfirmToProcessing\",
      \"from\": \"RequiresConfirmation\",
      \"to\": \"Processing\",
      \"event\": \"confirm\",
      \"guard\": {\"op\": \"and\", \"args\": [
        {\"op\": \"equals\", \"args\": [\"computed.require_sca\", false]},
        {\"op\": \"not_equals\", \"args\": [\"variables.risk_level\", \"blocked\"]}
      ]}
    },
    {
      \"name\": \"AuthSuccessManualCapture\",
      \"from\": \"Processing\",
      \"to\": \"RequiresCapture\",
      \"event\": \"authorization_succeeded\",
      \"guard\": {\"op# Xavante Payment Intent Workflow (Stripe-Compatible Design)

## 1) Project Overview

This document describes how to implement a Stripe-like Payment Intents mechanism using Xavante, a PHP workflow engine designed around deterministic state machines, JSON-first serialization, and a comprehensive action system. The goal is to model the lifecycle of a payment—from creation to success or cancellation—while supporting 3D Secure authentication, asynchronous payment methods, manual capture, retries, and compensating actions.

### Objectives

- Mirror Stripe’s Payment Intents behavior and status transitions.
- Provide a deterministic, testable state machine using Xavante’s workflows, events, and actions.
- Support both synchronous and asynchronous payment methods, SCA flows, and manual capture.
- Offer clear extension points for new payment methods and compensations.

### How Stripe Payment Intents Work (Primer)

Stripe’s Payment Intent represents the process of collecting a payment. It tracks the current status of the attempt and drives required actions. Core statuses include:

- requires_payment_method: A payment method is needed or the previous attempt failed.
- requires_confirmation: A payment method is present; you must confirm to start authorization and capture.
- requires_action: Customer intervention is needed (e.g., 3D Secure).
- processing: The payment is being processed asynchronously (e.g., bank transfer).
- requires_capture: Authorization succeeded and is held; you must capture funds (for capture_method=manual).
- succeeded: The payment completed successfully.
- canceled: The payment intent was canceled.

Key operations:

- Create a Payment Intent (amount, currency, capture and confirmation settings).
- Attach or update a payment method.
- Confirm a Payment Intent (start the processing).
- If manual capture, capture when ready.
- Cancel under certain conditions.
- Handle next_action for SCA or asynchronous methods.
- React to webhooks for asynchronous completion.

## 2) Xavante Overview (Quick Intro)

Xavante is a deterministic, JSON-first workflow engine with:

- Immutable templates and stateful runtime instances (dual model).
- Deterministic state machines with guards and conditions.
- Actions executed on state entry, exit, and on transitions.
- An operator library (equals, not, in, greater_than, is_set, etc.).
- Factories for safe creation (WorkflowFactory, StateFactory, TransitionFactory).
- Processor for executing workflows (e.g., Processor::processEvent).
- Special actions:
  - forceMoveAllStatesToFinalState: Move instance to specified final state (administrative override).
  - SetVariableValue: Set workflow instance variables (useful for feature flags and runtime data).
  - triggerEvent: Emit an event that can drive transitions and actions.

Xavante is ideal for event-driven, saga-style workflows with compensations invoked via events and parallel actions.

## 3) Domain Mapping

### Payment Intent to Xavante Concepts

- Workflow instance = Payment Intent.
- Variables = Payment details, status, flags (amount, currency, payment_method, capture_method, etc.).
- States = Stripe-like statuses.
- Events = API and webhook triggers (create, attach payment method, confirm, capture, cancel, auth results, async funds availability).
- Actions = REST calls to gateway/PSP, setting variables, emitting webhooks, scheduling timeouts, logging, compensations.

## 4) Variables (Per-Instance)

Define the minimal set, expand as needed:

- Core payment data
  - amount
  - currency
  - customer_id
  - payment_method_id
  - payment_method_type (e.g., card, bank_transfer)
  - capture_method (\"automatic\" or \"manual\")
  - confirmation_method (\"automatic\" or \"manual\")
  - livemode (boolean)
  - metadata (object)
- Status and progress
  - status (mirrors current state name)
  - latest_charge_id
  - attempt_count
  - max_attempts (default: 3)
  - next_action_type (e.g., \"3ds_redirect\", \"redirect_url\", \"display_bank_instructions\")
  - next_action_payload (structured data for client-side handling)
  - last_error_code
  - last_error_message
- Async/timeout and scheduling
  - async_timeout_at (UTC timestamp)
  - cancellation_reason (e.g., \"requested_by_customer\", \"timeout\", \"abandoned\")
- Capture specifics
  - captured_amount (default: 0)
  - capture_remaining (derived: amount - captured_amount)
  - allow_partial_capture (boolean; feature flag)
- Feature flags / controls (SetVariableValue friendly)
  - auto_confirm_on_attach (boolean)
  - auto_retry_enabled (boolean)
  - require_3ds (boolean or \"auto\")
  - manual_capture_supported (boolean by payment_method_type)
  - processing_status (\"none\", \"pending\", \"succeeded\", \"failed\")
  - idempotency_key (for deduplication)
- Audit / idempotency
  - created_at
  - updated_at
  - last_event_id

Note: Use SetVariableValue consistently to modify these, and prefer guards/conditions from OperatorRegistry to maintain determinism.

## 5) Actions Catalog

Use Xavante’s action system to orchestrate internal logic and REST calls. Common actions:

- SetVariableValue: set or update variables.
- triggerEvent: emit external or internal events to drive further transitions.
- Call REST endpoints:
  - InvokeGatewayAuthorize: POST to gateway/PSP to authorize or confirm payment.
  - InvokeGatewayCapture: POST to capture funds.
  - InvokeGatewayCancel: POST to void/cancel authorization or revoke payment attempt where applicable.
  - PublishWebhook: POST internal webhooks (e.g., payment_intent.succeeded).
  - ScheduleTimeout: POST to a scheduler to emit timeout event at a future time.
  - RecordAuditLog: POST or log structured events for compliance.
- Derived, composite actions:
  - ComputeNextAction: Inspect gateway response and set next_action_type and payload.
  - IncrementAttemptCount: bump attempt_count.
  - ClearNextAction: set next_action_type to null and payload to null.
  - SetStatusFromState: write current state name to status.

Note: Each of these is either a SetVariableValue, a triggerEvent, or an HTTP call to a service your platform controls.

## 6) Events Catalog

External API or system-triggered events. Your adapter layer should validate payloads and then call Processor::processEvent.

- create_intent(payload)
  - Initializes variables, enters initial state.
- attach_payment_method(payment_method_id, type, options?)
  - Associates a PM and possibly transitions to requires_confirmation.
- confirm_intent(options?)
  - Starts authorization/confirmation flow.
- authentication_succeeded(context)
  - From a 3DS or similar challenge result.
- authentication_failed(error)
  - Failed SCA; often implies requires_payment_method.
- funds_pending(context)
  - Async PM indicates pending processing.
- funds_available(context)
  - Async PM indicates funds are available; usually leads to success.
- funds_failed(error)
  - Async PM failed; implies retry or requires payment method.
- capture(capture_amount?)
  - For manual capture flows.
- cancel(reason)
  - Cancel the payment intent when eligible.
- timeout_elapsed(kind)
  - Scheduler triggers when async or authentication timeouts expire.
- external_cancel(reason)
  - Backoffice or partner PSP cancellation.
- admin_force_finalize(final_state, reason)
  - Uses forceMoveAllStatesToFinalState to finalize administratively.

Webhook emission (e.g., payment_intent.succeeded) is done via actions (PublishWebhook), not by events above.

## 7) States and Transitions

Below is the designed state machine. Each state lists onEnter/onExit actions and transitions with conditions and actions.

Note: State names intentionally mirror Stripe statuses to aid parity.

### 7.1 requires_payment_method (initial)

- Purpose: Payment method needed or last attempt failed.
- onEnter:
  - SetStatusFromState
  - ClearNextAction
  - PublishWebhook(\"payment_intent.requires_payment_method\")
- onExit:
  - RecordAuditLog(\"pm_attached_or_confirm_attempted\")

Transitions:

1) Event: attach_payment_method
   - Guards:
     - equals(payment_method_id is_set, true)
   - Actions:
     - SetVariableValue(payment_method_id, payload.payment_method_id)
     - SetVariableValue(payment_method_type, payload.type)
     - SetVariableValue(updated_at, now)
   - To: requires_confirmation

2) Event: confirm_intent (for clients that create + confirm without waiting)
   - Guards:
     - equals(payment_method_id is_set, true)
   - Actions: None special (handoff to authorization)
   - To: requires_confirmation

3) Event: cancel
   - Actions:
     - SetVariableValue(cancellation_reason, payload.reason or \"requested_by_customer\")
   - To: canceled

### 7.2 requires_confirmation

- Purpose: PM is present; waiting for confirmation.
- onEnter:
  - SetStatusFromState
  - PublishWebhook(\"payment_intent.requires_confirmation\")
- onExit:
  - RecordAuditLog(\"confirming_authorization\")

Transitions:

1) Event: confirm_intent
   - Guards:
     - equals(payment_method_id is_set, true)
   - Actions:
     - IncrementAttemptCount
     - InvokeGatewayAuthorize (sends amount, currency, PM, capture_method, etc.)
     - ComputeNextAction (read gateway response; write next_action_type and payload)
     - SetVariableValue(latest_charge_id, response.charge_id)
     - If next_action_type == \"3ds_redirect\" or \"use_sdk\":
       - triggerEvent(\"auth_requires_action\")
     - Else if response.async == true:
       - triggerEvent(\"auth_processing\")
     - Else if response.authorized == true and capture_method == \"manual\":
       - triggerEvent(\"auth_requires_capture\")
     - Else if response.captured == true or authorized and capture_method == \"automatic\":
       - triggerEvent(\"auth_completed\")
     - Else:
       - triggerEvent(\"auth_failed\")
   - To: authorizing (internal working state)

2) Event: attach_payment_method (update or replacement before confirmation)
   - Actions:
     - SetVariableValue(payment_method_id, payload.payment_method_id)
     - SetVariableValue(payment_method_type, payload.type)
   - To: requires_confirmation (self-loop with updates)

3) Event: cancel
   - Actions:
     - SetVariableValue(cancellation_reason, payload.reason or \"requested_by_customer\")
   - To: canceled

### 7.3 authorizing

- Purpose: Transient/operational state to normalize gateway responses to the proper next state. You do not expose this externally; it reflects in logs and status if desired, but typically transitions immediately after the gateway call in requires_confirmation.
- onEnter:
  - SetStatusFromState
- onExit:
  - RecordAuditLog(\"authorization_outcome_classified\")

Transitions (triggered by internal events from ComputeNextAction logic):

1) Event: auth_requires_action
   - Actions:
     - PublishWebhook(\"payment_intent.requires_action\", { next_action_type, next_action_payload })
     - ScheduleTimeout(async_timeout_at) if needed
   - To: requires_action

2) Event: auth_processing
   - Actions:
     - SetVariableValue(processing_status, \"pending\")
     - PublishWebhook(\"payment_intent.processing\")
     - ScheduleTimeout(async_timeout_at)
   - To: processing

3) Event: auth_requires_capture
   - Guards:
     - equals(capture_method, \"manual\")
   - Actions:
     - PublishWebhook(\"payment_intent.requires_capture\")
   - To: requires_capture

4) Event: auth_completed
   - Actions:
     - SetVariableValue(captured_amount, amount) if captured
     - PublishWebhook(\"payment_intent.succeeded\")
   - To: succeeded

5) Event: auth_failed
   - Actions:
     - SetVariableValue(last_error_code, response.error_code)
     - SetVariableValue(last_error_message, response.error_message)
     - If auto_retry_enabled and attempt_count < max_attempts:
       - triggerEvent(\"auto_retry_backoff\") else no-op
   - To: requires_payment_method

6) Event: auto_retry_backoff
   - Actions:
     - RecordAuditLog(\"backoff_retry_scheduled\")
     - (Your scheduler emits a follow-up event attach_payment_method or confirm_intent with the same PM after delay; or you can rely on client to supply a new PM.)
   - To: requires_payment_method

### 7.4 requires_action

- Purpose: Customer must complete an action (e.g., SCA).
- onEnter:
  - SetStatusFromState
  - PublishWebhook(\"payment_intent.requires_action\", { next_action_type, next_action_payload })
- onExit:
  - ClearNextAction

Transitions:

1) Event: authentication_succeeded
   - Actions:
     - RecordAuditLog(\"auth_challenge_passed\")
     - triggerEvent(\"reconfirm_after_auth\")
   - To: authorizing (or requires_confirmation if you re-confirm at that state)

2) Event: reconfirm_after_auth
   - Actions:
     - InvokeGatewayAuthorize (idempotent; gateway should treat as confirm/complete)
     - ComputeNextAction (repeat same decision logic)
     - Then trigger appropriate event: auth_processing, auth_requires_capture, auth_completed, auth_failed
   - To: authorizing

3) Event: authentication_failed
   - Actions:
     - SetVariableValue(last_error_code, \"authentication_failed\")
     - SetVariableValue(last_error_message, payload.error or \"SCA challenge failed\")
   - To: requires_payment_method

4) Event: cancel
   - Actions:
     - SetVariableValue(cancellation_reason, payload.reason or \"abandoned\")
     - InvokeGatewayCancel where applicable
   - To: canceled

5) Event: timeout_elapsed(\"authentication\")
   - Actions:
     - SetVariableValue(cancellation_reason, \"timeout\")
     - InvokeGatewayCancel if authorization was pending challenge
   - To: canceled

### 7.5 processing

- Purpose: Wait for asynchronous payment completion (bank transfer, voucher, e-money).
- onEnter:
  - SetStatusFromState
  - PublishWebhook(\"payment_intent.processing\")
  - ScheduleTimeout(async_timeout_at)
- onExit:
  - RecordAuditLog(\"async_completed_or_failed\")

Transitions:

1) Event: funds_available
   - Actions:
     - If capture_method == \"automatic\":
       - SetVariableValue(captured_amount, amount)
       - PublishWebhook(\"payment_intent.succeeded\")
     - Else if capture_method == \"manual\" and this method supports manual capture:
       - triggerEvent(\"auth_requires_capture\")
   - To: succeeded (automatic) or requires_capture (manual)

2) Event: funds_pending
   - Actions:
     - SetVariableValue(processing_status, \"pending\")
     - Maybe reschedule timeout
   - To: processing (self-loop)

3) Event: funds_failed
   - Actions:
     - SetVariableValue(last_error_code, payload.code)
     - SetVariableValue(last_error_message, payload.message)
   - To: requires_payment_method

4) Event: cancel
   - Guards:
     - Payment method allows cancel while pending
   - Actions:
     - SetVariableValue(cancellation_reason, payload.reason or \"requested_by_customer\")
     - InvokeGatewayCancel
   - To: canceled

5) Event: timeout_elapsed(\"processing\")
   - Actions:
     - SetVariableValue(cancellation_reason, \"timeout\")
     - InvokeGatewayCancel where applicable
   - To: canceled

### 7.6 requires_capture

- Purpose: Authorization successful, funds held; waiting for explicit capture.
- onEnter:
  - SetStatusFromState
  - PublishWebhook(\"payment_intent.requires_capture\")
- onExit:
  - RecordAuditLog(\"capturing_or_canceled\")

Transitions:

1) Event: capture(capture_amount?)
   - Guards:
     - greater_than(capture_amount, 0)
     - less_or_equal(capture_amount, amount)
   - Actions:
     - InvokeGatewayCapture(capture_amount or amount)
     - SetVariableValue(captured_amount, capture_amount or amount)
     - If allow_partial_capture and capture_amount < amount:
       - SetVariableValue(capture_remaining, amount - capture_amount)
   - To: capturing

2) Event: cancel
   - Actions:
     - SetVariableValue(cancellation_reason, payload.reason or \"requested_by_customer\")
     - InvokeGatewayCancel (void the authorization)
   - To: canceled

3) Event: timeout_elapsed(\"requires_capture\")
   - Actions:
     - SetVariableValue(cancellation_reason, \"timeout\")
     - InvokeGatewayCancel
   - To: canceled

### 7.7 capturing

- Purpose: Transient state while capture is executed/settled.
- onEnter:
  - SetStatusFromState
- onExit:
  - RecordAuditLog(\"capture_outcome\")

Transitions:

1) Event: capture_succeeded
   - Actions:
     - PublishWebhook(\"payment_intent.succeeded\")
   - To: succeeded

2) Event: capture_failed
   - Guards:
     - If error is retryable:
       - To: requires_capture
     - Else:
       - To: canceled (or requires_payment_method depending on business decision)
   - Actions:
     - SetVariableValue(last_error_code, payload.code)
     - SetVariableValue(last_error_message, payload.message)
     - Optionally InvokeGatewayCancel if the authorization must be voided

### 7.8 succeeded (final)

- Purpose: Payment completed successfully.
- onEnter:
  - SetStatusFromState
  - PublishWebhook(\"payment_intent.succeeded\") if not already sent
  - RecordAuditLog(\"final_success\")
- onExit: None
- No outgoing transitions.

### 7.9 canceled (final)

- Purpose: Payment intent canceled.
- onEnter:
  - SetStatusFromState
  - PublishWebhook(\"payment_intent.canceled\", { reason: cancellation_reason })
  - RecordAuditLog(\"final_canceled\")
- onExit: None
- No outgoing transitions.

## 8) External Events That Drive the Workflow

- Client/API:
  - create_intent
  - attach_payment_method
  - confirm_intent
  - capture
  - cancel
- Customer/Provider interactions:
  - authentication_succeeded
  - authentication_failed
  - funds_pending
  - funds_available
  - funds_failed
- Platform:
  - timeout_elapsed
  - external_cancel
  - admin_force_finalize

These events should be validated and then passed to Xavante’s Processor::processEvent for the correct workflow instance.

## 9) Flow Walkthroughs

### A) Happy Path (Card, Automatic Capture, No SCA)

1) create_intent → enters requires_payment_method.
2) attach_payment_method → transitions to requires_confirmation.
3) confirm_intent → transitions to authorizing via requires_confirmation actions:
   - InvokeGatewayAuthorize returns captured=true (no SCA needed).
4) authorizing → auth_completed → succeeded.
5) Webhook payment_intent.succeeded is emitted.

End state: succeeded.

### B) Card Requiring 3DS (SCA), Automatic Capture

1) create_intent → requires_payment_method.
2) attach_payment_method → requires_confirmation.
3) confirm_intent → InvokeGatewayAuthorize indicates requires_action with next_action payload (3DS).
4) authorizing → auth_requires_action → requires_action; publish next_action to client.
5) Customer completes 3DS → authentication_succeeded.
6) requires_action → reconfirm_after_auth (InvokeGatewayAuthorize again).
7) authorizing decides:
   - If captured successfully → auth_completed → succeeded; webhook emitted.
   - If still async processing (rare for cards) → processing → funds_available → succeeded.

End state: succeeded.

If customer fails 3DS:
- authentication_failed → requires_payment_method (retry with a new PM).

If timeout:
- timeout_elapsed → canceled with reason timeout.

### C) Manual Capture (Card Auth then Capture)

1) create_intent (capture_method=\"manual\") → requires_payment_method.
2) attach_payment_method → requires_confirmation.
3) confirm_intent → InvokeGatewayAuthorize returns authorized=true.
4) authorizing → auth_requires_capture → requires_capture.
5) Later, capture(capture_amount) → capturing:
   - capture_succeeded → succeeded (captured_amount set).
   - capture_failed → requires_capture (retry) or canceled (non-retryable).
6) If capture is not called in time:
   - timeout_elapsed → canceled (void authorization).

Partial capture:
- If allow_partial_capture and capture_amount < amount:
  - captured_amount updated; intent still typically finalizes as succeeded (releasing the remainder) depending on gateway capabilities; you can model success and finalize here for simplicity.

### D) Asynchronous Payment Method (e.g., bank transfer)

1) create_intent → requires_payment_method.
2) attach_payment_method (type=bank_transfer) → requires_confirmation.
3) confirm_intent → InvokeGatewayAuthorize returns async=true with bank instructions in next_action.
4) authorizing → auth_processing → processing; publish instructions to the client.
5) Later, provider emits funds_pending → processing (self-loop) or funds_available → succeeded (automatic capture) or requires_capture (if your method supports manual capture).
6) If funds_failed: → requires_payment_method (retry).
7) If timeout_elapsed: → canceled.

### E) Failure and Retry

- If authorization fails: authorizing → auth_failed → requires_payment_method.
- Client attaches a new PM → requires_confirmation → confirm_intent again.
- Use attempt_count and max_attempts to limit retries. If auto_retry_enabled is true, you can schedule re-attempts or propose alternative methods.

### F) Cancellation

- From requires_payment_method, requires_confirmation, requires_action, processing, or requires_capture:
  - cancel → canceled. Where needed, InvokeGatewayCancel is executed (void auth, stop async).
- After succeeded, cancel is not allowed (you would issue a refund with a separate workflow).

### G) Administrative Force Finalization

- admin_force_finalize(\"canceled\" or \"succeeded\"):
  - Executes forceMoveAllStatesToFinalState with a reason.
  - This is guarded and audited; should be rarely used and only by administrators.

## 10) Conditions, Guards, and Operators (Examples)

Use OperatorRegistry in transitions:

- equals(status, \"requires_payment_method\")
- is_set(payment_method_id)
- equals(capture_method, \"manual\")
- greater_than(capture_amount, 0)
- less_or_equal(capture_amount, amount)
- in(payment_method_type, [\"card\", \"bank_transfer\"])
- equals(auto_retry_enabled, true)
- greater_than(max_attempts, attempt_count)

These enable deterministic branching across transitions.

## 11) Example: Workflow Template (JSON-First Snippet)

Below is an illustrative (truncated) JSON-like template showing a few states and transitions. Adjust to your actual schema.

```json
{
  \"id\": \"payment_intent_v1\",
  \"name\": \"Payment Intent\",
  \"version\": \"1.0.0\",
  \"initialState\": \"requires_payment_method\",
  \"variables\": {
    \"amount\": null,
    \"currency\": null,
    \"payment_method_id\": null,
    \"payment_method_type\": null,
    \"capture_method\": \"automatic\",
    \"confirmation_method\": \"automatic\",
    \"attempt_count\": 0,
    \"max_attempts\": 3,
    \"next_action_type\": null,
    \"next_action_payload\": null,
    \"latest_charge_id\": null,
    \"cancellation_reason\": null,
    \"captured_amount\": 0,
    \"auto_retry_enabled\": true,
    \"allow_partial_capture\": false
  },
  \"states\": [
    {
      \"name\": \"requires_payment_method\",
      \"onEnter\": [
        { \"type\": \"SetVariableValue\", \"key\": \"status\", \"value\": \"requires_payment_method\" },
        { \"type\": \"triggerEvent\", \"event\": \"emit_webhook\", \"payload\": { \"type\": \"payment_intent.requires_payment_method\" } }
      ],
      \"transitions\": [
        {
          \"event\": \"attach_payment_method\",
          \"guards\": [{ \"operator\": \"is_set\", \"left\": \"payload.payment_method_id\" }],
          \"actions\": [
            { \"type\": \"SetVariableValue\", \"key\": \"payment_method_id\", \"value\": \"{{payload.payment_method_id}}\" },
            { \"type\": \"SetVariableValue\", \"key\": \"payment_method_type\", \"value\": \"{{payload.type}}\" }
          ],
          \"to\": \"requires_confirmation\"
        },
        {
          \"event\": \"confirm_intent\",
          \"guards\": [{ \"operator\": \"is_set\", \"left\": \"payment_method_id\" }],
          \"to\": \"requires_confirmation\"
        },
        {
          \"event\": \"cancel\",
          \"actions\": [
            { \"type\": \"SetVariableValue\", \"key\": \"cancellation_reason\", \"value\": \"{{payload.reason || 'requested_by_customer'}}\" }
          ],
          \"to\": \"canceled\"
        }
      ]
    },
    {
      \"name\": \"requires_confirmation\",
      \"onEnter\": [
        { \"type\": \"SetVariableValue\", \"key\": \"status\", \"value\": \"requires_confirmation\" },
        { \"type\": \"triggerEvent\", \"event\": \"emit_webhook\", \"payload\": { \"type\": \"payment_intent.requires_confirmation\" } }
      ],
      \"transitions\": [
        {
          \"event\": \"confirm_intent\",
          \"guards\": [{ \"operator\": \"is_set\", \"left\": \"payment_method_id\" }],
          \"actions\": [
            { \"type\": \"SetVariableValue\", \"key\": \"attempt_count\", \"value\": \"{{variables.attempt_count + 1}}\" },
            { \"type\": \"InvokeGatewayAuthorize\" },
            { \"type\": \"ComputeNextAction\" },
            { \"type\": \"SetVariableValue\", \"key\": \"latest_charge_id\", \"value\": \"{{context.charge_id}}\" },
            { \"type\": \"triggerEvent\", \"event\": \"route_post_authorize\" }
          ],
          \"to\": \"authorizing\"
        },
        {
          \"event\": \"attach_payment_method\",
          \"actions\": [
            { \"type\": \"SetVariableValue\", \"key\": \"payment_method_id\", \"value\": \"{{payload.payment_method_id}}\" },
            { \"type\": \"SetVariableValue\", \"key\": \"payment_method_type\", \"value\": \"{{payload.type}}\" }
          ],
          \"to\": \"requires_confirmation\"
        },
        {
          \"event\": \"cancel\",
          \"actions\": [
            { \"type\": \"SetVariableValue\", \"key\": \"cancellation_reason\", \"value\": \"{{payload.reason || 'requested_by_customer'}}\" }
          ],
          \"to\": \"canceled\"
        }
      ]
    },
    {
      \"name\": \"authorizing\",
      \"onEnter\": [{ \"type\": \"SetVariableValue\", \"key\": \"status\", \"value\": \"authorizing\" }],
      \"transitions\": [
        { \"event\": \"auth_requires_action\", \"to\": \"requires_action\" },
        { \"event\": \"auth_processing\", \"to\": \"processing\" },
        { \"event\": \"auth_requires_capture\", \"to\": \"requires_capture\" },
        { \"event\": \"auth_completed\", \"to\": \"succeeded\" },
        { \"event\": \"auth_failed\", \"to\": \"requires_payment_method\" }
      ]
    }
  ],
  \"finalStates\": [\"succeeded\", \"canceled\"]
}
```

Your real template would fully define all states and transitions described in this document, and include the action definitions (e.g., HTTP endpoints, payload shapes, retry policies).

## 12) Webhook Emission

Emit webhooks via actions when entering or leaving key states:

- payment_intent.created
- payment_intent.requires_payment_method
- payment_intent.requires_confirmation
- payment_intent.requires_action
- payment_intent.processing
- payment_intent.requires_capture
- payment_intent.succeeded
- payment_intent.canceled
- payment_intent.payment_failed (optional on auth/capture failures)

Structure: PublishWebhook includes the intent id, amount, currency, status, next_action, latest_charge_id, timestamps, and metadata.

## 13) Idempotency and Concurrency

- Maintain an idempotency_key on client-triggered events (confirm, capture, cancel) to avoid duplicate processing.
- Use guards to ignore duplicate events with the same key and same state progression.
- For gateway calls, include idempotency keys and store charge IDs to dedupe results.

## 14) Compensation Strategy (Saga Style)

- Cancel/void authorization when moving to canceled from requires_capture or after failed capture.
- On processing timeouts for async methods, cancel at provider if supported.
- For failed confirmation with funds held inadvertently (edge cases), issue provider cancel immediately.
- Administrative force finalize is available, but always audit and restrict permissions.

## 15) Testing Strategy

- Unit test each state’s transitions and guard logic using deterministic inputs.
- Property-based tests for authorization outcomes (success, requires_action, async, failure).
- Replay tests: serialize instance state across transitions to ensure determinism and re-entrancy.
- Time-based tests: simulate timeout_elapsed events to verify cancel behavior.
- Gateway adapter tests: stub InvokeGatewayAuthorize/Capture/Cancel with predictable responses and error codes.
- Webhook contract tests: verify payload shapes across all emitted events.

## 16) Extensibility and Versioning

- Version your workflow template (e.g., payment_intent_v1, payment_intent_v1_1) when adding:
  - New payment methods (e.g., wallets, vouchers).
  - Partial multi-capture support changes.
  - Different SCA rules per region.
- Keep transitions deterministic; add new branches with explicit guards.
- Use feature flags (SetVariableValue) to roll out capabilities safely.

## 17) Operational Notes

- Observability: Log every transition with RecordAuditLog including event, prior state, next state, and variables diff (excluding sensitive data).
- Timeouts: Centralize ScheduleTimeout configuration per payment method type and region.
- Retries: Define retry policies at action level (e.g., exponential backoff on network errors when calling gateway).
- Security: Never log PAN or sensitive card data. Store tokens/PM ids only.

## 18) Summary

This design maps Stripe’s Payment Intent lifecycle into a robust Xavante workflow:

- States reflect Stripe statuses for parity and clarity.
- Transitions are event-driven with deterministic guards.
- Actions encapsulate gateway calls, variable updates, webhooks, and scheduling.
- Special Xavante actions (SetVariableValue, triggerEvent, forceMoveAllStatesToFinalState) are leveraged for flags, orchestration, and administrative recovery.
- The workflow supports happy paths (automatic capture), SCA flows (requires_action), manual capture, asynchronous payment methods, retries, cancellations, and timeouts—providing a comprehensive and extensible core for your payment platform.

If you want, I can deliver:
- A complete JSON workflow template covering all states and transitions.
- PHP scaffolding using WorkflowFactory, StateFactory, TransitionFactory, and Processor.
- Mock gateway adapter and test suite to validate the end-to-end flows.
