<?php

// require_once __DIR__ . '/../../../vendor/autoload.php';

use Xavante\Actions\MakeHttpRequestAction;
use Xavante\Actions\SetVariableValueAction;
use Xavante\Models\Domain\{Workflow, State, Transition, Variable, Condition, Event};


$wf = new Workflow(
    [
        'name' => 'simple-approval-v1',
        'description' => 'A simple approval workflow example.'
    ]
);


$pendingApprovalState = new State('id:pending-approval', 'Pending Approval');

$actionRequestAssignReviewer = new MakeHttpRequestAction();
$actionRequestAssignReviewer->setCaller($pendingApprovalState);
$actionRequestAssignReviewer->configure([
    'method' => 'POST',
    'url' => 'https://www.google.com/assign-reviewer',
    'headers' => ['Content-Type' => 'application/json'],
    'body' => json_encode(['documentId' => '{{process.documentId}}']),
    'dry_run' => true
],[
    'status_code' => 403,
    'reason_phrase' => 'Forbidden',
    'headers' => ['Content-Type' => ['application/json']],
    'contents' => '{"error": "Reviewer assignment failed in dry run mode"}'
]);

$actionAuditExitPendingApproval = new SetVariableValueAction(
    'Audit Exit Pending Approval',
    'audit.log',
    'Exited Pending Approval State'
);


$pendingApprovalState->addEntryAction($actionRequestAssignReviewer);
$pendingApprovalState->addExitAction($actionAuditExitPendingApproval);



$wf->addState(new State('id:draft', 'Draft', 'initial'));
$wf->addState($pendingApprovalState);
$wf->addState(new State('id:approved', 'Approved', 'final'));
$wf->addState(new State('id:rejected', 'Rejected', 'final'));


$wf->setInitialStatesIds(['id:draft']);


$wf->addVariable(new Variable('document.status', 'Document Status', 'string'));


$eventSubmit = new Event('id:document_submitted', 'Document Submitted');

$actionSetStatusSubmitted = new SetVariableValueAction(
    'Set Document Status to Submitted',
    'document.status',
    'submitted'
);

$wf->addEvent($eventSubmit);

$eventSubmit->addAction($actionSetStatusSubmitted);



$transitionDraftToPendingApproval = new Transition(
    'id:submit',
    'Submit for Approval',
    'id:draft',
    'id:pending-approval'
);

$transitionDraftToPendingApproval->addCondition(
    new Condition(
        'document.status',
        'equals',
        'submitted'
    )
);

$wf->addTransition($transitionDraftToPendingApproval);




$transitionPendingApprovalToApproved = new Transition(
    'id:approve',
    'Approve',
    'id:pending-approval',
    'id:approved'
);






$eventApprove = new Event('id:approve_document', 'Approve Document');

$actionSetStatusApproved = new SetVariableValueAction(
    'Set Document Status to Approved',
    'document.status',
    'approved'
);

$wf->addEvent($eventApprove);
$eventApprove->addAction($actionSetStatusApproved);



$conditionApproveDocument = new Condition(
    'document.status',
    'equals',
    'approved'
);

$transitionPendingApprovalToApproved->addCondition($conditionApproveDocument);

$conditionUserRoleIsManager = new Condition(
    'user.role',
    'equals',
    'manager'
);
$transitionPendingApprovalToApproved->addCondition($conditionUserRoleIsManager);






$transitionPendingApprovalToApproved->addCondition(
    new Condition(
        'user.role',
        'equals',
        'manager'
    )
);

$wf->addTransition($transitionPendingApprovalToApproved);

$wf->addTransition(new Transition(
    'id:reject',
    'Reject',
    'id:pending-approval',
    'id:rejected'
));


// print_r($wf->jsonSerialize());

return $wf;
