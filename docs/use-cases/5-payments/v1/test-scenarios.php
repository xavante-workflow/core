<?php

/**
 * Payment Intent Workflow Test Scenarios
 * 
 * This file contains comprehensive test scenarios for the Payment Intent Workflow.
 * It demonstrates various payment flows including happy paths, error handling,
 * retry logic, and edge cases.
 * 
 * Test scenarios include:
 * - Successful payments with automatic capture
 * - 3D Secure authentication flows
 * - Manual capture workflows
 * - Risk evaluation blocking
 * - Authorization failures and retries
 * - Timeout handling
 * - Customer cancellation scenarios
 * 
 * Usage:
 * php test-scenarios.php
 * 
 * @see README.01.md for complete workflow documentation
 * @see payment-intent.dot for visual state diagram
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/payment-workflow.php';

use Xavante\Runtime\Processor;
use Xavante\Models\Runtime\Process;

/**
 * Test runner for payment workflow scenarios
 */
class PaymentWorkflowTestRunner
{
    private $workflow;
    private $processor;
    
    public function __construct()
    {
        $this->workflow = createPaymentIntentWorkflow();
        $this->processor = new Processor();
    }
    
    public function runAllScenarios(): void
    {
        echo "=== Payment Intent Workflow Test Scenarios ===\n\n";
        
        $scenarios = createTestScenarios();
        $passed = 0;
        $failed = 0;
        
        foreach ($scenarios as $scenarioName => $scenario) {
            echo "Running: {$scenario['description']}\n";
            echo "Scenario: {$scenarioName}\n";
            
            try {
                $result = $this->runScenario($scenario);
                if ($result) {
                    echo "✓ PASSED\n";
                    $passed++;
                } else {
                    echo "✗ FAILED\n";
                    $failed++;
                }
            } catch (Exception $e) {
                echo "✗ ERROR: {$e->getMessage()}\n";
                $failed++;
            }
            
            echo "---\n\n";
        }
        
        echo "=== Test Results ===\n";
        echo "Total scenarios: " . ($passed + $failed) . "\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        echo "Success rate: " . round(($passed / ($passed + $failed)) * 100, 1) . "%\n";
    }
    
    /**
     * Run a single test scenario
     */
    private function runScenario(array $scenario): bool
    {
        // Create new process instance for this scenario
        $process = new Process($this->workflow);
        
        // Initialize process variables
        $process->setVariableValue('id', 'pi_test_' . uniqid());
        $process->setVariableValue('created_at', date('c'));
        
        $currentState = null;
        
        // Execute each event in the scenario
        foreach ($scenario['events'] as $eventStep) {
            $eventName = $eventStep['event'];
            $eventData = $eventStep['data'] ?? [];
            
            echo "  → Event: {$eventName}";
            if (!empty($eventData)) {
                echo " (data: " . json_encode($eventData) . ")";
            }
            echo "\n";
            
            try {
                // Process the event
                $this->processor->triggerEvent($process, $eventName, $eventData);
                
                // Get current state after event processing
                $currentState = $process->getCurrentState();
                echo "    State: {$currentState}\n";
                
                // If we've reached a final state, break
                if (in_array($currentState, ['succeeded', 'canceled', 'failed'])) {
                    break;
                }
                
            } catch (Exception $e) {
                echo "    Error: {$e->getMessage()}\n";
                return false;
            }
        }
        
        // Check if we reached the expected final state
        $expectedFinalState = $scenario['expected_final_state'];
        $success = ($currentState === $expectedFinalState);
        
        echo "  Expected final state: {$expectedFinalState}\n";
        echo "  Actual final state: {$currentState}\n";
        
        if ($success) {
            echo "  Result: State transition successful\n";
        } else {
            echo "  Result: State transition failed\n";
        }
        
        return $success;
    }
}

/**
 * Mock implementation of process methods for testing
 */
class MockPaymentProcess
{
    private $variables = [];
    private $currentState = 'created';
    private $workflow;
    
    public function __construct($workflow)
    {
        $this->workflow = $workflow;
    }
    
    public function setVariable(string $name, $value): void
    {
        $this->variables[$name] = $value;
    }
    
    public function getVariable(string $name)
    {
        return $this->variables[$name] ?? null;
    }
    
    public function getCurrentState(): string
    {
        return $this->currentState;
    }
    
    public function setState(string $state): void
    {
        $this->currentState = $state;
    }
}

/**
 * Demonstration of payment workflow state progression
 */
function demonstrateStateProgression(): void
{
    echo "=== Payment Workflow State Progression Demo ===\n\n";
    
    $workflow = createPaymentIntentWorkflow();
    
    // Show all states and their types
    echo "Workflow States:\n";
    foreach ($workflow->states->toArray() as $state) {
        $type = $state->type ?? 'intermediate';
        $marker = '';
        if ($type === 'initial') $marker = ' (START)';
        if ($type === 'final') $marker = ' (END)';
        
        echo "  • {$state->name}{$marker}\n";
        
        if (!empty($state->entryTasks)) {
            foreach ($state->entryTasks as $task) {
                echo "    ↳ entry: {$task}\n";
            }
        }
        
        if (!empty($state->exitTasks)) {
            foreach ($state->exitTasks as $task) {
                echo "    ↳ exit: {$task}\n";
            }
        }
    }
    
    echo "\nWorkflow Transitions:\n";
    foreach ($workflow->transitions->toArray() as $transition) {
        echo "  {$transition->sourceState} → {$transition->targetState}";
        echo " (event: {$transition->eventName})\n";
        
        if (!empty($transition->guards)) {
            echo "    guards: " . json_encode($transition->guards) . "\n";
        }
    }
    
    echo "\n=== Common Flow Examples ===\n\n";
    
    // Happy path
    echo "1. Happy Path (Auto Capture):\n";
    $happyPath = [
        'Created',
        'RequiresPaymentMethod (attach payment method)',
        'RequiresConfirmation (confirm intent)',
        'RiskEvaluation (risk approved)',
        'Processing (authorization succeeded)',
        'Succeeded ✓'
    ];
    
    foreach ($happyPath as $i => $step) {
        echo "   " . ($i + 1) . ". {$step}\n";
    }
    
    echo "\n2. 3DS Authentication Flow:\n";
    $threeDSPath = [
        'Created',
        'RequiresPaymentMethod (attach payment method)',
        'RequiresConfirmation (confirm intent)', 
        'RiskEvaluation (risk approved with 3DS)',
        'RequiresAction (customer completes 3DS)',
        'Processing (authorization succeeded)',
        'Succeeded ✓'
    ];
    
    foreach ($threeDSPath as $i => $step) {
        echo "   " . ($i + 1) . ". {$step}\n";
    }
    
    echo "\n3. Manual Capture Flow:\n";
    $manualCapturePath = [
        'Created',
        'RequiresPaymentMethod (attach payment method)',
        'RequiresConfirmation (confirm intent)',
        'RiskEvaluation (risk approved)',
        'Processing (authorization succeeded, manual capture)',
        'RequiresCapture (merchant captures when ready)',
        'Capturing (capture in progress)',
        'Succeeded ✓'
    ];
    
    foreach ($manualCapturePath as $i => $step) {
        echo "   " . ($i + 1) . ". {$step}\n";
    }
    
    echo "\n4. Risk Blocked:\n";
    $riskBlockedPath = [
        'Created',
        'RequiresPaymentMethod (attach payment method)',
        'RequiresConfirmation (confirm intent)',
        'RiskEvaluation (high risk detected)',
        'Canceled ✗'
    ];
    
    foreach ($riskBlockedPath as $i => $step) {
        echo "   " . ($i + 1) . ". {$step}\n";
    }
}

/**
 * JSON export/import verification
 */
function verifyJsonSerialization(): void
{
    echo "\n=== JSON Serialization Verification ===\n\n";
    
    $workflow = createPaymentIntentWorkflow();
    
    // Export to JSON
    echo "Exporting workflow to JSON...\n";
    $json = $workflow->jsonSerialize();
    $jsonSize = strlen($json);
    echo "✓ JSON export successful ({$jsonSize} characters)\n";
    
    // Validate JSON structure
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "✗ JSON parsing failed: " . json_last_error_msg() . "\n";
        return;
    }
    
    echo "✓ JSON parsing successful\n";
    
    // Verify essential fields
    $requiredFields = ['id', 'name', 'description', 'states', 'transitions', 'variables'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (empty($missingFields)) {
        echo "✓ All required fields present\n";
    } else {
        echo "✗ Missing fields: " . implode(', ', $missingFields) . "\n";
    }
    
    // Show summary
    echo "\nJSON Structure Summary:\n";
    echo "  • ID: {$data['id']}\n";
    echo "  • Name: {$data['name']}\n";
    echo "  • States: " . count($data['states'] ?? []) . "\n";
    echo "  • Transitions: " . count($data['transitions'] ?? []) . "\n";
    echo "  • Variables: " . count($data['variables'] ?? []) . "\n";
}

/**
 * Performance benchmark for workflow operations
 */
function performanceBenchmark(): void
{
    echo "\n=== Performance Benchmark ===\n\n";
    
    $iterations = 1000;
    
    // Benchmark workflow creation
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $workflow = createPaymentIntentWorkflow();
    }
    $creationTime = (microtime(true) - $start) * 1000;
    
    echo "Workflow Creation ({$iterations} iterations):\n";
    echo "  • Total time: " . number_format($creationTime, 2) . "ms\n";
    echo "  • Average time: " . number_format($creationTime / $iterations, 3) . "ms per workflow\n";
    
    // Benchmark JSON serialization
    $workflow = createPaymentIntentWorkflow();
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $json = $workflow->jsonSerialize();
    }
    $serializationTime = (microtime(true) - $start) * 1000;
    
    echo "\nJSON Serialization ({$iterations} iterations):\n";
    echo "  • Total time: " . number_format($serializationTime, 2) . "ms\n";
    echo "  • Average time: " . number_format($serializationTime / $iterations, 3) . "ms per serialization\n";
    
    // Memory usage
    $memoryUsage = memory_get_usage(true);
    $peakMemory = memory_get_peak_usage(true);
    
    echo "\nMemory Usage:\n";
    echo "  • Current: " . number_format($memoryUsage / 1024 / 1024, 2) . "MB\n";
    echo "  • Peak: " . number_format($peakMemory / 1024 / 1024, 2) . "MB\n";
}

// Run demonstrations if script is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    // Check if specific test is requested
    $testType = $argv[1] ?? 'all';
    
    switch ($testType) {
        case 'scenarios':
            $runner = new PaymentWorkflowTestRunner();
            $runner->runAllScenarios();
            break;
            
        case 'states':
            demonstrateStateProgression();
            break;
            
        case 'json':
            verifyJsonSerialization();
            break;
            
        case 'performance':
            performanceBenchmark();
            break;
            
        case 'all':
        default:
            $runner = new PaymentWorkflowTestRunner();
            $runner->runAllScenarios();
            demonstrateStateProgression();
            verifyJsonSerialization();
            performanceBenchmark();
            break;
    }
}