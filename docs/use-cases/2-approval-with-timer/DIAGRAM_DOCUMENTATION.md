# Approval with Timer - State Diagram

## Overview
The `approval-with-timer.dot` file contains a comprehensive GraphViz diagram representing the complete Approval with Timer workflow state machine as implemented in the Xavante Workflow Engine.

## Diagram Structure

### States (6 Total)

1. **Draft** (Initial State)
   - Green color scheme
   - Entry point for new approval requests
   - No timer behavior active

2. **PendingApproval** (Main State)
   - Orange color scheme  
   - Complex entry/exit actions
   - Three active timers: reminder, escalation, expiration
   - Most transitions originate from this state

3. **Escalated** (Backup Approver State)
   - Darker orange color scheme
   - Entry actions for notifications and reassignment
   - Continues expiration timer only

4. **Approved** (Terminal State)
   - Green color scheme with double border
   - Success completion state

5. **Rejected** (Terminal State)
   - Red color scheme with double border
   - Explicit rejection state

6. **AutoExpired** (Terminal State)
   - Purple color scheme with double border
   - Automatic expiration state

### Transitions (8 Total)

#### User-Initiated Transitions (Solid Lines)
- **submit**: Draft → PendingApproval
- **approve**: PendingApproval/Escalated → Approved
- **reject**: PendingApproval/Escalated → Rejected

#### Timer-Driven Transitions (Dashed Lines)
- **reminderTick**: PendingApproval → PendingApproval (loop)
- **escalate**: PendingApproval → Escalated
- **expire**: PendingApproval/Escalated → AutoExpired

### Visual Elements

#### Color Coding
- **Green**: Initial and success states
- **Orange**: Active processing states
- **Red**: Rejection/failure states  
- **Purple**: Timeout/expiration states
- **Blue**: User action transitions
- **Purple/Brown**: Timer transitions

#### Line Styles
- **Solid lines**: User-initiated events
- **Dashed lines**: System timer events
- **Thick lines**: Primary transitions
- **Thin lines**: Secondary transitions

#### Information Density
Each state box contains:
- State name with colored header
- Action descriptions (entry/exit)
- Timer specifications with variables
- State type indicators (initial/terminal)

### Legend and Reference

The diagram includes two reference boxes:

1. **Legend Box**
   - Explains line styles and symbols
   - Shows timer event priorities
   - Clarifies visual conventions

2. **Variables Box**
   - Lists key configuration variables
   - Shows default timer values
   - Documents status tracking variables

## Technical Features

### GraphViz Attributes Used
- **Layout**: Top-to-bottom (TB) ranking
- **Compound**: True for complex subgraphs
- **HTML Tables**: Rich formatting for state content
- **Color Schemes**: Consistent color palette
- **Typography**: Arial font family with size variations

### Advanced Elements
- **Subgraphs**: For legend and variables sections
- **HTML Labels**: For complex multi-line content
- **Conditional Variables**: Shows {{variable}} syntax
- **Edge Weights**: Visual hierarchy through line thickness

## Usage Instructions

### Generating the Diagram
```bash
# Generate PNG
dot -Tpng approval-with-timer.dot -o approval-with-timer.png

# Generate SVG (recommended for documentation)
dot -Tsvg approval-with-timer.dot -o approval-with-timer.svg

# Generate PDF
dot -Tpdf approval-with-timer.dot -o approval-with-timer.pdf
```

### Integration with Documentation
The diagram is designed to complement:
- README.md overview
- TECHNICAL_SPEC.md detailed specifications
- IMPLEMENTATION_GUIDE.md practical examples
- TEST_DOCUMENTATION.md validation scenarios

### Customization Points
The diagram can be easily modified to show:
- Different timer values by editing variable references
- Additional states by extending the state definitions  
- Custom action descriptions by modifying table content
- Alternative color schemes by updating fillcolor attributes

## Implementation Alignment

The diagram accurately represents:
- ✅ All 6 states from the implementation
- ✅ All 8 transitions with correct conditions
- ✅ All timer events with proper semantics
- ✅ Entry/exit actions as implemented
- ✅ Variable names and default values
- ✅ Event prioritization logic
- ✅ Terminal state indicators

This creates a comprehensive visual reference that matches the actual workflow implementation and serves as both documentation and specification for the Approval with Timer workflow.