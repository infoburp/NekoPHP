<?php

namespace NekoPHP;

/**
 * @author Patrick Spek <p.spek@tyil.nl>
 * @package NekoPHP
 * @license BSD 3-clause license
 */
abstract class DbObject
{
    use Traits\ZendDb;

    /**
     * @var string
     */
    protected static $idColumn = 'id';

    /**
     * @var array[string => array[string]]
     */
    protected static $relations = [];

    /**
     * @var string
     */
    protected static $table;

    /**
     * @var array[string => mixed]
     */
    protected $fields = [];

    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var array[string => \NekoPHP\DbObject]
     */
    private $relation_models;

    /**
     * @return array[string]
     */
    protected static function getColumns()
    {
        return static::$columns;
    }

    /**
     * @return string
     */
    protected static function getTable()
    {
        if (static::$table !== null) {
            return static::$table;
        }

        $parts      = explode('\\', get_called_class());
        $class_name = $parts[count($parts) - 1];
        $table      = strtolower($class_name).'s';

        return $table;
    }

    protected function relationOneToOne($relation_id)
    {
        if (!isset($this->relation_models[$relation_id])) {
            $model = static::$relations[$relation_id][0];

            $this->relation_models[$relation_id] = new $model($this->id);
        }

        return $this->relation_models[$relation_id];
    }

    /**
     * @return array[mixed]
     */
    private function getIdentifier()
    {
        return [
            'column' => static::$idColumn,
            'value'  => $this->id
        ];
    }

    /**
     * @param int $idValue
     * @return \NekoPHP\DbObject
     */
    public function __construct($idValue = null)
    {
        $this->id = $idValue;
        $columns  = $this->getColumns();
        $id       = $this->getIdentifier();

        if ($columns === []) {
            throw new \Exception(get_class($this).' extends DbObject, but no fields are defined', 1);
        }

        if ($id['value'] === null) {
            foreach ($columns as $column) {
                $this->fields[$column] = null;
            }
        } else {
            $db      = self::createZendDb();
            $sql     = new \Zend\Db\Sql\Sql($db);
            $query   = $sql->select()
                ->from(self::getTable())
                ->columns($columns)
                ->where([$id['column'] => $id['value']])
            ;
            $stmt    = $sql->prepareStatementForSqlObject($query);
            $results = $stmt->execute();

            if ($results->count() != 1) {
                throw new \Exception(sprintf('No record in table `%s` where %s = %s', self::getTable(), $id['column'], $id['value']), 2);
            }

            $row = $results->next();

            foreach ($columns as $column) {
                $this->fields[$column] = $row[$column];
            }
        }
    }

    /**
     * @return bool
     */
    public function create()
    {
        $id  = $this->getIdentifier();
        $db  = $this->createZendDb();
        $sql = new \Zend\Db\Sql\Sql($db);

        $query = $sql->insert()
            ->into(self::getTable())
            ->values($this->fields)
        ;

        try {
            $stmt   = $sql->prepareStatementForSqlObject($query);
            $result = $stmt->execute();

            $inserted_id = $result->getGeneratedValue();
            $this->id    = $inserted_id;

            if (isset($this->fields[$id['column']])) {
                $this->fields[$id['column']] = $inserted_id;
            }

            foreach (static::$relations as $relation) {
                if (isset($relation[2]) && $relation[2]) {
                    $model = new $relation[0]();
                    $model->$relation[1]($inserted_id);
                    $model->create();
                }
            }
        } catch (\Exception $e) {
            $this->exception = $e;

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function delete()
    {
        $id     = $this->getIdentifier();
        $db     = $this->createZendDb();
        $sql    = new \Zend\Db\Sql\Sql($db);
        $query  = $sql->delete()
            ->from(self::getTableName())
            ->where([$id['column'] => $id['value']])
        ;
        $stmt   = $sql->prepareStatementForSqlObject($query);
        $result = $stmt->execute();

        return ($result->getAffectedRows() > 0);
    }

    /**
     * @return \Exception
     */
    public function exception()
    {
        return $this->exception;
    }

    /**
     * @return bool
     */
    public function update()
    {
        $id    = $this->getIdentifier();
        $db    = $this->createZendDb();
        $sql   = new \Zend\Db\Sql\Sql($db);
        $query = $sql->update()
            ->table(self::getTable())
            ->set($this->fields)
            ->where([$id['column'] => $id['value']])
        ;

        try {
            $stmt   = $sql->prepareStatementForSqlObject($query);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->exception = $e;

            return false;
        }

        return true;
    }
}

