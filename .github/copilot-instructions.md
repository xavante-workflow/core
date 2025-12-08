# Xavante Workflow Engine - AI Coding Guidelines

## Project Overview
Xavante Core is a self-contained PHP workflow engine library for modeling, testing, and executing versioned workflows. This is the foundational component used by external modules but remains decoupled from them.

## Architecture & Key Concepts

### Dual Model Architecture (Critical Pattern)
- **Domain Models** (`src/Models/Domain/`): Immutable workflow templates
  - `Workflow.php`: Container with States, Transitions, Events, Variables
  - `State.php`: Nodes with entry/exit tasks and type (initial/intermediate/final)
  - `Transition.php`: Event-triggered edges with guards and sourceState→targetState
- **Runtime Models** (`src/Models/Runtime/`): Stateful execution instances  
  - `Process.php`: Running workflow instance with activeStatesIds, variables, history

### Factory Pattern (Standard Creation)
Use factories for object creation, not constructors directly:
```php
// Correct - use factory
$workflow = WorkflowFactory::createWorkflow($data);
$state = StateFactory::createFromArray($stateData);

// Avoid - direct constructor calls
$workflow = new Workflow($data); // Only for simple cases
```

### Value Objects in Types (`src/Models/Types/`)
- `Id.php`: Auto-generates unique IDs if null provided
- Containers: `States`, `Transitions`, `Variables`, `Events` collections
- Immutable value objects with `__toString()` and validation

## Development Workflows

### Docker Environment (Required)
```bash
# Start development environment  
docker compose up -d

# Run tests inside container
docker compose exec app vendor/bin/phpunit

# Access shell for debugging
docker compose exec app sh
```

### Testing Structure (PHPUnit 11.5)
- **Unit Tests**: `tests/Unit/` - Mock dependencies, test in isolation
- **Integration Tests**: `tests/Integration/` - Real component interactions
- **Use Case Tests**: `tests/Integration/UseCases/` - End-to-end scenarios
- Run specific suites: `vendor/bin/phpunit --testsuite="Unit Tests"`

## Critical Implementation Patterns

### JSON-First Serialization
All domain objects implement `JsonSerializable`:
```php
// Standard pattern in all Domain models
public function jsonSerialize(): string {
    return json_encode([
        'id' => (string) $this->id,
        'name' => (string) $this->name,
        // ... other properties
    ]);
}
```

### Actionable Interface (Actions Pattern)
All actions must implement `Actionable` interface:
```php
public function setWorkflow(Workflow $workflow): void;
public function configure(mixed ...$args): void;
public function execute(Process $process, mixed ...$args): void;
public function setCaller(mixed $caller): void;
```

### Process Execution Cycle (Processor.php)
1. Get active states from Process instance
2. Check raised events for transition triggers  
3. Evaluate transition guards/conditions
4. Execute: exit tasks → transition tasks → entry tasks
5. Update activeStatesIds and persist atomically

### State Machine Semantics (Critical)
- **One initial state**: Use `type: 'initial'` in state definition
- **Multiple final states**: Use `type: 'final'` - no outgoing transitions
- **Deterministic transitions**: Use guards to prevent ambiguous transition selection
- **Idempotent events**: Same event can be raised multiple times safely

### Use Case Development Pattern
Follow `docs/use-cases/1-simple-workflow/` structure:
1. Create workflow programmatically with factories
2. Add states with entry/exit tasks
3. Define transitions with guards and events
4. Test happy path + edge cases + guard enforcement
5. Verify JSON export/import capability

### Operators and Conditions (`src/Conditions/Operators/`)
- **OperatorRegistry**: Central registry for all operators with aliases (`equals`, `eq`, `==`)
- **OperatorConstants**: Type-safe constants to prevent typos (`OperatorConstants::EQUALS`)
- **Organized by type**: Comparison/, Logical/, String/, Date/ subdirectories
- **Interface-based**: All operators implement `OperatorInterface->evaluate(mixed, mixed): bool`
- **Usage pattern**: `OperatorRegistry::get(OperatorConstants::EQUALS)->evaluate($value1, $value2)`
- **Backward compatibility**: Original `Equals.php` still works via inheritance

## Key Files for Understanding
- `docs/specs.md`: Complete domain model semantics and execution rules
- `src/Models/Domain/Workflow.php`: Core workflow container implementation  
- `src/Runtime/Processor.php`: Execution engine with state transition logic
- `docs/use-cases/1-simple-workflow/simple-workflow.php`: Complete working example
- `tests/Unit/Models/Domain/WorkflowTest.php`: Factory usage and testing patterns
- `docs/operators.md`: Comprehensive operator guide with usage examples
- `src/Conditions/Operators/OperatorRegistry.php`: Central operator management

## Namespace & Autoloading
- PSR-4: `"Xavante\\": "src/"` (note: NOT `Xavante\Core\`)
- Tests: `"Tests\\": "tests/"`  
- Always use factories for object creation in tests and examples