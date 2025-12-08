# README — Enhanced Payment Intent Workflow v2

## Story: The multi-processor payment that saved Black Friday
Marcus, Head of Payments at a major e-commerce platform, watched their primary payment processor fail during Black Friday's peak traffic. Thanks to their new payment workflow system, transactions automatically failed over to backup processors within seconds. The system handled partial refunds for interrupted payments, maintained perfect audit trails, and even processed delayed settlements when processors came back online. What could have been a million-dollar disaster became a seamless customer experience.

## Solution: Advanced payment orchestration with intelligent fallbacks
This enhanced workflow introduces sophisticated payment processing capabilities:
- **Multi-processor Support** → Automatic failover between payment providers
- **Advanced Risk Assessment** → ML-powered fraud detection with contextual scoring
- **Intelligent Retry Logic** → Exponential backoff with processor-specific strategies
- **Partial Operations** → Support for partial captures, refunds, and settlements
- **Compensation Patterns** → Automatic rollback and recovery mechanisms
- **Real-time Monitoring** → Complete observability with performance metrics

## When to use it
- High-volume payment systems requiring 99.99% uptime
- Multi-tenant platforms with diverse payment requirements
- International commerce with regional processor preferences
- Marketplace platforms with complex settlement patterns
- Any system requiring sophisticated error recovery and compensation

## Enhanced Architecture

### Multi-Processor Integration
The v2 workflow supports multiple payment processor integrations with intelligent routing:

**Primary Processors**:
- Stripe (North America, Europe)
- Adyen (Global, specialized in international markets)
- PayPal (Consumer payments, wallet integration)

**Fallback Processors**:
- Square (Backup for North America)
- Braintree (PayPal ecosystem fallback)
- Local processors by region

**Processor Selection Logic**:
```json
{
  "routing_rules": {
    "primary": {
      "condition": "amount < 10000 AND region IN ['US', 'CA', 'EU']",
      "processor": "stripe"
    },
    "high_value": {
      "condition": "amount >= 10000 OR currency NOT IN ['USD', 'EUR']",
      "processor": "adyen"
    },
    "fallback": {
      "condition": "primary_failed == true",
      "processor": "square"
    }
  }
}
```

### Enhanced Risk Assessment
Advanced machine learning integration for comprehensive fraud detection:

**Risk Factors Analyzed**:
- Transaction velocity and patterns
- Device fingerprinting and geolocation
- Customer behavior analysis
- Merchant category risk profiling
- Real-time blacklist checking
- Regulatory compliance screening

**ML Model Integration**:
```http
POST /api/ml/risk/evaluate
{
  "transaction": {
    "amount": 25000,
    "currency": "USD",
    "merchant_id": "mch_12345",
    "customer_id": "cus_67890"
  },
  "context": {
    "ip_address": "203.0.113.1",
    "user_agent": "Mozilla/5.0...",
    "device_fingerprint": "fp_abc123xyz",
    "session_data": {...}
  },
  "historical_data": {
    "customer_transactions": 47,
    "merchant_volume_30d": 1250000,
    "dispute_rate": 0.008
  }
}
```

## Enhanced State Model

### States with Advanced Actions

**Created** (Enhanced Initialization)
- Entry: `initializePaymentMetadata()`, `generateIdempotencyKey()`, `selectOptimalProcessor()`
- Context: Enhanced metadata collection, processor pre-selection based on routing rules

**RequiresPaymentMethod** (Smart Payment Method Handling)
- Entry: `validatePaymentMethodReqs()`, `scheduleTimeout('pm_timeout', 7d)`, `suggestOptimalPaymentMethods()`
- Exit: `validatePaymentMethod()`, `enrichPaymentMethodData()`
- Context: Payment method optimization based on customer preferences and success rates

**RequiresConfirmation** (Enhanced Risk Integration)
- Entry: `initiateRiskAssessment()`, `createAuthorizationHold()`, `prepareProcessorContext()`
- Exit: `releaseTemporaryResources()`, `cacheRiskResults()`
- Context: Comprehensive risk evaluation with real-time decisioning

**RiskAssessment** (New State - ML-Powered)
- Entry: `callRiskAPI()`, `analyzeTransactionPattern()`, `checkFraudDatabase()`, `evaluateRegulatory()`
- Exit: `cacheRiskScore()`, `updateCustomerProfile()`
- Context: Advanced ML-based risk scoring with regulatory compliance checks

**RequiresAction** (Enhanced Customer Interaction)
- Entry: `generateChallengeToken()`, `prepareCustomerInterface()`, `scheduleActionTimeout()`
- Exit: `validateCustomerResponse()`, `updateAuthenticationHistory()`
- Context: Intelligent challenge selection and customer experience optimization

**Processing** (Multi-Processor Support)
- Entry: `selectProcessor()`, `lockPaymentVariables()`, `executeWithFallback()`
- Exit: `updateAuditTrail()`, `unlockPaymentVariables()`
- Context: Intelligent processor selection with automatic failover capabilities

**SettlementRetry** (New State - Advanced Recovery)
- Entry: `selectFallbackProcessor()`, `recalculateAmounts()`, `scheduleRetryDelay()`
- Exit: `clearRetrySchedule()`, `updateProcessorMetrics()`
- Context: Sophisticated retry logic with processor-specific strategies

**RequiresCapture** (Enhanced Capture Management)
- Entry: `enablePartialCaptureFlag()`, `calculateOptimalCapture()`, `scheduleReminderNotifications()`
- Exit: `validateCaptureAmount()`, `prepareCaptureContext()`
- Context: Advanced capture management with partial capture optimization

### Enhanced Variables Schema

```json
{
  "core_payment": {
    "id": "string (required)",
    "amount": "integer (required, cents)",
    "currency": "string (required, ISO 4217)",
    "capture_method": "string (automatic|manual|intelligent)"
  },
  "processor_management": {
    "primary_processor": "string",
    "current_processor": "string", 
    "processor_attempts": "object",
    "fallback_processors": "array",
    "processor_routing_reason": "string"
  },
  "risk_assessment": {
    "risk_score": "float (0.0-1.0)",
    "risk_level": "string (minimal|low|medium|high|critical)",
    "risk_factors": "array",
    "ml_model_version": "string",
    "regulatory_flags": "array"
  },
  "retry_management": {
    "retry_count": "integer",
    "max_retries": "integer",
    "retry_strategy": "string (exponential|linear|custom)",
    "last_error_type": "string",
    "backoff_multiplier": "float"
  },
  "capture_management": {
    "amount_capturable": "integer",
    "amount_captured": "integer", 
    "partial_captures": "array",
    "capture_timeline": "object",
    "auto_capture_enabled": "boolean"
  },
  "compensation_tracking": {
    "compensation_events": "array",
    "rollback_operations": "array",
    "recovery_actions": "array",
    "compensation_status": "string"
  },
  "monitoring": {
    "performance_metrics": "object",
    "latency_measurements": "object",
    "error_tracking": "object",
    "audit_trail": "array"
  }
}
```

### Enhanced Events

**External Events** (API-triggered):
- `create_intent` - Initialize with advanced routing
- `attach_payment_method` - Enhanced method validation
- `confirm_intent` - Trigger comprehensive risk assessment
- `capture_requested` - Support partial and scheduled captures
- `cancel_intent` - Sophisticated cancellation with compensation

**Internal Events** (System-generated):
- `risk_assessment_completed` - ML model results available
- `processor_selected` - Optimal processor chosen
- `settlement_retry_scheduled` - Fallback processor queued
- `compensation_triggered` - Recovery action initiated
- `partial_operation_completed` - Partial capture/refund processed

**Processor Events** (Provider callbacks):
- `processor_webhook_received` - Real-time status updates
- `settlement_batch_processed` - Batch settlement completed
- `dispute_notification` - Chargeback or dispute filed
- `regulatory_update` - Compliance status change

## Advanced Flow Examples

### Multi-Processor Failover Flow
```
Created → RequiresPaymentMethod → RequiresConfirmation → RiskAssessment → Processing
  ↓ (Primary processor fails)
Processing → SettlementRetry → Processing (with fallback processor) → Succeeded
```

1. Stripe authorization fails due to network issue
2. System automatically selects Square as fallback
3. Transaction completes seamlessly
4. Customer experience uninterrupted

### Intelligent Risk-Based Routing
```
Created → RequiresPaymentMethod → RequiresConfirmation → RiskAssessment
  ↓ (High-risk transaction detected)
RiskAssessment → RequiresAction → Processing (with enhanced monitoring) → Succeeded
```

1. ML model detects elevated risk score
2. Additional authentication required
3. Enhanced monitoring during processing
4. Successful completion with audit trail

### Partial Capture with Compensation
```
Processing → RequiresCapture → Capturing (partial) → RequiresCapture → Capturing (remaining) → Succeeded
```

1. Initial authorization for $1000
2. Merchant captures $600 for shipped items
3. Customer cancels remaining $400
4. System automatically handles partial refund

### Advanced Retry with Multiple Processors
```
Processing → SettlementRetry → Processing → SettlementRetry → Processing → Failed
```

1. Stripe fails (network timeout)
2. Retry with Square (rate limited)
3. Retry with Adyen (authorization declined)
4. All processors exhausted, payment fails with full compensation

## Compensation Patterns (Enhanced)

### 1. Multi-Processor Authorization Rollback
**Trigger**: Settlement failure after successful authorization across multiple processors
**Actions**: 
- Void authorizations on all processors
- Release customer funds immediately
- Update merchant settlement timeline
- Generate reconciliation report

### 2. Partial Transaction Compensation
**Trigger**: Partial failure in multi-step transaction
**Actions**:
- Calculate partial refund amount based on completion percentage
- Execute proportional refunds across payment methods
- Adjust merchant fees and commissions
- Maintain transaction integrity across systems

### 3. Intelligent Timeout Recovery
**Trigger**: Payment process timeout with preserved context
**Actions**:
- Preserve payment context for later recovery
- Implement exponential backoff retry strategy  
- Enable merchant manual intervention option
- Provide customer communication template

### 4. Cross-Processor Settlement Recovery
**Trigger**: Settlement batch failure affecting multiple transactions
**Actions**:
- Identify affected transactions by processor and batch
- Implement bulk retry mechanism with alternative processors
- Maintain transaction ordering and dependencies
- Provide real-time recovery status updates

## Enhanced API Integrations

### 1. Advanced Risk API Integration

**Endpoint**: `POST /api/ml/risk/comprehensive`

**Enhanced Request**:
```json
{
  "transaction_context": {
    "amount": 25000,
    "currency": "USD",
    "merchant_category": "electronics",
    "customer_segment": "premium"
  },
  "behavioral_signals": {
    "device_fingerprint": "fp_advanced_123",
    "session_data": {
      "duration_seconds": 1247,
      "page_views": 15,
      "cart_modifications": 3
    },
    "geolocation": {
      "ip_address": "203.0.113.1",
      "country": "US",
      "city": "San Francisco",
      "confidence_score": 0.95
    }
  },
  "historical_context": {
    "customer_lifetime_value": 15000,
    "transaction_velocity_24h": 2,
    "dispute_history": [],
    "payment_method_history": ["card", "wallet"]
  }
}
```

**Enhanced Response**:
```json
{
  "assessment": {
    "overall_risk_score": 0.23,
    "risk_level": "low",
    "confidence": 0.87,
    "model_version": "v2.3.1"
  },
  "detailed_scores": {
    "fraud_likelihood": 0.15,
    "chargeback_probability": 0.08,
    "regulatory_risk": 0.02,
    "velocity_risk": 0.31
  },
  "recommendations": {
    "primary_action": "approve",
    "secondary_actions": ["enhanced_monitoring"],
    "processor_preference": "stripe",
    "authentication_required": false
  },
  "risk_factors": [
    {
      "factor": "high_transaction_amount",
      "impact": 0.12,
      "explanation": "Amount exceeds customer's typical range"
    }
  ],
  "next_review_date": "2024-11-04T16:00:00Z"
}
```

### 2. Multi-Processor Authorization API

**Endpoint**: `POST /api/payments/authorize/multi`

**Request with Processor Preferences**:
```json
{
  "payment_intent_id": "pi_v2_1234567890",
  "processor_preferences": [
    {
      "processor": "stripe",
      "priority": 1,
      "conditions": ["amount < 50000", "currency IN ['USD', 'EUR']"]
    },
    {
      "processor": "adyen", 
      "priority": 2,
      "conditions": ["international_card == true"]
    },
    {
      "processor": "square",
      "priority": 3,
      "conditions": ["fallback_required == true"]
    }
  ],
  "authorization_context": {
    "risk_assessment": {
      "score": 0.23,
      "level": "low"
    },
    "customer_authentication": {
      "3ds_completed": true,
      "biometric_verified": false
    }
  }
}
```

### 3. Enhanced Capture API with Partial Support

**Endpoint**: `POST /api/payments/capture/enhanced`

**Partial Capture Request**:
```json
{
  "charge_id": "ch_1234567890",
  "capture_operations": [
    {
      "amount": 7500,
      "description": "Shipped items",
      "line_items": ["item_123", "item_456"],
      "capture_immediately": true
    },
    {
      "amount": 2500,
      "description": "Pending items", 
      "scheduled_capture_date": "2024-11-10T00:00:00Z",
      "auto_cancel_after": "2024-11-15T00:00:00Z"
    }
  ],
  "settlement_preferences": {
    "batch_processing": true,
    "priority_level": "standard"
  }
}
```

## Enhanced Testing Strategy

### Load Testing Scenarios
1. **Multi-Processor Stress Test**
   - Simulate 10,000 concurrent payments across 5 processors
   - Validate failover performance under load
   - Measure cross-processor consistency

2. **Advanced Retry Pattern Testing**
   - Test exponential backoff with jitter
   - Validate processor-specific retry strategies
   - Ensure compensation integrity during retry storms

3. **Partial Operation Testing**
   - Test complex partial capture scenarios
   - Validate partial refund calculations
   - Ensure audit trail completeness

### Chaos Engineering
1. **Processor Outage Simulation**
   - Random processor failures during peak traffic
   - Network partition testing
   - Gradual degradation scenarios

2. **Data Consistency Testing**
   - Concurrent modification detection
   - Cross-system state synchronization
   - Recovery from inconsistent states

## Enhanced Monitoring and Observability

### Real-Time Metrics Dashboard
```json
{
  "payment_metrics": {
    "transactions_per_second": 450,
    "success_rate_overall": 99.2,
    "success_rate_by_processor": {
      "stripe": 99.5,
      "adyen": 98.9,
      "square": 99.1
    },
    "average_processing_time_ms": 847,
    "retry_rate": 2.3,
    "compensation_events_24h": 12
  },
  "risk_metrics": {
    "average_risk_score": 0.18,
    "blocked_transactions_rate": 0.5,
    "false_positive_rate": 0.08,
    "ml_model_accuracy": 0.94
  },
  "processor_metrics": {
    "failover_events_24h": 3,
    "processor_latency_p99": {
      "stripe": 1200,
      "adyen": 1850,
      "square": 950
    },
    "processor_availability": {
      "stripe": 99.97,
      "adyen": 99.91,
      "square": 99.99
    }
  }
}
```

### Enhanced Alerting Rules
```yaml
alerts:
  critical:
    - name: ProcessorFailoverStorm
      condition: failover_rate > 10% for 2m
      action: page_oncall_team
      
    - name: CompensationEventSpike  
      condition: compensation_events_1h > 50
      action: escalate_to_payments_lead
      
    - name: MLModelDrift
      condition: model_accuracy < 0.85 for 15m
      action: trigger_model_retrain
      
  warning:
    - name: PartialCaptureBacklog
      condition: pending_captures_6h > 100
      action: notify_operations_team
      
    - name: CrossProcessorLatencyDrift
      condition: processor_latency_variance > 2x for 10m
      action: investigate_network_issues
```

## Performance Characteristics (Enhanced)

### Throughput Benchmarks (v2)
- **Multi-Processor Workflow Creation**: ~300 workflows/second
- **Cross-Processor State Transitions**: ~750 transitions/second
- **Advanced Risk Assessment**: ~200 assessments/second  
- **Partial Operation Processing**: ~400 operations/second
- **Compensation Event Handling**: ~150 events/second

### Memory Usage (Enhanced)
- **Base Workflow with Multi-Processor Context**: ~8KB per instance
- **With Full Risk Assessment Data**: ~15KB per instance  
- **Peak Memory (1,000 concurrent workflows)**: ~25MB
- **Compensation Event Storage**: ~2KB per event

### Latency Targets (v2)
- **State Transition (Simple)**: <15ms (99th percentile)
- **Cross-Processor Failover**: <500ms (95th percentile)
- **Risk Assessment Integration**: <200ms (90th percentile)
- **Partial Capture Processing**: <100ms (95th percentile)

## Migration from v1

### Backward Compatibility
- All v1 workflows continue to function
- Gradual migration path with feature flags
- Enhanced capabilities opt-in by merchant

### Migration Strategy
1. **Phase 1**: Deploy v2 alongside v1 (blue-green)
2. **Phase 2**: Enable enhanced features for test merchants
3. **Phase 3**: Migrate high-volume merchants
4. **Phase 4**: Deprecate v1 after 6-month transition period

---

This enhanced v2 implementation provides enterprise-grade payment processing capabilities while maintaining the deterministic, auditable characteristics of the Xavante workflow engine. The advanced compensation patterns, multi-processor support, and intelligent retry mechanisms make it suitable for high-stakes production environments with complex payment requirements.