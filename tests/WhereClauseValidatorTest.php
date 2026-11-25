<?php

/**
 * Dependency-free test for WhereClauseValidator.
 * Run with: php tests/WhereClauseValidatorTest.php
 */
require __DIR__.'/../src/Security/WhereClauseValidator.php';

use Sintattica\Atk\Security\WhereClauseValidator;

$mysql = new WhereClauseValidator(true);
$pgsql = new WhereClauseValidator(false);
$failures = 0;

$check = function ($label, $actual, $expected) use (&$failures) {
    if ($actual !== $expected) {
        ++$failures;
        echo "FAIL: $label (expected ".var_export($expected, true).")\n";
    }
};

$validKeys = [
    "",
    "person.id='5'",
    "person.id = '5'",
    "id='5'",
    "person.id=5",
    "`person`.`id`='5'",
    "person.id='5' AND person.lang='en'",
    "db.person.id='5'",
    "person.code='O''Brien'",
    "person.code='O\\'Brien'",
    "person.id='-1'",
    "person.amount=-1.5",
];
foreach ($validKeys as $k) {
    $check("key accepted: $k", $mysql->isValidKey($k), true);
    $check("key is also a condition: $k", $mysql->isValidCondition($k), true);
}

$invalidKeys = [
    "person.id>'0'",
    "person.id='5' OR person.id='6'",
    "person.id='5' OR 1=1",
    "1=1",
    "person.id IS NOT NULL",
    "person.id=person.id",
    "(person.id='5')",
    "person.id IN ('1','2')",
    "person.id='5' AND",
    "person.id=",
    "person.id='5",
    ["person.id='5'"],
    null,
    5,
];
foreach ($invalidKeys as $k) {
    $check('key rejected: '.var_export($k, true), $mysql->isValidKey($k), false);
}

$validConditions = [
    "project.status='active'",
    "project.status = 'active' AND project.owner_id = 12",
    "(a.x='1' OR a.x='2') AND a.y<>'3'",
    "a.x IN ('1','2',3)",
    "a.x NOT IN (1,2)",
    "a.x IS NULL",
    "a.x IS NOT NULL",
    "a.name LIKE 'jo%'",
    "a.name NOT LIKE '%x%'",
    "a.d BETWEEN '2020-01-01' AND '2020-12-31'",
    "NOT (a.x = 1)",
    "a.x >= 10 AND a.x <= 20",
    "a.x != -5",
    "a.x = b.y",
    "a.x = NULL",
    "a.note = 'select * from users; -- /* # union'",
];
foreach ($validConditions as $c) {
    $check("condition accepted: $c", $mysql->isValidCondition($c), true);
}

$invalidConditions = [
    "1=1",
    "a.x='1' OR 1=1",
    "a.x='1' UNION SELECT password FROM users",
    "a.x='1' UNION\tSELECT 1",
    "a.x='1'; DROP TABLE users",
    "a.x='1' -- comment",
    "a.x='1' /* comment */",
    "a.x='1' # comment",
    "a.x=(SELECT 1)",
    "a.x IN (SELECT id FROM users)",
    "SLEEP(5)",
    "a.x=SLEEP(5)",
    "a.x='1' AND SLEEP(5)",
    "a.x='1' AND (SELECT 1)=1",
    "a.x=0x3a",
    "a.x=1e5",
    "a.x=@@version",
    "a.x='1' AND b.y=c.z.d.e.f",
    "a.x LIKE b.y",
    "a.x='1' OR",
    "a.x",
    "'1'='1'",
    "a.x=\"1\"",
    "a.x='1\0'",
    "a.x = 1 - 1",
    "a.x=1-1",
    "a.x = CONCAT('a','b')",
    "a.x = 'a' 'b'",
    "a.x='1' AND NOT",
    "((((((((((((((((((((((a.x=1))))))))))))))))))))))",
];
foreach ($invalidConditions as $c) {
    $check('condition rejected: '.addcslashes($c, "\0\t"), $mysql->isValidCondition($c), false);
}

// Backslash escapes are only an escape on MySQL. On PostgreSQL the quote after
// a backslash ends the string, so the remainder would be executed as SQL.
$check('mysql accepts backslash escape', $mysql->isValidCondition("a.x='it\\'s'"), true);
$check('pgsql rejects backslash escape', $pgsql->isValidCondition("a.x='it\\'s'"), false);
$check('pgsql rejects backslash break-out', $pgsql->isValidCondition("a.x='a\\' OR b.y='"), false);
$check('mysql rejects trailing backslash', $mysql->isValidCondition("a.x='a\\"), false);
$check('pgsql accepts doubled quote', $pgsql->isValidCondition("a.x='it''s'"), true);

if ($failures) {
    echo "$failures failure(s)\n";
    exit(1);
}
echo "OK\n";
