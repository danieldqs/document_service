<?php

namespace App\Domain;

/**
 * Class AbstractRepository
 * @package App\Domain
 */
abstract class AbstractRepository
{
  /**
   * Allow a default model to be set for get model function
   */
  const DEFAULT_MODEL = "";

  /**
   * @var
   */
  protected $model;

  /**
   * @var int
   */
  protected $findAllLimit = 100;

  /**
   * @var string[]
   */
  protected $models = [];

  /**
   * @param array $filters
   * @param int $limit
   * @param int $offset
   * @return array
   */
  public function findAll (array $filters = [], int $limit = 0, int $offset = 0): array
  {
    return $this->getModel()->findAll($filters, $limit, $offset);
  }

  /**
   * @return AbstractModel
   */
  public function getModel (string $name = "")
  {
    if ( !$name && $this::DEFAULT_MODEL ) {
      $name = $this::DEFAULT_MODEL;
    }
    $m = $this->models[$name] ?? false;
    if ( $m ) {
      return new $m();
    }
    throw new \Exception("Unknown Model $name");
  }

  /**
   * @param int $id
   */
  public function findById (int $id)
  {
    // TODO: Implement findUserOfId() method.
  }

  /**
   * @param int $id
   * @return bool
   */
  public function deleteById (int $id): bool
  {
    return $this->getModel()->deleteById($id);
  }

  /**
   * @param array $args
   * @return mixed
   */
  public function create (array $args)
  {
    return $this->getModel()->store($args)->getId();
  }

  /**
   * @param int $id
   * @param array $args
   * @return bool
   */
  public function updateById (int $id, array $args): bool
  {
    $args[$this->getModel()->getIdField()] = $id;
    return $this->getModel()->store($args);
  }
}