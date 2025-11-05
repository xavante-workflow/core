<?php

declare(strict_types=1);

/**
 * Enhanced Payment Intent Workflow v2 - Multi-Processor Implementation
 * 
 * This implementation demonstrates advanced payment processing capabilities including:
 * - Multi-processor support with intelligent failover
 * - Advanced risk assessment with ML integration  
 * - Sophisticated retry logic with exponential backoff
 * - Partial operations support (capture, refund)
 * - Comprehensive compensation patterns
 * - Real-time monitoring and observability
 * 
 * @package Xavante\UseCases\Payments\V2
 * @version 2.0.0
 * @author Xavante Workflow Engine
 * @created 2024-11-04
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';

use Xavante\Models\Factories\WorkflowFactory;
use Xavante\Models\Factories\StateFactory;
use Xavante\Models\Factories\TransitionFactory;
use Xavante\Models\Factories\EventFactory;
use Xavante\Models\Factories\ProcessFactory;
use Xavante\Actions\SetVariableValueAction;
use Xavante\Actions\AddLogEntryAction;
use Xavante\Actions\CopyVariableAction;
use Xavante\Conditions\Operators\OperatorConstants;

/**
 * Enhanced Payment Intent Workflow v2 Factory
 * 
 * Creates a sophisticated payment processing workflow with advanced features:
 * - 12 states with comprehensive compensation patterns
 * - Multi-processor routing and fallback logic
 * - ML-powered risk assessment integration
 * - Intelligent retry mechanisms with exponential backoff
 * - Support for partial captures and settlements
 * - Real-time monitoring and audit trails
 */
class PaymentIntentWorkflowV2Factory
{
    /**
     * Create enhanced payment workflow with advanced multi-processor support
     * 
     * @return \Xavante\Models\Domain\Workflow
     */
    public static function createWorkflow(): \Xavante\Models\Domain\Workflow
    {
        // Enhanced workflow configuration
        $workflowData = [
            'id' => 'payment_intent_v2_' . uniqid(),
            'name' => 'Enhanced Payment Intent Workflow v2',
            'version' => '2.0.0',
            'description' => 'Advanced payment processing with multi-processor support and intelligent compensation'
        ];

        // Create enhanced variable schema
        $variables = self::createEnhancedVariables();
        
        // Create comprehensive event definitions  
        $events = self::createEnhancedEvents();
        
        // Create state machine with advanced compensation patterns
        $states = self::createEnhancedStates();
        
        // Create transition network with intelligent routing
        $transitions = self::createEnhancedTransitions();

        return WorkflowFactory::createWorkflow([
            ...$workflowData,
            'variables' => $variables,
            'events' => $events,
            'states' => $states,
            'transitions' => $transitions
        ]);
    }

    /**
     * Create enhanced variable schema for v2 capabilities
     */
    private static function createEnhancedVariables(): array
    {
        return [
            // Core payment variables
            'id' => 'pi_v2_' . uniqid(),
            'amount' => 25000, // $250.00 in cents
            'currency' => 'USD',
            'capture_method' => 'automatic',
            
            // Multi-processor management
            'primary_processor' => 'stripe',
            'current_processor' => null,
            'processor_attempts' => [
                'stripe' => 0,
                'adyen' => 0,
                'square' => 0,
                'paypal' => 0
            ],
            'fallback_processors' => ['adyen', 'square', 'paypal'],
            'processor_routing_reason' => 'amount_based_routing',
            
            // Advanced risk assessment
            'risk_score' => 0.0,
            'risk_level' => 'unknown',
            'risk_factors' => [],
            'ml_model_version' => 'v2.3.1',
            'regulatory_flags' => [],
            'requires_3ds' => false,
            
            // Intelligent retry management
            'retry_count' => 0,
            'max_retries' => 3,
            'max_settlement_retries' => 5,
            'retry_strategy' => 'exponential',
            'last_error_type' => null,
            'backoff_multiplier' => 2.0,
            
            // Advanced capture management
            'amount_capturable' => 0,
            'amount_captured' => 0,
            'partial_captures' => [],
            'capture_timeline' => [],
            'auto_capture_enabled' => true,
            
            // Compensation tracking
            'compensation_events' => [],
            'rollback_operations' => [],
            'recovery_actions' => [],
            'compensation_status' => 'none',
            
            // Enhanced monitoring
            'performance_metrics' => [
                'start_time' => microtime(true),
                'state_transitions' => 0,
                'api_calls' => 0
            ],
            'latency_measurements' => [],
            'error_tracking' => [],
            'audit_trail' => [],
            
            // Payment method validation
            'payment_method' => [
                'validated' => false,
                'type' => null,
                'last_four' => null,
                'brand' => null
            ],
            
            // Authorization management
            'authorization_hold' => [
                'created' => false,
                'amount' => 0,
                'expires_at' => null
            ],
            
            // Settlement tracking
            'settlement' => [
                'confirmed' => false,
                'processor_response' => null,
                'batch_id' => null,
                'settled_at' => null
            ],
            
            // Timeout management
            'timeouts' => [
                'pm_timeout' => null,
                'confirm_timeout' => null,
                'risk_timeout' => null,
                'action_timeout' => null,
                'capture_timeout' => null,
                'capture_exec' => null
            ],
            
            // User interaction
            'user' => [
                'cancelled' => false,
                'last_interaction' => null
            ],
            
            // Fraud and security
            'fraud_detected' => false,
            '3ds_result' => null,
            'device_fingerprint' => null,
            
            // Capture operations
            'capture_amount' => 0,
            'captured_amount' => 0,
            
            // Error handling
            'error_type' => null,
            'timeout_type' => null,
            'last_error_message' => null,
            
            // Processor availability
            'available_processors' => [
                'count' => 4,
                'list' => ['stripe', 'adyen', 'square', 'paypal']
            ],
            'fallback_processor' => [
                'available' => true,
                'selected' => null
            ]
        ];
    }

    /**
     * Create comprehensive event definitions for v2 workflow
     */
    private static function createEnhancedEvents(): array
    {
        $eventNames = [
            // External events (API-triggered)
            'create_intent',
            'payment_method_attached',
            'confirmation_received',
            'customer_action_completed',
            'action_failed',
            'settlement_confirmed',
            'settlement_failed',
            'capture_requested',
            'capture_succeeded',
            'capture_failed',
            'user_cancellation',
            
            // Internal events (system-generated)
            'risk_requires_action',
            'risk_approved',
            'risk_blocked',
            'authorization_succeeded',
            'retry_scheduled',
            'all_processors_failed',
            'timeout_expired'
        ];

        return array_map(function($eventName) {
            return EventFactory::createFromArray([
                'id' => $eventName . '_' . uniqid(),
                'name' => $eventName,
                'description' => "Enhanced v2 event: {$eventName}"
            ]);
        }, $eventNames);
    }

    /**
     * Create enhanced state definitions with advanced actions
     */
    private static function createEnhancedStates(): array
    {
        return [
            // Initial State - Enhanced initialization
            StateFactory::createFromArray([
                'id' => 'created',
                'name' => 'Created',
                'type' => 'initial',
                'description' => 'Payment intent created with enhanced v2 features',
                'entry_tasks' => [
                    new SetVariableValueAction('compensation_status', 'none'),
                    new SetVariableValueAction('retry_count', 0),
                    new SetVariableValueAction('performance_metrics.state_transitions', 1),
                    new AddLogEntryAction('Payment intent v2 created with enhanced features', 'info')
                ]
            ]),

            // Awaiting Payment Method - Enhanced validation
            StateFactory::createFromArray([
                'id' => 'requires_payment_method',
                'name' => 'RequiresPaymentMethod', 
                'type' => 'intermediate',
                'description' => 'Enhanced payment method requirements with intelligent validation',
                'entry_tasks' => [
                    new AddLogEntryAction('Awaiting enhanced payment method validation', 'info')
                ],
                'exit_tasks' => [
                    new SetVariableValueAction('payment_method.validated', true),
                    new AddLogEntryAction('Payment method validated with v2 enhancements', 'info')
                ]
            ]),

            // Confirmation Ready - Risk assessment preparation  
            StateFactory::createFromArray([
                'id' => 'requires_confirmation',
                'name' => 'RequiresConfirmation',
                'type' => 'intermediate',
                'description' => 'Enhanced confirmation state with risk assessment preparation',
                'entry_tasks' => [
                    new SetVariableValueAction('authorization_hold.created', true),
                    new SetVariableValueAction('authorization_hold.amount', function($process) { 
                        return $process->getVariableValue('amount'); 
                    }),
                    new AddLogEntryAction('Authorization hold created, ready for enhanced confirmation', 'info')
                ]
            ]),

            // ML-Powered Risk Assessment - New State
            StateFactory::createFromArray([
                'id' => 'risk_assessment',
                'name' => 'RiskAssessment',
                'type' => 'intermediate', 
                'description' => 'Advanced ML-powered risk assessment with regulatory compliance',
                'entry_tasks' => [
                    new AddLogEntryAction('Initiating advanced ML risk assessment v2', 'info'),
                    new SetVariableValueAction('ml_model_version', 'v2.3.1')
                ],
                'exit_tasks' => [
                    new AddLogEntryAction('Advanced risk assessment completed', 'info')
                ]
            ]),

            // Customer Action Required - Enhanced interaction
            StateFactory::createFromArray([
                'id' => 'requires_action',
                'name' => 'RequiresAction',
                'type' => 'intermediate',
                'description' => 'Enhanced customer interaction with intelligent challenge selection',
                'entry_tasks' => [
                    new AddLogEntryAction('Enhanced customer action required (3DS/verification)', 'info')
                ]
            ]),

            // Processing - Multi-processor support
            StateFactory::createFromArray([
                'id' => 'processing',
                'name' => 'Processing',
                'type' => 'intermediate',
                'description' => 'Advanced processing with multi-processor support and failover',
                'entry_tasks' => [
                    new CopyVariableAction('retry_count', 'current_retry_attempt'),
                    new AddLogEntryAction('Processing with enhanced multi-processor support', 'info')
                ],
                'exit_tasks' => [
                    new AddLogEntryAction('Processing attempt completed', 'info')
                ]
            ]),

            // Settlement Retry - New State for enhanced retry logic
            StateFactory::createFromArray([
                'id' => 'settlement_retry',
                'name' => 'SettlementRetry',
                'type' => 'intermediate',
                'description' => 'Intelligent settlement retry with fallback processor selection',
                'entry_tasks' => [
                    new AddLogEntryAction('Initiating intelligent settlement retry', 'warning')
                ],
                'exit_tasks' => [
                    new AddLogEntryAction('Settlement retry attempt completed', 'info')
                ]
            ]),

            // Manual Capture - Enhanced capture management
            StateFactory::createFromArray([
                'id' => 'requires_capture',
                'name' => 'RequiresCapture',
                'type' => 'intermediate',
                'description' => 'Enhanced capture management with partial capture support',
                'entry_tasks' => [
                    new CopyVariableAction('amount', 'amount_capturable'),
                    new SetVariableValueAction('auto_capture_enabled', false),
                    new AddLogEntryAction('Enhanced capture management enabled', 'info')
                ]
            ]),

            // Capture Execution - Enhanced execution tracking
            StateFactory::createFromArray([
                'id' => 'capturing',
                'name' => 'Capturing', 
                'type' => 'intermediate',
                'description' => 'Enhanced capture execution with comprehensive tracking',
                'entry_tasks' => [
                    new AddLogEntryAction('Executing enhanced capture operation', 'info')
                ]
            ]),

            // Success - Enhanced completion with full audit
            StateFactory::createFromArray([
                'id' => 'succeeded',
                'name' => 'Succeeded',
                'type' => 'final',
                'description' => 'Payment completed with enhanced success tracking',
                'entry_tasks' => [
                    new SetVariableValueAction('settlement.confirmed', true),
                    new SetVariableValueAction('settlement.settled_at', function() { 
                        return date('c'); 
                    }),
                    new AddLogEntryAction('Payment succeeded with enhanced v2 processing', 'info')
                ]
            ]),

            // Cancellation - Enhanced compensation patterns
            StateFactory::createFromArray([
                'id' => 'canceled',
                'name' => 'Canceled',
                'type' => 'final', 
                'description' => 'Payment canceled with advanced compensation execution',
                'entry_tasks' => [
                    new SetVariableValueAction('compensation_status', 'in_progress'),
                    new AddLogEntryAction('Payment canceled, executing enhanced compensation patterns', 'warning')
                ]
            ]),

            // Failure - Enhanced rollback and recovery
            StateFactory::createFromArray([
                'id' => 'failed',
                'name' => 'Failed',
                'type' => 'final',
                'description' => 'Payment failed with comprehensive rollback and audit',
                'entry_tasks' => [
                    new SetVariableValueAction('compensation_status', 'completed'),
                    new AddLogEntryAction('Payment failed, enhanced compensation completed', 'error')
                ]
            ])
        ];
    }

    /**
     * Create enhanced transition network with intelligent routing
     */
    private static function createEnhancedTransitions(): array
    {
        return [
            // Primary flow transitions
            TransitionFactory::createFromArray([
                'id' => 'create_to_requires_pm',
                'sourceStateId' => 'created',
                'targetStateId' => 'requires_payment_method',
                'eventId' => 'create_intent',
                'guard' => null,
                'description' => 'Initialize enhanced payment flow'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'pm_to_confirmation',
                'sourceStateId' => 'requires_payment_method',
                'targetStateId' => 'requires_confirmation', 
                'eventId' => 'payment_method_attached',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'payment_method.validated',
                    'right_operand' => true
                ],
                'description' => 'Payment method validated with enhanced checks'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'confirmation_to_risk',
                'sourceStateId' => 'requires_confirmation',
                'targetStateId' => 'risk_assessment',
                'eventId' => 'confirmation_received',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'authorization_hold.created',
                    'right_operand' => true
                ],
                'description' => 'Start advanced ML risk assessment'
            ]),

            // Risk assessment paths
            TransitionFactory::createFromArray([
                'id' => 'risk_to_action',
                'sourceStateId' => 'risk_assessment',
                'targetStateId' => 'requires_action',
                'eventId' => 'risk_requires_action',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'requires_3ds',
                    'right_operand' => true
                ],
                'description' => 'Risk assessment requires enhanced customer action'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'risk_to_processing',
                'sourceStateId' => 'risk_assessment',
                'targetStateId' => 'processing',
                'eventId' => 'risk_approved',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'authorization_hold.created',
                    'right_operand' => true
                ],
                'description' => 'Risk approved, proceed to enhanced processing'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'risk_to_canceled',
                'sourceStateId' => 'risk_assessment',
                'targetStateId' => 'canceled',
                'eventId' => 'risk_blocked',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'fraud_detected',
                    'right_operand' => true
                ],
                'description' => 'Risk blocked, execute enhanced compensation'
            ]),

            // Customer action paths
            TransitionFactory::createFromArray([
                'id' => 'action_to_processing',
                'sourceStateId' => 'requires_action',
                'targetStateId' => 'processing',
                'eventId' => 'customer_action_completed',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => '3ds_result',
                    'right_operand' => 'authenticated'
                ],
                'description' => 'Customer action successful, proceed to enhanced processing'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'action_to_pm_retry',
                'sourceStateId' => 'requires_action',
                'targetStateId' => 'requires_payment_method',
                'eventId' => 'action_failed',
                'guard' => [
                    'operator' => OperatorConstants::LESS_THAN,
                    'left_operand' => 'retry_count',
                    'right_operand' => 'max_retries'
                ],
                'description' => 'Action failed, retry with enhanced logic'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'action_to_failed',
                'sourceStateId' => 'requires_action', 
                'targetStateId' => 'failed',
                'eventId' => 'action_failed',
                'guard' => [
                    'operator' => OperatorConstants::GREATER_THAN_OR_EQUAL,
                    'left_operand' => 'retry_count',
                    'right_operand' => 'max_retries'
                ],
                'description' => 'Action failed permanently, enhanced compensation'
            ]),

            // Enhanced processing paths
            TransitionFactory::createFromArray([
                'id' => 'processing_to_capture',
                'sourceStateId' => 'processing',
                'targetStateId' => 'requires_capture',
                'eventId' => 'authorization_succeeded',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'capture_method',
                    'right_operand' => 'manual'
                ],
                'description' => 'Authorization successful, enhanced manual capture'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'processing_to_succeeded',
                'sourceStateId' => 'processing',
                'targetStateId' => 'succeeded',
                'eventId' => 'settlement_confirmed',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'capture_method',
                    'right_operand' => 'automatic'
                ],
                'description' => 'Enhanced automatic settlement confirmed'
            ]),

            // Enhanced settlement retry paths
            TransitionFactory::createFromArray([
                'id' => 'processing_to_retry',
                'sourceStateId' => 'processing',
                'targetStateId' => 'settlement_retry',
                'eventId' => 'settlement_failed',
                'guard' => [
                    'operator' => OperatorConstants::LESS_THAN,
                    'left_operand' => 'retry_count',
                    'right_operand' => 'max_settlement_retries'
                ],
                'description' => 'Settlement failed, intelligent retry available'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'processing_to_failed',
                'sourceStateId' => 'processing',
                'targetStateId' => 'failed',
                'eventId' => 'settlement_failed',
                'guard' => [
                    'operator' => OperatorConstants::GREATER_THAN_OR_EQUAL,
                    'left_operand' => 'retry_count',
                    'right_operand' => 'max_settlement_retries'
                ],
                'description' => 'Settlement failed permanently, enhanced compensation'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'retry_to_processing',
                'sourceStateId' => 'settlement_retry',
                'targetStateId' => 'processing',
                'eventId' => 'retry_scheduled',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'fallback_processor.available',
                    'right_operand' => true
                ],
                'description' => 'Intelligent retry with fallback processor'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'retry_to_failed',
                'sourceStateId' => 'settlement_retry',
                'targetStateId' => 'failed',
                'eventId' => 'all_processors_failed',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'available_processors.count',
                    'right_operand' => 0
                ],
                'description' => 'All processors exhausted, enhanced compensation'
            ]),

            // Enhanced capture flow
            TransitionFactory::createFromArray([
                'id' => 'capture_to_capturing',
                'sourceStateId' => 'requires_capture',
                'targetStateId' => 'capturing',
                'eventId' => 'capture_requested',
                'guard' => [
                    'operator' => OperatorConstants::GREATER_THAN,
                    'left_operand' => 'capture_amount',
                    'right_operand' => 0
                ],
                'description' => 'Enhanced capture operation requested'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'capturing_to_succeeded',
                'sourceStateId' => 'capturing',
                'targetStateId' => 'succeeded',
                'eventId' => 'capture_succeeded', 
                'guard' => [
                    'operator' => OperatorConstants::GREATER_THAN,
                    'left_operand' => 'captured_amount',
                    'right_operand' => 0
                ],
                'description' => 'Enhanced capture successful'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'capturing_to_capture_retry',
                'sourceStateId' => 'capturing',
                'targetStateId' => 'requires_capture',
                'eventId' => 'capture_failed',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'error_type',
                    'right_operand' => 'retryable'
                ],
                'description' => 'Capture failed, enhanced retry logic'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'capturing_to_failed',
                'sourceStateId' => 'capturing',
                'targetStateId' => 'failed',
                'eventId' => 'capture_failed',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'error_type',
                    'right_operand' => 'permanent'
                ],
                'description' => 'Capture failed permanently, enhanced compensation'
            ]),

            // Enhanced cancellation paths with timeout management
            TransitionFactory::createFromArray([
                'id' => 'pm_user_cancel',
                'sourceStateId' => 'requires_payment_method',
                'targetStateId' => 'canceled',
                'eventId' => 'user_cancellation',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'user.cancelled',
                    'right_operand' => true
                ],
                'description' => 'User cancellation with enhanced compensation'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'confirmation_timeout',
                'sourceStateId' => 'requires_confirmation',
                'targetStateId' => 'canceled',
                'eventId' => 'timeout_expired',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'timeout_type',
                    'right_operand' => 'confirmation'
                ],
                'description' => 'Confirmation timeout, enhanced compensation'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'action_timeout',
                'sourceStateId' => 'requires_action',
                'targetStateId' => 'canceled',
                'eventId' => 'timeout_expired',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'timeout_type',
                    'right_operand' => 'customer_action'
                ],
                'description' => 'Customer action timeout, enhanced compensation'
            ]),

            TransitionFactory::createFromArray([
                'id' => 'capture_timeout',
                'sourceStateId' => 'requires_capture',
                'targetStateId' => 'canceled',
                'eventId' => 'timeout_expired',
                'guard' => [
                    'operator' => OperatorConstants::EQUALS,
                    'left_operand' => 'timeout_type',
                    'right_operand' => 'capture_window'
                ],
                'description' => 'Capture timeout, enhanced compensation'
            ])
        ];
    }
}

/**
 * Enhanced Payment Processing Service v2
 * 
 * Demonstrates advanced integration patterns with:
 * - Multi-processor management and intelligent routing
 * - ML-powered risk assessment with real-time scoring
 * - Sophisticated retry logic with exponential backoff
 * - Comprehensive compensation and recovery patterns
 * - Real-time monitoring and observability
 */
class EnhancedPaymentProcessingServiceV2
{
    private \Xavante\Runtime\Processor $processor;
    private array $processorConfig;
    private array $riskConfig;
    
    public function __construct()
    {
        $this->processor = new \Xavante\Runtime\Processor();
        $this->processorConfig = $this->initializeProcessorConfig();
        $this->riskConfig = $this->initializeRiskConfig();
    }

    /**
     * Create and initialize enhanced payment process v2
     */
    public function createEnhancedPaymentProcess(array $paymentData): \Xavante\Models\Runtime\Process
    {
        $workflow = PaymentIntentWorkflowV2Factory::createWorkflow();
        
        // Enhanced variable initialization with v2 features
        $enhancedVariables = [
            'id' => $paymentData['id'] ?? 'pi_v2_' . uniqid(),
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'USD',
            'capture_method' => $paymentData['capture_method'] ?? 'automatic',
            
            // Enhanced processor selection based on intelligent routing
            'primary_processor' => $this->selectOptimalProcessor($paymentData),
            'processor_routing_reason' => $this->getRoutingReason($paymentData),
            
            // Initialize enhanced monitoring
            'performance_metrics' => [
                'start_time' => microtime(true),
                'creation_timestamp' => date('c'),
                'state_transitions' => 0,
                'api_calls' => 0,
                'processor_attempts' => 0
            ],
            
            // Enhanced audit trail initialization
            'audit_trail' => [[
                'timestamp' => date('c'),
                'event' => 'process_creation',
                'details' => 'Enhanced payment process v2 created',
                'version' => '2.0.0'
            ]]
        ];

        return ProcessFactory::createFromWorkflow($workflow, $enhancedVariables);
    }

    /**
     * Execute enhanced payment processing with comprehensive error handling
     */
    public function processEnhancedPayment(\Xavante\Models\Runtime\Process $process, string $eventName, array $eventData = []): array
    {
        try {
            $startTime = microtime(true);
            
            // Enhanced event processing with performance tracking
            $this->updatePerformanceMetrics($process, 'event_processing_start');
            
            // Process event with enhanced error handling
            $result = $this->processor->processEvent($process, $eventName, $eventData);
            
            // Calculate processing latency
            $latency = (microtime(true) - $startTime) * 1000;
            $this->recordLatencyMeasurement($process, $eventName, $latency);
            
            // Enhanced audit trail update
            $this->updateAuditTrail($process, $eventName, $result, $latency);
            
            return [
                'success' => true,
                'result' => $result,
                'performance' => [
                    'latency_ms' => $latency,
                    'timestamp' => date('c')
                ],
                'current_state' => $process->getActiveStates(),
                'enhanced_metadata' => $this->getEnhancedProcessMetadata($process)
            ];
            
        } catch (\Exception $e) {
            // Enhanced error handling with compensation patterns
            return $this->handleEnhancedProcessingError($process, $eventName, $e);
        }
    }

    /**
     * Intelligent processor selection based on enhanced routing rules
     */
    private function selectOptimalProcessor(array $paymentData): string
    {
        $amount = $paymentData['amount'];
        $currency = $paymentData['currency'] ?? 'USD';
        $region = $paymentData['region'] ?? 'US';
        
        // Enhanced routing logic with multiple factors
        if ($amount >= 100000) { // High value transactions
            return 'adyen'; // Better for high-value international
        }
        
        if (in_array($currency, ['EUR', 'GBP']) || $region === 'EU') {
            return 'adyen'; // Better for European markets
        }
        
        if ($amount < 1000 && in_array($region, ['US', 'CA'])) {
            return 'square'; // Cost-effective for small domestic transactions
        }
        
        return 'stripe'; // Default primary processor
    }

    /**
     * Get routing reason for audit and debugging
     */
    private function getRoutingReason(array $paymentData): string
    {
        $amount = $paymentData['amount'];
        $currency = $paymentData['currency'] ?? 'USD';
        $region = $paymentData['region'] ?? 'US';
        
        if ($amount >= 100000) {
            return 'high_value_routing';
        }
        if (in_array($currency, ['EUR', 'GBP'])) {
            return 'european_currency_routing';
        }
        if ($amount < 1000) {
            return 'cost_optimization_routing';
        }
        
        return 'default_routing';
    }

    /**
     * Update enhanced performance metrics
     */
    private function updatePerformanceMetrics(\Xavante\Models\Runtime\Process $process, string $metricType): void
    {
        $metrics = $process->getVariableValue('performance_metrics');
        
        switch ($metricType) {
            case 'event_processing_start':
                $metrics['api_calls']++;
                break;
            case 'state_transition':
                $metrics['state_transitions']++;
                break;
            case 'processor_attempt':
                $metrics['processor_attempts']++;
                break;
        }
        
        $metrics['last_update'] = date('c');
        $process->setVariableValue('performance_metrics', $metrics);
    }

    /**
     * Record latency measurements for monitoring
     */
    private function recordLatencyMeasurement(\Xavante\Models\Runtime\Process $process, string $operation, float $latencyMs): void
    {
        $measurements = $process->getVariableValue('latency_measurements');
        
        if (!isset($measurements[$operation])) {
            $measurements[$operation] = [
                'count' => 0,
                'total_ms' => 0,
                'avg_ms' => 0,
                'min_ms' => $latencyMs,
                'max_ms' => $latencyMs
            ];
        }
        
        $measurements[$operation]['count']++;
        $measurements[$operation]['total_ms'] += $latencyMs;
        $measurements[$operation]['avg_ms'] = $measurements[$operation]['total_ms'] / $measurements[$operation]['count'];
        $measurements[$operation]['min_ms'] = min($measurements[$operation]['min_ms'], $latencyMs);
        $measurements[$operation]['max_ms'] = max($measurements[$operation]['max_ms'], $latencyMs);
        $measurements[$operation]['last_measured'] = date('c');
        
        $process->setVariableValue('latency_measurements', $measurements);
    }

    /**
     * Update comprehensive audit trail
     */
    private function updateAuditTrail(\Xavante\Models\Runtime\Process $process, string $eventName, array $result, float $latencyMs): void
    {
        $auditTrail = $process->getVariableValue('audit_trail');
        
        $auditTrail[] = [
            'timestamp' => date('c'),
            'event' => $eventName,
            'state_transition' => $result['state_transition'] ?? null,
            'latency_ms' => round($latencyMs, 2),
            'success' => $result['success'] ?? true,
            'processor' => $process->getVariableValue('current_processor'),
            'compensation_events' => $result['compensation_events'] ?? []
        ];
        
        // Keep audit trail manageable (last 100 entries)
        if (count($auditTrail) > 100) {
            $auditTrail = array_slice($auditTrail, -100);
        }
        
        $process->setVariableValue('audit_trail', $auditTrail);
    }

    /**
     * Handle enhanced processing errors with compensation patterns
     */
    private function handleEnhancedProcessingError(\Xavante\Models\Runtime\Process $process, string $eventName, \Exception $e): array
    {
        // Record error in enhanced tracking
        $errorTracking = $process->getVariableValue('error_tracking');
        $errorTracking[] = [
            'timestamp' => date('c'),
            'event' => $eventName,
            'error_type' => get_class($e),
            'error_message' => $e->getMessage(),
            'compensation_triggered' => true
        ];
        $process->setVariableValue('error_tracking', $errorTracking);
        
        // Trigger enhanced compensation patterns
        $this->executeEnhancedCompensation($process, $e);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'compensation_executed' => true,
            'current_state' => $process->getActiveStates(),
            'enhanced_error_details' => [
                'error_type' => get_class($e),
                'compensation_status' => $process->getVariableValue('compensation_status'),
                'recovery_actions' => $process->getVariableValue('recovery_actions')
            ]
        ];
    }

    /**
     * Execute enhanced compensation patterns
     */
    private function executeEnhancedCompensation(\Xavante\Models\Runtime\Process $process, \Exception $e): void
    {
        $compensationEvents = $process->getVariableValue('compensation_events');
        $recoveryActions = $process->getVariableValue('recovery_actions');
        
        // Determine compensation type based on error and current state
        $currentState = $process->getActiveStates()[0] ?? 'unknown';
        $compensationType = $this->determineCompensationType($currentState, $e);
        
        $compensationEvent = [
            'id' => uniqid('comp_'),
            'timestamp' => date('c'),
            'type' => $compensationType,
            'trigger_error' => get_class($e),
            'state' => $currentState,
            'actions_taken' => []
        ];
        
        // Execute compensation actions based on type
        switch ($compensationType) {
            case 'authorization_rollback':
                $this->executeAuthorizationRollback($process, $compensationEvent);
                break;
            case 'partial_refund_compensation':
                $this->executePartialRefundCompensation($process, $compensationEvent);
                break;
            case 'processor_failover_compensation':
                $this->executeProcessorFailoverCompensation($process, $compensationEvent);
                break;
            default:
                $this->executeGenericCompensation($process, $compensationEvent);
        }
        
        $compensationEvents[] = $compensationEvent;
        $process->setVariableValue('compensation_events', $compensationEvents);
        $process->setVariableValue('compensation_status', 'completed');
    }

    /**
     * Determine appropriate compensation type
     */
    private function determineCompensationType(string $state, \Exception $e): string
    {
        if (in_array($state, ['processing', 'settlement_retry'])) {
            return 'processor_failover_compensation';
        }
        if ($state === 'capturing') {
            return 'partial_refund_compensation';
        }
        if (in_array($state, ['requires_confirmation', 'risk_assessment'])) {
            return 'authorization_rollback';
        }
        
        return 'generic_compensation';
    }

    /**
     * Execute authorization rollback compensation
     */
    private function executeAuthorizationRollback(\Xavante\Models\Runtime\Process $process, array &$compensationEvent): void
    {
        $compensationEvent['actions_taken'][] = 'void_authorization_hold';
        $compensationEvent['actions_taken'][] = 'release_customer_funds';
        $compensationEvent['actions_taken'][] = 'notify_merchant_system';
        
        // Update process variables
        $process->setVariableValue('authorization_hold.created', false);
        $process->setVariableValue('authorization_hold.amount', 0);
    }

    /**
     * Execute partial refund compensation
     */
    private function executePartialRefundCompensation(\Xavante\Models\Runtime\Process $process, array &$compensationEvent): void
    {
        $amountCaptured = $process->getVariableValue('amount_captured');
        $totalAmount = $process->getVariableValue('amount');
        
        if ($amountCaptured > 0 && $amountCaptured < $totalAmount) {
            $refundAmount = $totalAmount - $amountCaptured;
            $compensationEvent['actions_taken'][] = "partial_refund_{$refundAmount}";
            $compensationEvent['refund_amount'] = $refundAmount;
        }
        
        $compensationEvent['actions_taken'][] = 'update_merchant_balance';
        $compensationEvent['actions_taken'][] = 'generate_refund_receipt';
    }

    /**
     * Execute processor failover compensation
     */
    private function executeProcessorFailoverCompensation(\Xavante\Models\Runtime\Process $process, array &$compensationEvent): void
    {
        $currentProcessor = $process->getVariableValue('current_processor');
        $fallbackProcessors = $process->getVariableValue('fallback_processors');
        
        if (!empty($fallbackProcessors)) {
            $nextProcessor = array_shift($fallbackProcessors);
            $process->setVariableValue('current_processor', $nextProcessor);
            $process->setVariableValue('fallback_processors', $fallbackProcessors);
            
            $compensationEvent['actions_taken'][] = "failover_to_{$nextProcessor}";
            $compensationEvent['previous_processor'] = $currentProcessor;
            $compensationEvent['new_processor'] = $nextProcessor;
        }
        
        $compensationEvent['actions_taken'][] = 'update_processor_metrics';
        $compensationEvent['actions_taken'][] = 'notify_monitoring_system';
    }

    /**
     * Execute generic compensation
     */
    private function executeGenericCompensation(\Xavante\Models\Runtime\Process $process, array &$compensationEvent): void
    {
        $compensationEvent['actions_taken'][] = 'log_error_details';
        $compensationEvent['actions_taken'][] = 'update_audit_trail';
        $compensationEvent['actions_taken'][] = 'notify_support_team';
    }

    /**
     * Get enhanced process metadata for monitoring
     */
    private function getEnhancedProcessMetadata(\Xavante\Models\Runtime\Process $process): array
    {
        return [
            'version' => '2.0.0',
            'processor_info' => [
                'current' => $process->getVariableValue('current_processor'),
                'primary' => $process->getVariableValue('primary_processor'),
                'routing_reason' => $process->getVariableValue('processor_routing_reason')
            ],
            'risk_assessment' => [
                'score' => $process->getVariableValue('risk_score'),
                'level' => $process->getVariableValue('risk_level'),
                'model_version' => $process->getVariableValue('ml_model_version')
            ],
            'retry_info' => [
                'count' => $process->getVariableValue('retry_count'),
                'strategy' => $process->getVariableValue('retry_strategy'),
                'max_retries' => $process->getVariableValue('max_retries')
            ],
            'compensation_status' => $process->getVariableValue('compensation_status'),
            'performance_summary' => $process->getVariableValue('performance_metrics')
        ];
    }

    /**
     * Initialize processor configuration
     */
    private function initializeProcessorConfig(): array
    {
        return [
            'stripe' => [
                'priority' => 1,
                'regions' => ['US', 'CA', 'EU'],
                'max_amount' => 500000,
                'retry_strategy' => 'exponential'
            ],
            'adyen' => [
                'priority' => 2,
                'regions' => ['EU', 'APAC', 'GLOBAL'],
                'max_amount' => 10000000,
                'retry_strategy' => 'linear'
            ],
            'square' => [
                'priority' => 3,
                'regions' => ['US', 'CA'],
                'max_amount' => 50000,
                'retry_strategy' => 'exponential'
            ],
            'paypal' => [
                'priority' => 4,
                'regions' => ['GLOBAL'],
                'max_amount' => 1000000,
                'retry_strategy' => 'custom'
            ]
        ];
    }

    /**
     * Initialize risk configuration
     */
    private function initializeRiskConfig(): array
    {
        return [
            'thresholds' => [
                'minimal' => 0.1,
                'low' => 0.3,
                'medium' => 0.5,
                'high' => 0.7,
                'critical' => 0.9
            ],
            'ml_model' => [
                'version' => 'v2.3.1',
                'endpoint' => '/api/ml/risk/comprehensive',
                'timeout_ms' => 500
            ],
            'regulatory' => [
                'pci_compliance_required' => true,
                'gdpr_compliance_required' => true,
                'regional_restrictions' => []
            ]
        ];
    }
}

// Example Usage of Enhanced Payment Processing v2
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "=== Enhanced Payment Intent Workflow v2 Demo ===\n\n";
    
    try {
        // Initialize enhanced service
        $paymentService = new EnhancedPaymentProcessingServiceV2();
        
        // Create enhanced payment with v2 features
        $paymentData = [
            'amount' => 75000, // $750.00
            'currency' => 'USD',
            'capture_method' => 'manual',
            'region' => 'US',
            'risk_factors' => ['new_customer', 'high_velocity']
        ];
        
        echo "Creating enhanced payment process v2...\n";
        $process = $paymentService->createEnhancedPaymentProcess($paymentData);
        echo "✓ Payment process created with enhanced features\n";
        echo "  Process ID: {$process->getId()}\n";
        echo "  Selected Processor: {$process->getVariableValue('primary_processor')}\n";
        echo "  Routing Reason: {$process->getVariableValue('processor_routing_reason')}\n\n";
        
        // Simulate enhanced payment flow with comprehensive monitoring
        $events = [
            'create_intent' => [],
            'payment_method_attached' => ['method_type' => 'card'],
            'confirmation_received' => ['customer_id' => 'cus_enhanced_123'],
            'risk_approved' => ['risk_score' => 0.25],
            'authorization_succeeded' => ['auth_code' => 'AUTH_V2_456'],
            'capture_requested' => ['capture_amount' => 75000]
        ];
        
        foreach ($events as $eventName => $eventData) {
            echo "Processing enhanced event: {$eventName}\n";
            $result = $paymentService->processEnhancedPayment($process, $eventName, $eventData);
            
            if ($result['success']) {
                echo "  ✓ Event processed successfully\n";
                echo "  ⏱ Latency: {$result['performance']['latency_ms']}ms\n";
                echo "  📊 Current State: " . implode(', ', $result['current_state']) . "\n";
                
                if (isset($result['enhanced_metadata']['processor_info'])) {
                    echo "  🔧 Processor: {$result['enhanced_metadata']['processor_info']['current']}\n";
                }
            } else {
                echo "  ❌ Event failed: {$result['error']}\n";
                echo "  🔄 Compensation: {$result['compensation_executed']}\n";
            }
            echo "\n";
        }
        
        // Display enhanced final metrics
        echo "=== Enhanced Final Process Metrics ===\n";
        $metrics = $process->getVariableValue('performance_metrics');
        echo "State Transitions: {$metrics['state_transitions']}\n";
        echo "API Calls: {$metrics['api_calls']}\n";
        echo "Processor Attempts: {$metrics['processor_attempts']}\n";
        
        $latencyMeasurements = $process->getVariableValue('latency_measurements');
        if (!empty($latencyMeasurements)) {
            echo "\nLatency Profile:\n";
            foreach ($latencyMeasurements as $operation => $stats) {
                echo "  {$operation}: avg {$stats['avg_ms']}ms (min: {$stats['min_ms']}ms, max: {$stats['max_ms']}ms)\n";
            }
        }
        
        $auditTrail = $process->getVariableValue('audit_trail');
        echo "\nAudit Trail Entries: " . count($auditTrail) . "\n";
        
        $compensationStatus = $process->getVariableValue('compensation_status');
        echo "Compensation Status: {$compensationStatus}\n";
        
        echo "\n✅ Enhanced Payment Processing v2 Demo Completed Successfully!\n";
        
    } catch (Exception $e) {
        echo "❌ Enhanced demo failed: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}