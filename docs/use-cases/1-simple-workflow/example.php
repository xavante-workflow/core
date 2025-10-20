<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use Xavante\Models\Domain\{Workflow, State, Transition, Variable, Condition};


$wf = new Workflow(
    [
        'name' => 'simple-approval-v1',
        'description' => 'A simple approval workflow example.'
    ]
);

$wf->addState(new State('id:draft', 'Draft', 'initial'));
$wf->addState(new State('id:pending-approval', 'Pending Approval'));
$wf->addState(new State('id:approved', 'Approved', 'final'));
$wf->addState(new State('id:rejected', 'Rejected', 'final'));


$transitionDraftToPendingApproval = new Transition(
    'submit',
    'Submit for Approval',
    'id:draft',
    'id:pending-approval'
);

$wf->addTransition($transitionDraftToPendingApproval);


$transitionPendingApprovalToApproved = new Transition(
    'approve',
    'Approve',
    'id:pending-approval',
    'id:approved'
);

$transitionPendingApprovalToApproved->addCondition(
    new Condition(
        'user.role',
        'equals',
        'manager'
    )
);

$wf->addTransition($transitionPendingApprovalToApproved);

$wf->addTransition(new Transition(
    'reject',
    'Reject',
    'id:pending-approval',
    'id:rejected'
));



