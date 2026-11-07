<?php

    include_once __DIR__ . '/database.php';

    /* Function that SELECT data in the databse table tools */
    function get_tool_data($id) {
        $sql = $pdo->prepare('SELECT * FROM tools WHERE id = :id');
        $sql->bindParam(':id', $id, PDO::PARAM_INT);
        $sql->execute();
        return $sql->fetch(PDO::FETCH_ASSOC);
    }

