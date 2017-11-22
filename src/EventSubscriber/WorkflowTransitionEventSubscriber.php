<?php

namespace Drupal\my_workflow\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\my_workflow\WorkflowHelperInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowState;
use Drupal\state_machine_workflow\RevisionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to handle revisions on workflow-enabled entities.
 */
class WorkflowTransitionEventSubscriber implements EventSubscriberInterface {

  /**
   * The workflow helper.
   *
   * @var \Drupal\my_workflow\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * Constructs a new WorkflowTransitionEventSubscriber object.
   *
   * @param \Drupal\my_workflow\WorkflowHelperInterface $workflowHelper
   *   The workflow helper.
   */
  public function __construct(WorkflowHelperInterface $workflowHelper) {
    $this->workflowHelper = $workflowHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'state_machine.pre_transition' => 'handleAction',
    ];
  }

  /**
   * handle action based on the workflow.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The state change event.
   */
  public function handleAction(WorkflowTransitionEvent $event) {
    $entity = $event->getEntity();

    // Verify if the new state is marked as published state.
    $is_published_state = $this->isPublishedState($event->getToState(), $event->getWorkflow());

    $fields = $this->workflowHelper->getEntityStateField($entity);

    if ($entity instanceof EntityPublishedInterface) {
      if ($is_published_state) {
        $entity->setPublished();
      }
      else {
        $entity->setUnpublished();
      }

    }
  }

  /**
   * Checks if an entity has a published default revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity has a published default revision, FALSE otherwise.
   */
  protected function hasPublishedDefaultRevision(ContentEntityInterface $entity) {
    // New entities don't have revisions, obviously.
    if ($entity->isNew()) {
      return FALSE;
    }

    $default_revision = $this->revisionManager->loadDefaultRevision($entity);
    // The entity needs to implement the published interface, or of course it's
    // not published.
    if (!$default_revision instanceof EntityPublishedInterface) {
      return FALSE;
    }

    return $default_revision->isPublished();
  }

  /**
   * Checks if a state is set as published in a certain workflow.
   *
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowState $state
   *   The state to check.
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow
   *   The workflow the state belongs to.
   *
   * @return bool
   *   TRUE if the state is set as published in the workflow, FALSE otherwise.
   */
  protected function isPublishedState(WorkflowState $state, WorkflowInterface $workflow) {
    return $this->workflowHelper->isWorkflowStatePublished($state->getId(), $workflow);
  }

  /**
   * Checks if a state is set as default revision in a certain workflow.
   *
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowState $state
   *   The state to check.
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow
   *   The workflow the state belongs to.
   *
   * @return bool
   *   TRUE if the state is set as default revision in the workflow, FALSE otherwise.
   */
  protected function isDefaultRevisionState(WorkflowState $state, WorkflowInterface $workflow) {
    return $this->workflowHelper->isWorkflowStateDefaultRevision($state->getId(), $workflow);
  }

}
