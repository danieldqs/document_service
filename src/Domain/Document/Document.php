<?php

declare(strict_types=1);

namespace App\Domain\Document;

use App\Domain\AbstractModel;

class Document extends AbstractModel
{

  /**
   * Indicates if the model should be timestamped.
   *
   * @var bool
   */
  public $timestamps = false;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'document';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name',
    'type',
    'created_at',
  ];

  /**
   * @var array
   */
  protected $columns = [
    'id' => [
      'type' => 'int'
    ],
    'name' => [
      'type' => 'string',
      'required' => true
    ],
    'type' => [
      'type' => 'int',
      'required' => true
    ],
    'created_at' => [
      'type' => 'string',
      'required' => false
    ],
  ];

}
