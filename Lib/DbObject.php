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

    /**
     * @return array[mixed]
     */
    private function getIdentifier()
    {
        return [
            'column' => self::$idColumn,
            'value'  => $this->id
        ];
    }

    /**
     * @return \Exception
     */
    public function exception()
    {
        return $this->exception;
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
            throw new \Exception(get_class($this).' extends DbObject, but no fields are defined');
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
                throw new \Exception(sprintf('No record in table `%s` where %s = %s', $table, $id['column'], $id['value']));
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
     * @return bool
     */
    public function save()
    {
        $id  = $this->getIdentifier();
        $db  = $this->createZendDb();
        $sql = new \Zend\Db\Sql\Sql($db);

        if ($this->id === null) {
            $query = $sql->insert()
                ->into(self::getTable())
                ->values($this->fields)
            ;
        } else {
            $query = $sql->update()
                ->table(self::getTable())
                ->set($this->fields)
                ->where([$id['column'] => $id['value']])
            ;
        }

        try {
            $stmt   = $sql->prepareStatementForSqlObject($query);
            $result = $stmt->execute();

            if ($this->id === null) {
                $inserted_id = $result->getGeneratedValue();
                $this->id    = $inserted_id;

                if (isset($this->fields[$id['column']])) {
                    $this->fields[$id['column']] = $inserted_id;
                }
            }
        } catch (\Exception $e) {
            $this->exception = $e;

            return false;
        }

        return true;
    }
}

