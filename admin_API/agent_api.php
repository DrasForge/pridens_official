<?php
// admin_API/agent_api.php
header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_agent_ranks':
        try {
            $stmt = $pdo->query("SELECT * FROM ranks ORDER BY rank_order ASC");
            $ranks = $stmt->fetchAll();
            
            // Add qualification_count (mocking for now or counting from table)
            foreach ($ranks as &$rank) {
                $stmtQ = $pdo->prepare("SELECT COUNT(*) FROM rank_qualifications WHERE rank_id = ?");
                $stmtQ->execute([$rank['id']]);
                $rank['qualification_count'] = $stmtQ->fetchColumn();
            }
            
            echo json_encode(['status' => 'success', 'data' => $ranks]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'create_agent_rank':
        try {
            $name = $_POST['name'] ?? '';
            $type = $_POST['promotion_type'] ?? 'auto';
            $is_team_leader = $_POST['is_team_leader'] ?? 0;
            
            if (!$name) throw new Exception("Rank name is required");
            
            // Get max order
            $stmt = $pdo->query("SELECT MAX(rank_order) FROM ranks");
            $maxOrder = $stmt->fetchColumn() ?: 0;
            
            $stmt = $pdo->prepare("INSERT INTO ranks (rank_name, promotion_type, is_team_leader, rank_order, level) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $type, $is_team_leader, $maxOrder + 1, $maxOrder + 1]);
            
            echo json_encode(['status' => 'success', 'message' => 'Rank created']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'update_agent_rank':
        try {
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            $type = $_POST['promotion_type'] ?? 'auto';
            $is_team_leader = $_POST['is_team_leader'] ?? 0;
            
            if (!$id || !$name) throw new Exception("ID and name are required");
            
            $stmt = $pdo->prepare("UPDATE ranks SET rank_name = ?, promotion_type = ?, is_team_leader = ? WHERE id = ?");
            $stmt->execute([$name, $type, $is_team_leader, $id]);
            
            echo json_encode(['status' => 'success', 'message' => 'Rank updated']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_agent_rank':
        try {
            $id = $_POST['id'] ?? '';
            if (!$id) throw new Exception("Rank ID is required");
            
            $stmt = $pdo->prepare("DELETE FROM ranks WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['status' => 'success', 'message' => 'Rank deleted']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'reorder_agent_ranks':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $order = $data['order'] ?? [];
            
            $pdo->beginTransaction();
            foreach ($order as $index => $id) {
                $stmt = $pdo->prepare("UPDATE ranks SET rank_order = ?, level = ? WHERE id = ?");
                $stmt->execute([$index + 1, $index + 1, $id]);
            }
            $pdo->commit();
            
            echo json_encode(['status' => 'success', 'message' => 'Reordered']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'get_rank_qualifications':
        try {
            $rankId = $_GET['rank_id'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM rank_qualifications WHERE rank_id = ?");
            $stmt->execute([$rankId]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'save_rank_qualifications':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $rankId = $data['rank_id'] ?? '';
            $quals = $data['qualifications'] ?? [];
            
            $pdo->beginTransaction();
            // Delete old
            $stmt = $pdo->prepare("DELETE FROM rank_qualifications WHERE rank_id = ?");
            $stmt->execute([$rankId]);
            
            // Insert new
            $stmt = $pdo->prepare("INSERT INTO rank_qualifications (rank_id, qualification_type, value, plan_id, target_rank_id, is_direct, max_levels) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($quals as $q) {
                $stmt->execute([
                    $rankId, 
                    $q['qualification_type'], 
                    $q['value'], 
                    $q['plan_id'] ?? null, 
                    $q['target_rank_id'] ?? null, 
                    $q['is_direct'] ?? 1, 
                    $q['max_levels'] ?? null
                ]);
            }
            $pdo->commit();
            
            echo json_encode(['status' => 'success', 'message' => 'Qualifications saved']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'get_cluster_config':
        try {
            $rankId = $_GET['rank_id'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM rank_clusters WHERE rank_id = ?");
            $stmt->execute([$rankId]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'save_cluster_config':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $rankId = $data['rank_id'] ?? '';
            $clusters = $data['clusters'] ?? [];
            
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM rank_clusters WHERE rank_id = ?");
            $stmt->execute([$rankId]);
            
            $stmt = $pdo->prepare("INSERT INTO rank_clusters (rank_id, subordinate_rank_id, plan_id, min_groups, min_members_per_group, group_quota, group_incentive, start_day, end_day, allow_multipayout, is_group_count, individual_peer_boost_incentive, leader_leg_bonus_incentive, boosted_subsub_incentive) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($clusters as $c) {
                $stmt->execute([
                    $rankId,
                    $c['subordinate_rank_id'],
                    $c['plan_id'] ?: null,
                    $c['min_groups'],
                    $c['min_members_per_group'],
                    $c['group_quota'],
                    $c['group_incentive'],
                    $c['start_day'],
                    $c['end_day'],
                    $c['allow_multipayout'] ? 1 : 0,
                    $c['is_group_count'] ? 1 : 0,
                    $c['individual_peer_boost_incentive'] ?? 0,
                    $c['leader_leg_bonus_incentive'] ?? 0,
                    $c['boosted_subsub_incentive'] ?? 0
                ]);
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Clusters saved']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'get_incentive_config':
        try {
            $rankId = $_GET['rank_id'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM rank_incentive_config WHERE rank_id = ?");
            $stmt->execute([$rankId]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'save_incentive_config':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $rankId = $data['rank_id'] ?? '';
            $incentives = $data['incentives'] ?? [];
            $allow_multi = $data['allow_multipayout'] ?? 0;
            $count_downline = $data['count_downline'] ?? 1;
            $start_day = $data['start_day'] ?? 1;
            $end_day = $data['end_day'] ?? 31;
            
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM rank_incentive_config WHERE rank_id = ?");
            $stmt->execute([$rankId]);
            
            $stmt = $pdo->prepare("INSERT INTO rank_incentive_config (rank_id, plan_id, monthly_quota, base_incentive, quota_50, incentive_50, quota_10, incentive_10, allow_multipayout, count_downline, start_day, end_day) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($incentives as $i) {
                $stmt->execute([
                    $rankId,
                    $i['plan_id'],
                    $i['monthly_quota'],
                    $i['base_incentive'],
                    $i['quota_50'],
                    $i['incentive_50'],
                    $i['quota_10'],
                    $i['incentive_10'],
                    $allow_multi ? 1 : 0,
                    $count_downline ? 1 : 0,
                    $start_day,
                    $end_day
                ]);
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Incentives saved']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'get_incentive_tiers':
        try {
            $rankName = $_GET['rank_name'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM rank_incentive_tiers WHERE rank_name = ?");
            $stmt->execute([$rankName]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'save_incentive_tiers':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $rankName = $data['rank_name'] ?? '';
            $tiers = $data['tiers'] ?? [];
            
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM rank_incentive_tiers WHERE rank_name = ?");
            $stmt->execute([$rankName]);
            
            $stmt = $pdo->prepare("INSERT INTO rank_incentive_tiers (rank_name, quota, incentive, is_shared) VALUES (?, ?, ?, ?)");
            foreach ($tiers as $t) {
                $stmt->execute([$rankName, $t['quota'], $t['incentive'], $t['is_shared'] ? 1 : 0]);
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Tiers saved']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'get_card_theme':
        try {
            $rankId = $_GET['rank_id'] ?? '';
            $stmt = $pdo->prepare("SELECT card_theme as theme_data FROM ranks WHERE id = ?");
            $stmt->execute([$rankId]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetch()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'save_card_theme':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $rankId = $data['rank_id'] ?? '';
            $themeData = json_encode($data['theme_data'] ?? []);
            
            $stmt = $pdo->prepare("UPDATE ranks SET card_theme = ? WHERE id = ?");
            $stmt->execute([$themeData, $rankId]);
            
            echo json_encode(['status' => 'success', 'message' => 'Card theme saved']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'get_subscription_plans':
        try {
            $stmt = $pdo->query("SELECT id, plan_name as name, status FROM subscription_plans WHERE status != 'archived'");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>
