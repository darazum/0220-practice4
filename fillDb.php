<?php
/**
 *  1. что такое большая база и большая нагрузка
    2. заполнили большую таблицу
    3. движки таблиц
    4. запросы
    5. explain
    6. О - нотация
    7. индексы
 */
require __DIR__ . '/../vendor/autoload.php';

use Elasticsearch\ClientBuilder;
use Search\Service;


$pdo = new \PDO('mysql:host=5.7-mysql:3306;dbname=docker', 'docker', 'docker');
$client = ClientBuilder::create()->setHosts(['elasticsearch'])->build();
$search = new Service($client);
$index = 'users';
$ret = $search->deleteIndex($index);
echo "delete elk index: " . print_r($ret, 1) . "\n";

$ret = $search->createIndex($index);
echo "create elk index: " . print_r($ret, 1) . "\n";

/**
SET GLOBAL tmp_table_size = 1024 * 1024 * 1024 * 2;
SET GLOBAL max_heap_table_size = 1024 * 1024 * 1024 * 2;

SHOW VARIABLES LIKE 'max_heap_table_size';

insert into users_memory (
select id, gender, age, height, weight, likes, points  from users
)

$sql = "INSERT INTO users(`name`, gender, age, height, weight, likes, points)
VALUES('Vasja', 1, 15, 156, 84, 156, 18767), ('Vasja', 1, 15, 156, 84, 156, 18767)";
 */

$values = [];
$elkModels = [];

$assoc = [
    '123' => [],
    '1234' => [],

];

$t = microtime(1);
$bulkSize = 1000;
for($i = 0; $i < 5000000; $i++) {
    $data['gender'] = mt_rand(0, 1);
    $data['name'] = ($data['gender'] == 0 ? 'Masha #' : 'Vasja #') . $i;
    $data['name'] = $i;
    $data['age'] = mt_rand(20, 30);
    $data['height'] = mt_rand(150, 200);
    $data['weight'] = mt_rand(50, 100);
    $data['likes'] = mt_rand(0, 1000);
    $data['points'] = mt_rand(0, 100000000);
    $elkModels[] = new \Search\Model($data);

    $data['name'] = "'{$data['name']}'";
    $values[] = '(' . implode(',', $data) . ')';

    if (sizeof($values) >= $bulkSize) {
        $valuesStr = implode(',', $values);
        $sql = "INSERT INTO users(gender, `name`, age, height, weight, likes, points) VALUES $valuesStr";
        $ret = $pdo->exec($sql);
        $lastId = (int) $pdo->lastInsertId();
        foreach ($elkModels as $k => $elkModel) {
            $elkModel->setId($lastId + $k);
        }

        $search->upload($index, $elkModels);
        $values = [];
        $elkModels = [];

        $time = microtime(1) - $t;
        echo "$i users inserted in $time sec \n";
    }

}

// SELECT SQL_NO_CACHE * FROM `users` where gender = 1 and height > 199 and weight < 51 limit 100;
// SELECT SQL_NO_CACHE * FROM `users` where gender = 1 and points < 1000 limit 100;
// SELECT * FROM users  where age > 25 and age < 28 and weight < 80 and likes > 200 and likes < 800 order by likes asc, id desc limit 100
