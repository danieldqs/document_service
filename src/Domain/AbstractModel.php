<?php

namespace App\Domain;

use App\Infrastructure\Environment as Env;
use App\Infrastructure\Persistence\DB;
use JsonSerializable;
use App\Domain\Oauth2;
use App\Domain\DomainException;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class AbstractModel
 * @package App\Domain
 */
class AbstractModel extends Eloquent implements JsonSerializable
{
  /**
   * Allow for child classes to override the default
   * resource name via a constant
   * @const string
   */
  const NAME = "";

  /**
   * Allow for override of id field name
   */
  const ID_FIELD = "id";

  /**
   * @var array
   */
  protected $columns = [];

  /**
   * @var array
   */
  protected $data = [];

  /**
   * @var array
   */
  protected $errors = [];

  /**
   * @var array
   */
  protected $response_column_blacklist = [];
  /**
   * Maintain list of associated models
   * @var array
   */
  protected $children = [];

  /**
   * @return string
   * @ToDo Refactor class to only use static method for get name
   * @throws \ReflectionException
   */

  public static function getStaticName (): string
  {

    $cls = get_called_class();
    if ( $cls::NAME ) {
      return $cls::NAME;
    }

    return strtolower((new \ReflectionClass($cls))->getShortName());
  }

  /**
   * @param string|null $k
   * @param null $def
   * @return array|mixed|null
   */
  public function getData (string $k = null, $def = null)
  {
    if ( $k ) {
      return $this->data[$k] ?? $def;
    }

    return $this->data;
  }

  /**
   * @TODO SETDATA WILL NOT SAVE DATA TO DB
   * @param array $data
   * @return array
   */
  public function setData (array $data)
  {
    if ( $data ) {
      foreach ($data as $k => $v) {
        $this->data[$k] = $v;
      }
    }

    return $this;
  }

  /**
   * @return mixed
   * @throws \Exception
   */
  public function getDB ()
  {
    if ( !Env::getValue('DB_ORM') ) {
      throw new \Exception('No default ORM set');
    }

    return DB::getConnection(Env::getValue('DB_ORM'));
  }

  /**
   * Allow override of RedBean store method for save
   * @param $tbl
   * @return mixed
   */
  public function saveEntity ($tbl)
  {
    return $this->getDB()->save($tbl);
  }

  /**
   * After save hook.
   */
  public function afterSave ()
  {
  }

  /**
   * @return string
   * @throws \ReflectionException
   */
  public function getName (): string
  {
    if ( $this::NAME ) {
      $name = $this::NAME;
    } else {
      $name = (new \ReflectionClass($this))->getShortName();
    }

    return $this->formatTblName($name);
  }

  /**
   * @param string $name
   * @return string
   */
  public function formatTblName (string $name): string
  {
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
  }

  /**
   * After a successful load, allow a model to perform an action
   */
  public function afterLoad ($id)
  {
  }

  /**
   * @param string $alias
   * @param array $blacklist
   * @return array
   */
  public function getColumnNames (string $alias = "", array $blacklist = []): array
  {
    $cols = [];
    foreach ($this->columns as $name => $data) {
      if ( in_array($name, $blacklist) ) {
        continue;
      }
      $label = $name;
      if ( $alias ) {
        $label = sprintf("%s.%s", $alias, $label);
      }

      $cols[] = $label;
    }

    return $cols;
  }

  /**
   * @return false|string
   */
  public function getCalledClass ()
  {
    return get_class($this);
  }

  /**
   * @param array $filters
   * @return $this
   * @throws \ReflectionException
   */
  public function findOne (array $filters): AbstractModel
  {
    $records = $this->findAll($filters);
    if ( count($records) > 1 ) {
      throw new \Exception('multiple records found for findOne');
    }

    if ( !$records ) {
      throw new \Exception('no records found');
    }

    $this->data = $records[0] ?? [];

    return $this;
  }

  /**
   * @param array $filters
   * @param int $limit
   * @param int $offset
   * @return array
   */
  public function findAll (array $filters = [], int $limit = 0, int $offset = 0): array
  {

    if ( !$filters ) {
      $result = $this::all();
    } else {
      $result = $this::where($filters)->get();
    }

    if ( !$limit ) {
      $limit = $this->getMaxLimit();
    }
    $result = $result->skip($offset)->take($limit);

    return $result->toArray();
  }

  /**
   * @return int
   */
  public function getMaxLimit ()
  {
    return DB::getFindAllLimit();
  }

  /**
   * @return string
   * @throws \ReflectionException
   */
  public function getSelect (array $cols = ["*"]): string
  {
    return sprintf("SELECT %s FROM %s", implode(",", $cols), $this->getName());
  }

  /**
   * @param array $filters
   * @return array
   * @throws \Exception
   */
  public function applyFilters (string $sql, array $filters): string
  {
    $clauses = [];
    if ( $filters ) {
      foreach ($filters as $key => $value) {
        if ( $this->canFilter($key) ) {
          if ( is_array($value) ) {
            $clauses[] = $key . ' ' . $value[0] . ' ' . $this->sanitizeValue($value[1]);
          } else {
            $clauses[] = $key . ' = ' . $this->sanitizeValue($value);
          }
        }
      }
    }
    if ( $clauses ) {
      $sql .= ' WHERE ' . implode(" AND ", $clauses);
    }
    return $sql;
  }

  /**
   * @param string $filter
   * @return bool
   */
  public function canFilter (string $filter): bool
  {
    if ( isset($this->getColumns()[$filter]) ) {
      return true;
    }
    return in_array($filter, $this->getColumns(), true) !== false;
  }

  /**
   * @param $value
   * @TODO add a model validation exception handler
   * @throws \Exception
   */
  public function sanitizeValue ($value): string
  {
    $value = strip_tags($value);
    $value = htmlentities($value, ENT_QUOTES, 'UTF-8');

    $string_length = 256;

    if ( strlen($value) > $string_length ) {
      throw new \Exception('invalid string length value');
    }
    $value = substr($value, 0, $string_length);
    $value = "'" . $value . "'";

    return $value;
  }

  /**
   * @param string $sql
   * @param int $limit
   * @param $offset
   * @return string
   */
  public function applyLimit (string $sql, int $limit = 0, $offset = 0): string
  {
    $max = $this->getMaxLimit();
    if ( !$limit || $limit > $max ) {
      $limit = $max;
    }

    if ( $limit ) {
      $sql .= sprintf(' LIMIT %s,%s', $offset, $limit);
    }

    return $sql;
  }

  /**
   * @param $value
   * @param $castType
   */
  public function typeCastValue (&$value, $castType): void
  {
    $castTypes = [
      'int',
      'integer',
      'bool',
      'boolean',
      'array',
      'object',
      'string'
    ];

    if ( in_array($castType, $castTypes, true) !== false ) {
      if(is_scalar($value)) {
        settype($value, $castType);
      }
    }
  }

  public function validateColumn (array $data, $column_data, string $column): void
  {
    if ( isset($column_data['validate']) ) {
      if ( strpos($column_data['validate'], '|') !== false ) {
        $column_data['validate'] = explode("|", $column_data['validate']);
      } else {
        $column_data['validate'] = (array)$column_data['validate'];
      }
      $rules = [];
      foreach ($column_data['validate'] as $validate) {
        if ( strpos($validate, ':') !== false ) {
          $rules[] = explode(":", $validate);
        }
      }

      if ( $rules ) {
        foreach ($rules as $rule) {

          if ( !isset($data[$column]) ) {
            $this->errors[] = $column . ' is a required field';
            continue;
          }

          switch ($rule[0]) {
            case "maxlength":
              if ( strlen($data[$column]) > $rule[1] ) {
                $this->errors[] = "Max length exceeded for $column";
              }
              break;
            case "minlength":
              if ( strlen($data[$column]) < $rule[1] ) {
                $this->errors[] = "$column requires minimum length of " . $rule[1];
              }
              break;
          }
        }
      }
    }
  }

  /**
   * @param array $data
   */
  public function validate (array $data): void
  {

    if ( !$this->isLoaded() ) {
      $data = $this->populate($data);
    }

    $columns = $this->getColumns();

    foreach ($data as $k => $v) {

      $column_data = $columns[$k] ?? null;

      if ( isset($column_data) ) {

        //cast the values to the accepted type
        if ( isset($column_data['type'], $v) ) {
          $this->typeCastValue($v, $column_data['type']);
        }

        //validate the dolumns
        $this->validateColumn($data, $column_data, $k);
      }
    }
  }

  /**
   * @return bool
   */
  public function isLoaded (): bool
  {
    return $this->getId() !== false;
  }

  /**
   * @return false|mixed
   */
  public function getId ()
  {
    $idField = self::ID_FIELD;
    $id = $this->data[$idField] ?? $this->$idField ?? false;
    if ( $id ) {
      $id = (int)$id;
    }
    return $id;
  }

  /**
   * @param array $data
   * @return array
   */
  public function populate (array $data): array
  {
    $values = $this->data;
    foreach ($this->getColumns() as $column => $column_data) {

      //if there is no validation supplied for the columns the column will be actually $column_data
      if ( !is_array($column) && !is_array($column_data) ) {
        $column = $column_data;
      }

      if ( isset($data[$column]) ) {
        $values[$column] = $data[$column];
      }
    }

    return $values;
  }

  /**
   * @return array
   */
  public function getColumns (): array
  {
    return $this->columns;
  }

  /**
   * Allow children to alter values prior to db insert and update
   * @param array $values
   * @return array
   */
  public function beforeSave (array $values, array $raw): array
  {
    return $values;
  }

  /**
   * @return mixed
   * @throws \Exception
   */
  public function getTableModel ()
  {
    if ( !$this->getId() ) {
      return $this;
    }
    return $this::find($this->getId());
  }

  /**
   * @param array $data
   */
  public function store(array $data)
  {

    $result = $this->getTableModel();

    $this->validate($data);

    if ( $this->errors ) {
      throw new DomainException(implode("\n", $this->errors));
    }

    $values = $this->beforeSave($this->populate($data), $data);

    foreach ($values as $field => $value) {
      $result->$field = $value;
      $this->data[$field] = $value;
    }

    try {
      $result->save();
      $retval = $result->getId();
    } catch (\Exception $e) {
      throw new DomainException($e->getMessage());
    }

    $this->data[$this::ID_FIELD] = $retval;
    $this->afterSave($retval);

    return $this;
  }

  /**
   * @param array|string $id
   * @param string $idField
   * @return AbstractModel
   */
  public function load ($id, $idField = self::ID_FIELD): AbstractModel
  {
    $result = $this::where([$idField => $id]);
    if ( $result ) {
      $this->data = $result->get()->first()->toArray();
    }

    if ( $this->isLoaded() ) {
      $this->afterLoad($id);
    }
    return $this;
  }

  /**
   * @param int $id
   */
  public function deleteById (int $id)
  {
    return $this->deleteBy([self::ID_FIELD => $id]);
  }

  /**
   * @param array $data
   * @return mixed
   */
  public function deleteBy (array $data)
  {
    $result = $this::where($data);
    return $result->delete();
  }

  /**
   * @return array
   */
  public function jsonSerialize (): array
  {
    $json = [];
    foreach ($this->data as $k => $v) {
      if ( in_array($k, $this->response_column_blacklist, true) !== false ) {
        continue;
      }
      $json[$k] = $v;
    }
    foreach ($this->children as $name => $child) {
      $json[$name] = $child;
    }
    return $json;
  }

  /**
   * @return string
   */
  public function getIdField (): string
  {
    return $this::ID_FIELD;
  }

  /**
   * @return int
   */
  public function getCount (): int
  {
    return $this::all()->count();
  }
}