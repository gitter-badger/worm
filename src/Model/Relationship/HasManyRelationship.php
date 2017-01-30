<?php
declare(strict_types=1);

namespace WoohooLabs\Worm\Model\Relationship;

use WoohooLabs\Larva\Connection\ConnectionInterface;
use WoohooLabs\Larva\Query\Condition\ConditionBuilderInterface;
use WoohooLabs\Larva\Query\Select\SelectQueryBuilder;
use WoohooLabs\Larva\Query\Select\SelectQueryBuilderInterface;
use WoohooLabs\Worm\Execution\ModelContainer;
use WoohooLabs\Worm\Model\ModelInterface;

class HasManyRelationship extends AbstractRelationship
{
    /**
     * @var string
     */
    private $relatedModel;

    /**
     * @var string
     */
    private $foreignKey;

    /**
     * @var string
     */
    private $referencedKey;

    public function __construct(string $relatedModel, string $foreignKey, string $referencedKey)
    {
        $this->relatedModel = $relatedModel;
        $this->foreignKey = $foreignKey;
        $this->referencedKey = $referencedKey;
    }

    public function getRelationship(
        ModelInterface $model,
        ModelContainer $container,
        ConnectionInterface $connection,
        array $entities
    ): SelectQueryBuilderInterface {
        $relatedModel = $container->get($this->relatedModel);

        $queryBuilder = new SelectQueryBuilder($connection);
        $queryBuilder
            ->fields(["`" . $relatedModel->getTable() . "`.*"])
            ->from($relatedModel->getTable())
            ->join($model->getTable())
            ->on(
                function (ConditionBuilderInterface $on) use ($model, $relatedModel) {
                    $on->columnToColumn(
                        $this->referencedKey,
                        "=",
                        $this->foreignKey,
                        $model->getTable(),
                        $relatedModel->getTable()
                    );
                }
            )
            ->where($this->getWhereCondition($model, $entities));

        return $queryBuilder;
    }

    public function matchEntities(array $entities, string $relationshipName, array $relatedEntities): array
    {
        return $entities;
    }

    public function getRelatedModel(): string
    {
        return $this->relatedModel;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getReferencedKey(): string
    {
        return $this->referencedKey;
    }
}