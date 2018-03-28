<?php

namespace Swoft\Db;

use Swoft\App;
use Swoft\Db\Bean\Collector\EntityCollector;
use Swoft\Db\Exception\DbException;
use Swoft\Db\Exception\MysqlException;
use Swoft\Db\Helper\DbHelper;
use Swoft\Db\Helper\EntityHelper;
use Swoft\Core\ResultInterface;

/**
 * 查询器
 */
class QueryBuilder implements QueryBuilderInterface
{
    /**
     * 升序
     */
    const ORDER_BY_ASC = 'ASC';

    /**
     * 降序
     */
    const ORDER_BY_DESC = 'DESC';

    /**
     * 等于
     */
    const OPERATOR_EQ = '=';

    /**
     * 不等于
     */
    const OPERATOR_NE = '!=';

    /**
     * 小于
     */
    const OPERATOR_LT = '<';

    /**
     * 小于等于
     */
    const OPERATOR_LTE = '<=';

    /**
     * 大于
     */
    const OPERATOR_GT = '>';

    /**
     * 大于等于
     */
    const OPERATOR_GTE = '>=';

    /**
     * 左括号
     */
    const BRACKET_OPEN = '(';

    /**
     * 右括号
     */
    const BRACKET_CLOSE = ')';

    /**
     * 修饰符in
     */
    const IN = 'IN';

    /**
     * 修饰符not in
     */
    const NOT_IN = 'NOT IN';

    /**
     * 修饰符like
     */
    const LIKE = 'LIKE';

    /**
     * 修饰符in
     */
    const NOT_LIKE = 'NOT LIKE';

    /**
     * 修饰符between
     */
    const BETWEEN = 'BETWEEN';

    /**
     * 修饰符not between
     */
    const NOT_BETWEEN = 'NOT BETWEEN';

    /**
     * 内连接
     */
    const INNER_JOIN = 'INNER JOIN';

    /**
     * 左连接
     */
    const LEFT_JOIN = 'LEFT JOIN';

    /**
     * 右连接
     */
    const RIGHT_JOIN = 'RIGHT JOIN';

    /**
     * 逻辑运算符and
     */
    const LOGICAL_AND = 'AND';

    /**
     * 逻辑运算符or
     */
    const LOGICAL_OR = 'OR';

    /**
     * is判断语句
     */
    const IS = 'IS';

    /**
     * is not 判断语句
     */
    const IS_NOT = 'IS NOT';

    /**
     * 插入表名
     *
     * @var string
     */
    private $insert = '';

    /**
     * 更新表名
     *
     * @var string
     */
    private $update = '';

    /**
     * 是否是delete
     *
     * @var bool
     */
    private $delete = false;

    /**
     * select语句
     *
     * @var array
     */
    private $select = [];

    /**
     * set语句
     *
     * @var array
     */
    private $set = [];


    /**
     * from语句
     *
     * @var array
     */
    private $from = [];

    /**
     * join语句
     *
     * @var array
     */
    private $join = [];

    /**
     * where语句
     *
     * @var array
     */
    private $where = [];

    /**
     * group by语句
     *
     * @var array
     */
    private $groupBy = [];

    /**
     * having语句
     *
     * @var array
     */
    private $having = [];

    /**
     * order by 语句
     *
     * @var array
     */
    private $orderBy = [];

    /**
     * limit 语句
     *
     * @var array
     */
    protected $limit = [];

    /**
     * 参数集合
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * @var AbstractDbConnection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $lastSql;

    /**
     * @var array
     */
    protected $table = [];

    /**
     * @var string
     */
    protected $instance = Pool::INSTANCE;

    /**
     * @var string
     */
    protected $node = '';

    /**
     * Selected database
     *
     * @var string
     */
    protected $db = '';

    /**
     * @return QueryBuilder
     * @throws DbException
     */
    public function insert(): QueryBuilder
    {
        $this->insert = $this->getTableName();

        return $this;
    }

    /**
     * @return QueryBuilder
     * @throws DbException
     */
    public function update(): QueryBuilder
    {
        $this->update = $this->getTableName();

        return $this;
    }

    /**
     * delete语句
     *
     * @return QueryBuilder
     */
    public function delete(): QueryBuilder
    {
        $this->delete = true;

        return $this;
    }

    /**
     * select语句
     *
     * @param mixed  $column
     * @param string $alias
     *
     * @return QueryBuilder
     */
    public function select($column, string $alias = null): QueryBuilder
    {
        if (is_array($column)) {
            return $this->selects($column);
        }
        $this->select[$column] = $alias;

        return $this;
    }

    /**
     * select语句
     *
     * @param array $columns
     *
     * @return QueryBuilder
     */
    public function selects(array $columns): QueryBuilder
    {
        foreach ($columns as $key => $column) {
            if (\is_int($key)) {
                $this->select[$column] = null;
                continue;
            }
            $this->select[$key] = $column;
        }

        return $this;
    }

    /**
     * @param string $table
     * @param string $alias
     *
     * @return QueryBuilder
     */
    public function table(string $table, string $alias = null)
    {
        $this->table['table'] = $this->getTableNameByClassName($table);
        $this->table['alias'] = $alias;

        return $this;
    }

    /**
     * inner join语句
     *
     * @param string       $table
     * @param string|array $criteria
     * @param string       $alias
     *
     * @return QueryBuilder
     * @throws \Swoft\Db\Exception\DbException
     */
    public function innerJoin(string $table, $criteria = null, string $alias = null): QueryBuilder
    {
        $table = $this->getTableNameByClassName($table);
        $this->join($table, $criteria, self::INNER_JOIN, $alias);

        return $this;
    }

    /**
     * left join语句
     *
     * @param string       $table
     * @param string|array $criteria
     * @param string       $alias
     *
     * @return QueryBuilder
     * @throws \Swoft\Db\Exception\DbException
     */
    public function leftJoin(string $table, $criteria = null, string $alias = null): QueryBuilder
    {
        $table = $this->getTableNameByClassName($table);
        $this->join($table, $criteria, self::LEFT_JOIN, $alias);

        return $this;
    }

    /**
     * right join语句
     *
     * @param string       $table
     * @param string|array $criteria
     * @param string       $alias
     *
     * @return QueryBuilder
     * @throws \Swoft\Db\Exception\DbException
     */
    public function rightJoin(string $table, $criteria = null, string $alias = null): QueryBuilder
    {
        $table = $this->getTableNameByClassName($table);
        $this->join($table, $criteria, self::RIGHT_JOIN, $alias);

        return $this;
    }

    /**
     * where语句
     *
     * @param string $column
     * @param mixed  $value
     * @param string $operator
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function where(string $column, $value, $operator = self::OPERATOR_EQ, $connector = self::LOGICAL_AND): QueryBuilder
    {
        $this->criteria($this->where, $column, $value, $operator, $connector);

        return $this;
    }

    /**
     * where and 语句
     *
     * @param string $column
     * @param mixed  $value
     * @param string $operator
     *
     * @return QueryBuilder
     */
    public function andWhere(string $column, $value, $operator = self::OPERATOR_EQ): QueryBuilder
    {
        $this->criteria($this->where, $column, $value, $operator, self::LOGICAL_AND);

        return $this;
    }

    /**
     * where条件中，括号开始(左括号)
     *
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function openWhere($connector = self::LOGICAL_AND): QueryBuilder
    {
        return $this->bracketCriteria($this->where, self::BRACKET_OPEN, $connector);
    }

    /**
     * where条件中，括号结束(右括号)
     *
     * @return QueryBuilder
     */
    public function closeWhere(): QueryBuilder
    {
        return $this->bracketCriteria($this->where, self::BRACKET_CLOSE);
    }

    /**
     * where or 语句
     *
     * @param string $column
     * @param mixed  $value
     * @param string $operator
     *
     * @return QueryBuilder
     */
    public function orWhere($column, $value, $operator = self::OPERATOR_EQ): QueryBuilder
    {
        $this->criteria($this->where, $column, $value, $operator, self::LOGICAL_OR);

        return $this;
    }

    /**
     * where in 语句
     *
     * @param string $column
     * @param array  $values
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function whereIn(string $column, array $values, string $connector = self::LOGICAL_AND): QueryBuilder
    {
        $this->criteria($this->where, $column, $values, self::IN, $connector);

        return $this;
    }

    /**
     * where not in 语句
     *
     * @param string $column
     * @param array  $values
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function whereNotIn(string $column, array $values, string $connector = self::LOGICAL_AND): QueryBuilder
    {
        $this->criteria($this->where, $column, $values, self::NOT_IN, $connector);

        return $this;
    }

    /**
     * between语句
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function whereBetween(string $column, $min, $max, string $connector = self::LOGICAL_AND): QueryBuilder
    {
        $this->criteria($this->where, $column, array($min, $max), self::BETWEEN, $connector);

        return $this;
    }

    /**
     * not between语句
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function whereNotBetween(string $column, $min, $max, string $connector = self::LOGICAL_AND): QueryBuilder
    {
        $this->criteria($this->where, $column, array($min, $max), self::NOT_BETWEEN, $connector);

        return $this;
    }

    /**
     * having语句
     *
     * @param string $column
     * @param mixed  $value
     * @param string $operator
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function having(string $column, $value, string $operator = self::OPERATOR_EQ, string $connector = self::LOGICAL_AND): QueryBuilder
    {
        $this->criteria($this->having, $column, $value, $operator, $connector);

        return $this;
    }

    /**
     * having and 语句
     *
     * @param string $column
     * @param mixed  $value
     * @param string $operator
     *
     * @return QueryBuilder
     */
    public function andHaving(string $column, $value, string $operator = self::OPERATOR_EQ): QueryBuilder
    {
        $this->criteria($this->having, $column, $value, $operator, self::LOGICAL_AND);

        return $this;
    }

    /**
     * having or 语句
     *
     * @param string $column
     * @param mixed  $value
     * @param string $operator
     *
     * @return QueryBuilder
     */
    public function orHaving(string $column, $value, string $operator = self::OPERATOR_EQ): QueryBuilder
    {
        $this->criteria($this->having, $column, $value, $operator, self::LOGICAL_OR);

        return $this;
    }

    /**
     * having in 语句
     *
     * @param string $column
     * @param array  $values
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function havingIn(string $column, array $values, string $connector = self::LOGICAL_AND): QueryBuilder
    {
        $this->criteria($this->having, $column, $values, self::IN, $connector);

        return $this;
    }

    /**
     * having not in 语句
     *
     * @param string $column
     * @param array  $values
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function havingNotIn(string $column, array $values, string $connector = self::LOGICAL_AND): QueryBuilder
    {
        $this->criteria($this->having, $column, $values, self::NOT_IN, $connector);

        return $this;
    }

    /**
     * having between语句
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function havingBetween(string $column, $min, $max, string $connector = self::LOGICAL_AND): QueryBuilder
    {
        $this->criteria($this->having, $column, array($min, $max), self::BETWEEN, $connector);

        return $this;
    }

    /**
     * having not between语句
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function havingNotBetween(string $column, $min, $max, string $connector = self::LOGICAL_AND): QueryBuilder
    {
        $this->criteria($this->having, $column, array($min, $max), self::NOT_BETWEEN, $connector);

        return $this;
    }

    /**
     * having，括号开始(左括号)
     *
     * @param string $connector
     *
     * @return QueryBuilder
     */
    public function openHaving($connector = self::LOGICAL_AND): QueryBuilder
    {
        return $this->bracketCriteria($this->having, self::BRACKET_OPEN, $connector);
    }

    /**
     * having，括号开始(右括号)
     *
     * @return QueryBuilder
     */
    public function closeHaving(): QueryBuilder
    {
        return $this->bracketCriteria($this->having, self::BRACKET_CLOSE);
    }

    /**
     * group by语句
     *
     * @param string $column
     * @param string $order
     *
     * @return QueryBuilder
     */
    public function groupBy(string $column, string $order = null): QueryBuilder
    {
        $this->groupBy[] = array(
            'column' => $column,
            'order'  => $order,
        );

        return $this;
    }

    /**
     * order by语句
     *
     * @param string $column
     * @param string $order
     *
     * @return QueryBuilder
     */
    public function orderBy(string $column, string $order = self::ORDER_BY_ASC): QueryBuilder
    {
        $this->orderBy[] = array(
            'column' => $column,
            'order'  => $order,
        );

        return $this;
    }

    /**
     * limit语句
     *
     * @param int $limit
     * @param int $offset
     *
     * @return QueryBuilder
     */
    public function limit(int $limit, $offset = 0): QueryBuilder
    {
        $this->limit['limit']  = $limit;
        $this->limit['offset'] = $offset;

        return $this;
    }

    /**
     * set语句
     *
     * @param mixed $column
     * @param mixed $value
     *
     * @return QueryBuilder
     */
    public function set($column, $value = null): QueryBuilder
    {
        if (!\is_array($column)) {
            $this->set[] = array(
                'column' => $column,
                'value'  => $value,
            );

            return $this;
        }

        foreach ($column as $columnName => $columnValue) {
            $this->set($columnName, $columnValue);
        }

        return $this;
    }

    /**
     * 设置参数
     *
     * @param mixed  $key   参数名称整数和字符串，(?n|:name)
     * @param mixed  $value 值
     * @param string $type  类型，默认按照$value传值的类型
     *
     * @return QueryBuilder
     * @throws \Swoft\Db\Exception\DbException
     */
    public function setParameter($key, $value, $type = null): QueryBuilder
    {
        list($key, $value) = $this->transferParameter($key, $value, $type);
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * 设置多个参数
     *
     * @param array $parameters
     *    $parameters = [
     *    'key1' => 'value1',
     *    'key2' => 'value2',
     *    ]
     *    $parameters = [
     *    'value1',
     *    'value12',
     *    ]
     *    $parameters = [
     *    ['key', 'value', 'type'],
     *    ['key', 'value'],
     *    ['key', 'value', 'type'],
     *    ]
     *
     *
     * @throws \Swoft\Db\Exception\DbException
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        // 循环设置每个参数
        foreach ($parameters as $index => $parameter) {
            $key   = null;
            $type  = null;
            $value = null;

            if (\count($parameter) >= 3) {
                list($key, $value, $type) = $parameter;
            } elseif (\count($parameter) == 2) {
                list($key, $value) = $parameter;
            } elseif (!is_array($parameter)) {
                $key   = $index;
                $value = $parameter;
            }

            if ($key === null || $value === null) {
                App::warning('sql参数设置格式错误，parameters=' . json_encode($parameters));
                continue;
            }
            $this->setParameter($key, $value, $type);
        }

        return $this;
    }

    /**
     * @return \Swoft\Db\AbstractDbConnection
     */
    public function getConnection(): \Swoft\Db\AbstractDbConnection
    {
        return $this->connection;
    }

    /**
     * 括号条件组拼
     *
     * @param array  $criteria
     * @param string $bracket
     * @param string $connector
     *
     * @return QueryBuilder
     */
    private function bracketCriteria(array &$criteria, string $bracket = self::BRACKET_OPEN, string $connector = self::LOGICAL_AND): QueryBuilder
    {
        $criteria[] = array(
            'bracket'   => $bracket,
            'connector' => $connector,
        );

        return $this;
    }

    /**
     * join数据组装
     *
     * @param string       $table
     * @param string|array $criteria
     * @param string       $type
     * @param string       $alias
     *
     * @return QueryBuilder
     */
    private function join(string $table, $criteria = null, string $type = self::INNER_JOIN, string $alias = null): QueryBuilder
    {
        // 是否存在判断...

        if (\is_string($criteria)) {
            $criteria = array($criteria);
        }

        $this->join[] = array(
            'table'    => $table,
            'criteria' => $criteria,
            'type'     => $type,
            'alias'    => $alias,
        );

        return $this;
    }

    /**
     * 条件组装
     *
     * @param array  $criteria
     * @param string $column
     * @param mixed  $value
     * @param string $operator
     * @param string $connector
     *
     * @return QueryBuilder
     */
    private function criteria(
        array &$criteria,
        string $column,
        $value,
        string $operator = self::OPERATOR_EQ,
        string $connector = self::LOGICAL_AND
    ): QueryBuilder {
        $criteria[] = array(
            'column'    => $column,
            'value'     => $value,
            'operator'  => $operator,
            'connector' => $connector,
        );

        return $this;
    }

    /**
     * @param string $db
     *
     * @return QueryBuilder
     */
    public function selectDb(string $db)
    {
        $this->db = $db;
        return $this;
    }

    /**
     * @param string $node
     *
     * @return QueryBuilder
     */
    public function selectNode(string $node = Pool::MASTER)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * @param string $instance
     *
     * @return QueryBuilder
     */
    public function selectInstance(string $instance)
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * @param bool $master
     *
     * @return QueryBuilder
     */
    public function force(bool $master = true)
    {
        if ($master) {
            $this->node = Pool::MASTER;
        }
        return $this;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $statementClassName = DbHelper::getStatementClassNameByInstance($this->instance);
        /* @var StatementInterface $statement */
        $statement    = new $statementClassName($this);
        $sql          = $statement->getStatement();
        $instanceName = $this->getInstanceName();

        return Db::query($sql, $this->parameters, $instanceName);
    }

    /**
     * @return string
     */
    private function getInstanceName()
    {
        return sprintf('%s.%s.%s', $this->instance, $this->node, $this->db);
    }


    /**
     * 实体类名获取表名
     *
     * @param string $tableName
     *
     * @return string
     * @throws DbException
     */
    private function getTableNameByClassName($tableName): string
    {
        // 不是实体类名
        if (strpos($tableName, '\\') === false) {
            return $tableName;
        }

        $entities = EntityCollector::getCollector();
        if (!isset($entities[$tableName]['table']['name'])) {
            throw new DbException('类不是实体，className=' . $tableName);
        }
        $name = $entities[$tableName]['table']['name'];

        return $name;
    }

    /**
     * 参数个数转换
     *
     * @param mixed  $key
     * @param mixed  $value
     * @param string $type
     *
     * @throws DbException
     *
     * @return array
     */
    private function transferParameter($key, $value, $type): array
    {
        if (!\is_int($key) && !\is_string($key)) {
            throw new DbException('参数key,只能是字符串和整数');
        }
        $key = $this->formatParamsKey($key);

        // 参数值类型转换
        if ($type !== null) {
            $value = EntityHelper::trasferTypes($type, $value);
        }

        return [$key, $value];
    }

    /**
     * @return string
     * @throws \Swoft\Db\Exception\MysqlException
     */
    private function getTableName(): string
    {
        if (empty($this->table)) {
            throw new MysqlException('Table name must be setted!');
        }
        $table = $this->table['table'];

        return $table;
    }

    /**
     * @param mixed $key
     *
     * @return string
     */
    private function formatParamsKey($key): string
    {
        if (\is_string($key) && strpos($key, ':') === false) {
            return ':' . $key;
        }
        if (is_int($key) && App::isCoContext()) {
            return '?' . $key;
        }

        return $key;
    }

    /**
     * @return string
     */
    public function getInsert(): string
    {
        return $this->insert;
    }

    /**
     * @return string
     */
    public function getUpdate(): string
    {
        return $this->update;
    }

    /**
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->delete;
    }

    /**
     * @return array
     */
    public function getSelect(): array
    {
        return $this->select;
    }

    /**
     * @return array
     */
    public function getSet(): array
    {
        return $this->set;
    }

    /**
     * @return array
     * @throws MysqlException
     */
    public function getFrom(): array
    {
        if (empty($this->table)) {
            throw new MysqlException('Table name must be setted!');
        }

        return $this->table;
    }

    /**
     * @return array
     */
    public function getJoin(): array
    {
        return $this->join;
    }

    /**
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * @return array
     */
    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    /**
     * @return array
     */
    public function getHaving(): array
    {
        return $this->having;
    }

    /**
     * @return array
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @return array
     */
    public function getLimit(): array
    {
        return $this->limit;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
