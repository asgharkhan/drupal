<?php

// @codingStandardsIgnoreStart
// @todo: Move this back to \Drupal\Core\Config in #2991683.
// Use this class with its class alias Drupal\Core\Config\ExportStorageManager
// @codingStandardsIgnoreEnd
namespace Drupal\config_environment\Core\Config;

use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\ReadOnlyStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The export storage manager dispatches an event for the export storage.
 *
 * @internal
 */
class ExportStorageManager implements StorageManagerInterface {

  use StorageCopyTrait;

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $active;

  /**
   * The database storage.
   *
   * @var \Drupal\Core\Config\DatabaseStorage
   */
  protected $storage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * ExportStorageManager constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $active
   *   The active config storage to prime the export storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(StorageInterface $active, Connection $connection, EventDispatcherInterface $event_dispatcher) {
    $this->active = $active;
    $this->eventDispatcher = $event_dispatcher;
    // The point of this service is to provide the storage and dispatch the
    // event when needed, so the storage itself can not be a service.
    $this->storage = new DatabaseStorage($connection, 'config_export');
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {
    self::replaceStorageContents($this->active, $this->storage);
    // @todo: Use ConfigEvents::STORAGE_TRANSFORM_EXPORT in #2991683
    $this->eventDispatcher->dispatch('config.transform.export', new StorageTransformEvent($this->storage));

    return new ReadOnlyStorage($this->storage);
  }

}
