# README — Simple Approval Workflow

## Story: The lost approval that delayed a product launch
Olivia, a product manager, submitted a budget increase for an urgent user-research sprint. She emailed her manager, pinged in Slack, and added a line item to a spreadsheet. A week later, the sprint stalled because nobody could say with certainty whether the request was approved, rejected, or still pending. There was no audit trail, and reminders depended on people remembering to follow up.

## Solution: A deterministic approval state machine
This workflow introduces a small, explicit state machine for requests:
- Draft → PendingApproval → Approved or Rejected
- Every transition is triggered by an event and recorded.
- Guards enforce role-based approval (e.g., only managers can approve).
- Entry/exit actions handle assignments, notifications, and auditing.

## When to use it
- Lightweight approval flows where a single approver is sufficient.
- You want a clear audit trail and deterministic outcomes.
- You need role-based access controls on approve/reject actions.

## State model
- States:
  - Draft (entry: initContext)
  - PendingApproval (entry: assignReviewer, exit: audit)
  - Approved (terminal; entry: notifyRequester('approved'))
  - Rejected (terminal; entry: notifyRequester('rejected'))
- Events:
  - submit(requestId)
  - approve(user)
  - reject(user)
- Guards:
  - approve only if user.role == 'manager'

## Reference JSON (instance example)
```json
{
  "workflowId": "simple-approval-v1",
  "instanceId": "req-18492",
  "state": "PendingApproval",
  "context": {
    "requesterId": "u_olivia",
    "amount": 12000,
    "currency": "USD",
    "justification": "Urgent research sprint"
  },
  "history": [
    {"at": "2025-01-10T12:00:01Z", "event": "submit", "from": "Draft", "to": "PendingApproval"},
    {"at": "2025-01-10T12:00:02Z", "action": "assignReviewer", "params": {"queue": "pm-managers"}}
  ]
}
```

## Operational notes
- Idempotency: Re-submitting “approve” for an already “Approved” instance should be a no-op that returns the same terminal state.
- Auditing: Emit an event on every transition including user and correlationId.
- Access control: Enforce guard conditions server-side, not just in the UI.

## Testing checklist
- Happy path: Draft → submit → PendingApproval → approve → Approved
- Negative path: PendingApproval → reject → Rejected
- Guard enforcement: approve denied for non-manager role
- Idempotency: double approve and double reject are safe

