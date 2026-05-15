<?php
require_once '../../includes/helpers.php';
$user = requireAuth('super_admin');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();
$id     = (int)($_POST['id']??0);
$action = sanitize($_POST['action']??'');
if (!$id) jsonResponse(['success'=>false,'message'=>'ID required']);
if ($action==='approve') $db->execute("UPDATE tutors SET is_approved=1 WHERE id=?",[$id]);
elseif ($action==='suspend') $db->execute("UPDATE tutors SET is_suspended=1 WHERE id=?",[$id]);
elseif ($action==='unsuspend') $db->execute("UPDATE tutors SET is_suspended=0 WHERE id=?",[$id]);
else jsonResponse(['success'=>false,'message'=>'Invalid action']);
logActivity((int)$user['id'],'admin',$action.'_tutor',"Tutor ID:{$id}");
jsonResponse(['success'=>true,'message'=>'Tutor updated']);
