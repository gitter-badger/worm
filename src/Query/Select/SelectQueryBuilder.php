<?php
declare(strict_types=1);

namespace WoohooLabs\Worm\Query\Select;

use Closure;
use WoohooLabs\Worm\Connection\ConnectionInterface;
use WoohooLabs\Worm\Query\Condition\ConditionBuilder;
use WoohooLabs\Worm\Query\Condition\ConditionsInterface;

class SelectQueryBuilder implements SelectQueryBuilderInterface, SelectQueryInterface
{
    /**
     * @var \WoohooLabs\Worm\Connection\ConnectionInterface
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $distinct = false;

    /**
     * @var array
     */
    protected $select = [];

    /**
     * @var array
     */
    protected $from = [];

    /**
     * @var array
     */
    protected $aggregate = [];

    /**
     * @var array
     */
    protected $join = [];

    /**
     * @var ConditionBuilder
     */
    protected $where;

    /**
     * @var array
     */
    protected $groupBy = [];

    /**
     * @var ConditionBuilder
     */
    protected $having;

    /**
     * @var array
     */
    protected $orderBy = [];

    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @var int|null
     */
    protected $offset;

    /**
     * @var array
     */
    protected $union = [];

    /**
     * @var array
     */
    protected $params = [];

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->where = new ConditionBuilder($this->connection);
        $this->having = new ConditionBuilder($this->connection);
    }

    public function select(array $fields): SelectQueryBuilderInterface
    {
        $this->select = $fields;

        return $this;
    }

    public function distinct(bool $isDistinct = true): SelectQueryBuilderInterface
    {
        $this->distinct = $isDistinct;

        return $this;
    }

    public function from(string $table, string $alias = ""): SelectQueryBuilderInterface
    {
        $this->from = [
            "type" => "table",
            "table" => $table,
            "alias" => $alias,
        ];

        return $this;
    }

    public function fromSubquery(Closure $subquery, string $alias): SelectQueryBuilderInterface
    {
        $queryBuilder = new SelectQueryBuilder($this->connection);
        $subquery($queryBuilder);

        $this->from = [
            "type" => "subquery",
            "table" => $queryBuilder,
            "alias" => $alias,
        ];

        return $this;
    }

    public function leftJoin(string $table, string $alias = ""): SelectQueryBuilderInterface
    {
        return $this->join($table, $alias, "LEFT");
    }

    public function rightJoin(string $table, string $alias = ""): SelectQueryBuilderInterface
    {
        return $this->join($table, $alias, "RIGHT");
    }

    public function join(string $table, string $alias = "", string $type = ""): SelectQueryBuilderInterface
    {
        $this->join[] = [
            "type" => $type,
            "table" => $table,
            "alias" => $alias,
        ];

        return $this;
    }

    public function on(Closure $on = null): SelectQueryBuilderInterface
    {
        $queryBuilder = new ConditionBuilder($this->connection);
        $on($queryBuilder);

        $this->join[] = [
            "type" => "on",
            "on" => $queryBuilder,
        ];

        return $this;
    }

    public function where(Closure $condition): SelectQueryBuilderInterface
    {
        $condition($this->where);

        return $this;
    }

    public function having(string $operand1, string $operator, string $operand2, string $connector = "and"): SelectQueryBuilderInterface
    {
        $this->having->columnToValue($operand1, $operator, $operand2, $connector);

        return $this;
    }

    public function havingRaw(string $condition, array $params = [], string $connector = "and"): SelectQueryBuilderInterface
    {
        $this->having->raw($condition, $params, $connector);

        return $this;
    }

    public function havingNested(Closure $condition, string $connector = "and"): SelectQueryBuilderInterface
    {
        $this->having->nested($condition, $connector);

        return $this;
    }

    public function groupBy(string $attribute): SelectQueryBuilderInterface
    {
        $this->groupBy[] = $attribute;

        return $this;
    }

    public function groupByAttributes(array $attributes): SelectQueryBuilderInterface
    {
        foreach ($attributes as $attribute) {
            $this->groupBy($attribute);
        }

        return $this;
    }

    public function orderBy(string $attribute, string $direction = "ASC"): SelectQueryBuilderInterface
    {
        $this->orderBy[] = ["attribute" => $attribute, "direction" => $direction];

        return $this;
    }

    public function limit($limit): SelectQueryBuilderInterface
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset($offset): SelectQueryBuilderInterface
    {
        $this->offset = $offset;

        return $this;
    }

    public function execute(): array
    {
        $query = $this->connection->getDriver()->translateSelectQuery($this);

        return $this->connection->queryAll($query->getSql(), $query->getParams());
    }

    public function getSql(): string
    {
        return $this->connection->getDriver()->translateSelectQuery($this)->getSql();
    }

    public function getParams(): array
    {
        return $this->connection->getDriver()->translateSelectQuery($this)->getParams();
    }

    public function getSelect(): array
    {
        return $this->select;
    }

    public function isDistinct(): bool
    {
        return $this->distinct;
    }

    public function getFrom(): array
    {
        return $this->from;
    }

    public function getAggregate(): array
    {
        return $this->aggregate;
    }

    public function getJoins(): array
    {
        return $this->join;
    }

    public function getWhere(): ConditionsInterface
    {
        return $this->where;
    }

    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    public function getHaving(): ConditionsInterface
    {
        return $this->having;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int|null
     */
    public function getOffset()
    {
        return $this->offset;
    }

    public function getUnion(): array
    {
        return $this->union;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}
