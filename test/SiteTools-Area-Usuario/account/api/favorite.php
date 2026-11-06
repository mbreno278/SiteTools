<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/user-area.php';

header('Content-Type: application/json; charset=utf-8');
$user=auth_user();
if(!$user){http_response_code(401);echo json_encode(['success'=>false,'error'=>'Faça login para gerenciar favoritos.']);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'error'=>'Método não permitido.']);exit;}
csrf_verify();

$payload=csrf_json_input()??[];
$toolId=filter_var($payload['tool_id']??null,FILTER_VALIDATE_INT);
if(!$toolId){http_response_code(422);echo json_encode(['success'=>false,'error'=>'Ferramenta inválida.']);exit;}

$check=$pdo->prepare('SELECT id FROM tools WHERE id=:id AND is_active=1 LIMIT 1');
$check->execute(['id'=>$toolId]);
if(!$check->fetchColumn()){http_response_code(404);echo json_encode(['success'=>false,'error'=>'Ferramenta não encontrada.']);exit;}

$find=$pdo->prepare('SELECT id FROM user_favorites WHERE user_id=:uid AND tool_id=:tid');
$find->execute(['uid'=>(int)$user['id'],'tid'=>$toolId]);
if($find->fetchColumn()){
    $del=$pdo->prepare('DELETE FROM user_favorites WHERE user_id=:uid AND tool_id=:tid');
    $del->execute(['uid'=>(int)$user['id'],'tid'=>$toolId]);
    $active=false;
}else{
    $ins=$pdo->prepare('INSERT IGNORE INTO user_favorites (user_id,tool_id) VALUES (:uid,:tid)');
    $ins->execute(['uid'=>(int)$user['id'],'tid'=>$toolId]);
    $active=true;
}
echo json_encode(['success'=>true,'active'=>$active],JSON_UNESCAPED_UNICODE);
