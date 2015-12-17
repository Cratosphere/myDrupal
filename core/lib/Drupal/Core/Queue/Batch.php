<?php

/**
 * @file
 * Contains \Drupal\Core\Queue\Batch.
 */

namespace Drupal\Core\Queue;

/**
 * Defines a batch queue handler used by the Batch API.
 *
 * This implementation:
 * - Ensures FIFO ordering.
 * - Allows an item to be repeatedly claimed until it is actually deleted (no
 *   notion of lease time or 'expire' date), to allow multipass operations.
 *
 * Stale items from failed batches are cleaned from the {queue} table on cron
 * using the 'created' date.
 *
 * @ingroup queue
 */
class Batch extends DatabaseQueue {

  /**
   * Overrides \Drupal\Core\Queue\DatabaseQueue::claimItem().
   *
   * Unlike \Drupal\Core\Queue\DatabaseQueue::claimItem(), this method provides
   * a default lease time of 0 (no expiration) instead of 30. This allows the
   * item to be claimed repeatedly until it is deleted.
   */
  public function claimItem($lease_time = 0) {
    $item = $this->connection->queryRange('SELECT data, item_id FROM {queue} q WHERE name = :name ORDER BY item_id ASC', 0, 1, array(':name' => $this->name))->fetchObject();
    if ($item) {
      $item->data = unserialize($item->data);
      return $item;
    }
    return FALSE;
  }

  /**
   * Retrieves all remaining items in the queue.
   *
   * This is specific to Batch API and is not part of the
   * \Drupal\Core\Queue\QueueInterface.
   *
   * @return array
   *   An array of queue items.
   */
  public function getAllItems() {
    $result = array();
    $items = $this->connection->query('SELECT data FROM {queue} q WHERE name = :name ORDER BY item_id ASC', array(':name' => $this->name))->fetchAll();
    foreach ($items as $item) {
      $result[] = unserialize($item->data);
    }
    return $result;
  }
}
