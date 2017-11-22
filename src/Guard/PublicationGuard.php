<?php

/**
 * @file
 * Contains \Drupal\my_workflow\Guard\PublicationGuard.
 */

namespace Drupal\my_workflow\Guard;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\Core\Entity\EntityInterface;
use Drupal\state_machine\WorkflowManagerInterface;

class PublicationGuard implements GuardInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The workflow manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;

  /**
   * Constructs a new PublicationGuard object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user..
   */
  public function __construct(AccountProxyInterface $current_user, WorkflowManagerInterface $workflow_manager) {
    $this->currentUser = $current_user;
    $this->workflowManager = $workflow_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    // Don't allow transition for users without permissions.
    $transition_id = $transition->getId();
    $workflow_id = $workflow->getId();
    if (!$this->currentUser->hasPermission('use ' . $transition_id . ' transition in ' . $workflow_id)) {
      return FALSE;
    }
  }

}