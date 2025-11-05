<?php

use Xavante\Actions\MakeHttpRequestAction;
use Xavante\Actions\SetVariableValueAction;
use Xavante\Actions\CopyVariableAction;
use Xavante\Models\Domain\{Workflow, State, Transition, Variable, Condition, Event};

/**
 * Approval with Timer Workflow Definition
 * 
 * This workflow implements a time-based approval system with:
 * - Periodic reminders while awaiting approval (every 24h)
 * - Escalation to backup approver after SLA breach (48h)
 * - Auto-expiration after hard deadline (7 days)
 * 
 * States:
 * - Draft (initial)
 * - PendingApproval (with timers)
 * - Escalated (escalated approval with backup approver)
 * - Approved (final)
 * - Rejected (final) 
 * - AutoExpired (final)
 * 
 * Events:
 * - submit: Start approval process
 * - approve: Approve the request
 * - reject: Reject the request
 * - reminderTick: System-generated reminder (every 24h)
 * - escalate: System-generated escalation (at 48h)
 * - expire: System-generated expiration (at 7d)
 */

$wf = new Workflow([
    'name' => 'approval-with-timers-v1',
    'description' => 'Approval workflow with reminders, escalation, and auto-expiration'
]);

// ========================================
// STATES DEFINITION
// ========================================

// Draft State (Initial)
$draftState = new State('id:draft', 'Draft', 'initial');
$wf->addState($draftState);

// PendingApproval State (with timer setup)
$pendingApprovalState = new State('id:pending-approval', 'Pending Approval');

// Entry actions for PendingApproval: assign reviewer and schedule timers
$actionAssignReviewer = new MakeHttpRequestAction();
$actionAssignReviewer->setCaller($pendingApprovalState);
$actionAssignReviewer->configure([
    'method' => 'POST',
    'url' => 'https://api.company.com/assign-reviewer',
    'headers' => ['Content-Type' => 'application/json'],
    'body' => json_encode([
        'requestId' => '{{process.requestId}}',
        'approver' => '{{approver}}',
        'amount' => '{{amount}}'
    ]),
    'dry_run' => true
], [
    'status_code' => 200,
    'reason_phrase' => 'OK',
    'headers' => ['Content-Type' => ['application/json']],
    'contents' => '{"success": true, "assignedTo": "reviewer@company.com"}'
]);

$actionScheduleTimers = new SetVariableValueAction(
    'Schedule Timers',
    'timers.scheduled',
    'true'
);

// Exit actions for PendingApproval: cancel timers and audit
$actionCancelTimers = new SetVariableValueAction(
    'Cancel Timers',
    'timers.cancelled',
    'true'
);

$actionAuditExit = new SetVariableValueAction(
    'Audit Exit Pending Approval',
    'audit.log',
    'Exited Pending Approval State'
);

$pendingApprovalState->addEntryAction($actionAssignReviewer);
$pendingApprovalState->addEntryAction($actionScheduleTimers);
$pendingApprovalState->addExitAction($actionCancelTimers);
$pendingApprovalState->addExitAction($actionAuditExit);

$wf->addState($pendingApprovalState);

// Escalated State (backup approver)
$escalatedState = new State('id:escalated', 'Escalated');

// Entry actions for Escalated: notify escalation and reassign
$actionNotifyEscalation = new MakeHttpRequestAction();
$actionNotifyEscalation->setCaller($escalatedState);
$actionNotifyEscalation->configure([
    'method' => 'POST',
    'url' => 'https://api.company.com/notify-escalation',
    'headers' => ['Content-Type' => 'application/json'],
    'body' => json_encode([
        'requestId' => '{{process.requestId}}',
        'originalApprover' => '{{approver}}',
        'backupApprover' => '{{backupApprover}}',
        'reason' => 'SLA breach - escalating to backup approver'
    ]),
    'dry_run' => true
], [
    'status_code' => 200,
    'reason_phrase' => 'OK',
    'headers' => ['Content-Type' => ['application/json']],
    'contents' => '{"success": true, "escalated": true}'
]);

$actionReassignToBackup = new CopyVariableAction(
    'Reassign to Backup Approver',
    'backupApprover',
    'current.approver'
);

$escalatedState->addEntryAction($actionNotifyEscalation);
$escalatedState->addEntryAction($actionReassignToBackup);

$wf->addState($escalatedState);

// Terminal States
$wf->addState(new State('id:approved', 'Approved', 'final'));
$wf->addState(new State('id:rejected', 'Rejected', 'final'));
$wf->addState(new State('id:auto-expired', 'Auto Expired', 'final'));

$wf->setInitialStatesIds(['id:draft']);

// ========================================
// VARIABLES DEFINITION
// ========================================

$wf->addVariable(new Variable('request.status', 'Request Status', 'string', 'draft'));
$wf->addVariable(new Variable('approver', 'Primary Approver', 'string', ''));
$wf->addVariable(new Variable('backupApprover', 'Backup Approver', 'string', ''));
$wf->addVariable(new Variable('current.approver', 'Current Approver', 'string', ''));
$wf->addVariable(new Variable('amount', 'Request Amount', 'number', 0));
$wf->addVariable(new Variable('sla.hours', 'SLA Hours', 'number', 48));
$wf->addVariable(new Variable('reminder.hours', 'Reminder Interval Hours', 'number', 24));
$wf->addVariable(new Variable('expiry.days', 'Expiry Days', 'number', 7));
$wf->addVariable(new Variable('timers.scheduled', 'Timers Scheduled', 'string', 'false'));
$wf->addVariable(new Variable('timers.cancelled', 'Timers Cancelled', 'string', 'false'));
$wf->addVariable(new Variable('audit.log', 'Audit Log', 'string', ''));

// ========================================
// EVENTS DEFINITION
// ========================================

// Submit Event
$eventSubmit = new Event('id:submit', 'Submit Request');
$actionSetStatusSubmitted = new SetVariableValueAction(
    'Set Request Status to Submitted',
    'request.status',
    'submitted'
);
$eventSubmit->addAction($actionSetStatusSubmitted);
$wf->addEvent($eventSubmit);

// Approve Event
$eventApprove = new Event('id:approve', 'Approve Request');
$actionSetStatusApproved = new SetVariableValueAction(
    'Set Request Status to Approved',
    'request.status',
    'approved'
);
$eventApprove->addAction($actionSetStatusApproved);
$wf->addEvent($eventApprove);

// Reject Event
$eventReject = new Event('id:reject', 'Reject Request');
$actionSetStatusRejected = new SetVariableValueAction(
    'Set Request Status to Rejected',
    'request.status',
    'rejected'
);
$eventReject->addAction($actionSetStatusRejected);
$wf->addEvent($eventReject);

// Timer Events (system-generated)
$eventReminderTick = new Event('id:reminderTick', 'Reminder Tick');
$actionSendReminder = new SetVariableValueAction(
    'Send Reminder',
    'audit.log',
    'Reminder sent'
);
$eventReminderTick->addAction($actionSendReminder);
$wf->addEvent($eventReminderTick);

$eventEscalate = new Event('id:escalate', 'Escalate Request');
$actionSetStatusEscalated = new SetVariableValueAction(
    'Set Request Status to Escalated',
    'request.status',
    'escalated'
);
$eventEscalate->addAction($actionSetStatusEscalated);
$wf->addEvent($eventEscalate);

$eventExpire = new Event('id:expire', 'Expire Request');
$actionSetStatusExpired = new SetVariableValueAction(
    'Set Request Status to Expired',
    'request.status',
    'expired'
);
$eventExpire->addAction($actionSetStatusExpired);
$wf->addEvent($eventExpire);

// ========================================
// TRANSITIONS DEFINITION
// ========================================

// Draft → PendingApproval (on submit)
$transitionSubmit = new Transition(
    'id:submit-transition',
    'Submit for Approval',
    'id:draft',
    'id:pending-approval'
);
$transitionSubmit->addCondition(new Condition(
    'request.status',
    'equals',
    'submitted'
));
$wf->addTransition($transitionSubmit);

// PendingApproval → Approved (on approve)
$transitionApprove = new Transition(
    'id:approve-transition',
    'Approve Request',
    'id:pending-approval',
    'id:approved'
);
$transitionApprove->addCondition(new Condition(
    'request.status',
    'equals',
    'approved'
));
$wf->addTransition($transitionApprove);

// PendingApproval → Rejected (on reject)
$transitionReject = new Transition(
    'id:reject-transition',
    'Reject Request',
    'id:pending-approval',
    'id:rejected'
);
$transitionReject->addCondition(new Condition(
    'request.status',
    'equals',
    'rejected'
));
$wf->addTransition($transitionReject);

// PendingApproval → Escalated (on escalate)
$transitionEscalate = new Transition(
    'id:escalate-transition',
    'Escalate Request',
    'id:pending-approval',
    'id:escalated'
);
$transitionEscalate->addCondition(new Condition(
    'request.status',
    'equals',
    'escalated'
));
$wf->addTransition($transitionEscalate);

// PendingApproval → AutoExpired (on expire)
$transitionExpireFromPending = new Transition(
    'id:expire-from-pending-transition',
    'Expire from Pending',
    'id:pending-approval',
    'id:auto-expired'
);
$transitionExpireFromPending->addCondition(new Condition(
    'request.status',
    'equals',
    'expired'
));
$wf->addTransition($transitionExpireFromPending);

// Escalated → Approved (backup approver approves)
$transitionEscalatedApprove = new Transition(
    'id:escalated-approve-transition',
    'Approve Escalated Request',
    'id:escalated',
    'id:approved'
);
$transitionEscalatedApprove->addCondition(new Condition(
    'request.status',
    'equals',
    'approved'
));
$wf->addTransition($transitionEscalatedApprove);

// Escalated → Rejected (backup approver rejects)
$transitionEscalatedReject = new Transition(
    'id:escalated-reject-transition',
    'Reject Escalated Request',
    'id:escalated',
    'id:rejected'
);
$transitionEscalatedReject->addCondition(new Condition(
    'request.status',
    'equals',
    'rejected'
));
$wf->addTransition($transitionEscalatedReject);

// Escalated → AutoExpired (hard deadline reached)
$transitionExpireFromEscalated = new Transition(
    'id:expire-from-escalated-transition',
    'Expire from Escalated',
    'id:escalated',
    'id:auto-expired'
);
$transitionExpireFromEscalated->addCondition(new Condition(
    'request.status',
    'equals',
    'expired'
));
$wf->addTransition($transitionExpireFromEscalated);

// Stay in PendingApproval on reminder (no state change, just action execution)
// This would typically be handled by the timer system, but we model it as a self-transition
// that doesn't change state but allows reminder actions to execute

return $wf;