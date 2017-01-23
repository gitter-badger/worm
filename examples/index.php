<?php
declare(strict_types=1);

require __DIR__ . "/../vendor/autoload.php";

use WoohooLabs\Worm\Connection\MySqlPdoConnection;
use WoohooLabs\Worm\Query\Condition\ConditionBuilderInterface;
use WoohooLabs\Worm\Worm;

$worm = new Worm(
    MySqlPdoConnection::create(
        "mysql",
        "mysql",
        (int) getenv("MYSQL_PORT"),
        getenv("MYSQL_DATABASE"),
        getenv("MYSQL_USER"),
        getenv("MYSQL_PASSWORD"),
        "utf8mb4",
        "utf8mb4_unicode_ci",
        [],
        [],
        true
    )
);

$query = $worm
    ->query()
    ->from("students", "s")
    ->where(
        function (ConditionBuilderInterface $where) {
            $where
                ->raw("last_name LIKE ?", ["%a%"])
                ->and()
                ->nested(
                    function (ConditionBuilderInterface $where) {
                        $where
                            ->is("birthday", null, "s")
                            ->or()
                            ->is("gender", null, "s");
                    }
                );
        }
    )
    ->limit(10)
    ->offset(0);

echo "Query:<br/>";
echo "<pre>";
print_r($query->getSql());
echo "</pre>";

echo "Params:<br/>";
echo "<pre>";
print_r($query->getParams());
echo "</pre>";

echo "Result Set:<br/>";
echo "<pre>";
print_r($query->execute());
echo "</pre>";

$query = $worm
    ->query()
    ->select(["s.*"])
    ->distinct()
    ->from("courses", "c")
    ->join("classes", "cl")
    ->on(
        function (ConditionBuilderInterface $on) {
            $on->raw("c.id = cl.course_id");
        }
    )
    ->join("classes_students", "cs")
    ->on(
        function (ConditionBuilderInterface $on) {
            $on->raw("cl.id = cs.class_id");
        }
    )
    ->join("students", "s")
    ->on(
        function (ConditionBuilderInterface $on) {
            $on->raw("s.id = cs.student_id");
        }
    )
    ->where(
        function (ConditionBuilderInterface $on) {
            $on->raw("c.id = ?", [2]);
        }
    )
    ->orderBy("s.id", "ASC");

echo "Query:<br/>";
echo "<pre>";
print_r($query->getSql());
echo "</pre>";

echo "Params:<br/>";
echo "<pre>";
print_r($query->getParams());
echo "</pre>";

echo "Result Set:<br/>";
echo "<pre>";
print_r($query->execute());
echo "</pre>";
