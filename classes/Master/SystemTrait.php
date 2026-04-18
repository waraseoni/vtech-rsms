<?php
trait SystemTrait {
    function create_backup(){
        $backup_dir = __DIR__ . "/../backups/";
        if(!is_dir($backup_dir)) mkdir($backup_dir, 0777, true);
        $filename = "vikram_db_backup_".date("Y-m-d_H-i-s").".sql";
        $filepath = $backup_dir . $filename;
        $sql = "-- VTech-RSMS Backup\n-- Date: ".date("Y-m-d H:i:s")."\n\n";
        $tables = [];
        $res = $this->conn->query("SHOW TABLES");
        while($row = $res->fetch_row()) $tables[] = $row[0];
        $total_records = 0;
        foreach($tables as $table){
            $sql .= "DROP TABLE IF EXISTS `$table`;\n" . $this->conn->query("SHOW CREATE TABLE `$table`")->fetch_row()[1] . ";\n\n";
            $data = $this->conn->query("SELECT * FROM `$table`");
            while($row = $data->fetch_row()){
                $sql .= "INSERT INTO `$table` VALUES (";
                foreach($row as $k => $v) $sql .= ($k > 0 ? ", " : "") . "'" . addslashes($v) . "'";
                $sql .= ");\n"; $total_records++;
            }
            $sql .= "\n";
        }
        $sql .= "\n-- CHECKSUM: ".md5($sql)."\n-- TOTAL RECORDS: $total_records\n-- TOTAL TABLES: ".count($tables)."\n";
        if(file_put_contents($filepath, $sql) !== false){
            $this->rotate_backups($backup_dir, 10);
            $this->settings->set_flashdata('success', "Backup created successfully! ($total_records records)");
            return json_encode(['status' => 'success', 'file' => $filename]);
        }
        return json_encode(['status' => 'failed', 'msg' => 'Failed to save backup file.']);
    }

    function rotate_backups($backup_dir, $max = 10) {
        $files = glob($backup_dir . '*.sql');
        if (count($files) > $max) {
            usort($files, function($a, $b) { return filemtime($a) - filemtime($b); });
            foreach (array_slice($files, 0, count($files) - $max) as $file) unlink($file);
        }
    }

    function restore_backup(){
        if(!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== 0) return json_encode(['status' => 'failed', 'msg' => 'No file uploaded.']);
        $sql_content = file_get_contents($_FILES['backup_file']['tmp_name']);
        $this->conn->query("SET FOREIGN_KEY_CHECKS=0");
        $success = true; $error_msg = ''; $current_query = ''; $in_string = false; $string_char = '';
        $len = strlen($sql_content);
        for ($i = 0; $i < $len; $i++) {
            $char = $sql_content[$i];
            if (!$in_string) {
                if ($char == "'" || $char == '"') { $in_string = true; $string_char = $char; }
                elseif ($char == ';') {
                    if (!empty(trim($current_query)) && !$this->conn->query(trim($current_query))) { $success = false; $error_msg = $this->conn->error; break; }
                    $current_query = ''; continue;
                }
            } else {
                if ($char == '\\') { $current_query .= $char; $i++; $char = $sql_content[$i] ?? ''; }
                elseif ($char == $string_char) $in_string = false;
            }
            $current_query .= $char;
        }
        $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
        if($success) return json_encode(['status' => 'success', 'msg' => 'Database restored successfully!']);
        return json_encode(['status' => 'failed', 'msg' => 'Restore failed: ' . $error_msg]);
    }

    function dry_run_backup(){
        if(!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== 0) 
            return json_encode(['status' => 'failed', 'msg' => 'No file uploaded or upload error.']);

        $sql = file_get_contents($_FILES['backup_file']['tmp_name']);
        $backup_file_name = $_FILES['backup_file']['name'];

        // 1. Analyze Backup File
        preg_match_all('/CREATE TABLE `([^`]+)`/', $sql, $create_matches);
        $backup_tables = array_unique($create_matches[1]);
        
        $backup_table_counts = [];
        foreach($backup_tables as $table){
            // Count INSERT statements for this table
            // Using a more robust regex for INSERT INTO `table`
            $pattern = '/INSERT INTO `'.preg_quote($table).'`/i';
            preg_match_all($pattern, $sql, $insert_matches);
            $backup_table_counts[$table] = count($insert_matches[0]);
        }

        $records_in_backup = array_sum($backup_table_counts);

        // 2. Get Current Database Info
        $current_tables_info = [];
        $current_table_counts = [];
        $res = $this->conn->query("SHOW TABLES");
        while($row = $res->fetch_row()){
            $t = $row[0];
            $count_res = $this->conn->query("SELECT COUNT(*) FROM `{$t}`");
            $cnt = $count_res ? $count_res->fetch_row()[0] : 0;
            $current_table_counts[$t] = intval($cnt);
        }

        $current_tables = array_keys($current_table_counts);
        $records_in_db = array_sum($current_table_counts);

        // 3. Compare and Calculate Impact
        $tables_to_create = array_diff($backup_tables, $current_tables);
        $tables_to_drop_if_exists = array_intersect($backup_tables, $current_tables); // These will be dropped and recreated
        
        // This is what the user is worried about: Tables in DB but NOT in backup
        $tables_not_in_backup = array_diff($current_tables, $backup_tables);

        // Check if the SQL file explicitly drops tables not in its list (rare but possible)
        preg_match_all('/DROP TABLE IF EXISTS `([^`]+)`/', $sql, $drop_matches);
        $explicit_drops = array_unique($drop_matches[1]);
        $extra_tables_to_be_dropped = array_intersect($tables_not_in_backup, $explicit_drops);

        $analysis = [
            'backup_file' => $backup_file_name,
            'tables_in_backup' => count($backup_tables),
            'records_in_backup' => $records_in_backup,
            'current_tables' => count($current_tables),
            'current_records' => $records_in_db,
            'backup_table_counts' => $backup_table_counts,
            'current_table_counts' => $current_table_counts,
            'tables_to_create' => $tables_to_create,
            'tables_to_drop' => $extra_tables_to_be_dropped, // Tables that WILL be lost but aren't replaced
            'impact' => [
                'new_tables' => count($tables_to_create),
                'drop_tables' => count($extra_tables_to_be_dropped),
                'affected_tables' => count($tables_to_drop_if_exists),
                'total_changes' => count($tables_to_create) + count($extra_tables_to_be_dropped) + count($tables_to_drop_if_exists)
            ]
        ];

        return json_encode(['status' => 'success', 'analysis' => $analysis]);
    }

    function delete_backup(){
        $file = $_POST['file'] ?? '';
        $path = __DIR__ . "/../backups/" . basename($file);
        if(file_exists($path) && unlink($path)){
            $this->settings->set_flashdata('success', "Backup deleted successfully.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed']);
    }

    function save_settings(){
        extract($_POST);
        $fields = ['name', 'short_name', 'email', 'contact', 'address'];
        foreach($fields as $f){
            $val = $this->conn->real_escape_string($$f);
            if($this->conn->query("SELECT * FROM system_info WHERE meta_field = '$f'")->num_rows > 0)
                $this->conn->query("UPDATE system_info SET meta_value = '$val' WHERE meta_field = '$f'");
            else
                $this->conn->query("INSERT INTO system_info (meta_field, meta_value) VALUES ('$f', '$val')");
        }
        if(isset($content['welcome'])) file_put_contents(base_app . 'welcome.html', $content['welcome']);
        if(isset($content['about'])) file_put_contents(base_app . 'about.html', $content['about']);
        if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
            $fname = 'uploads/logo.' . pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
            if(move_and_compress_uploaded_file($_FILES['img']['tmp_name'], base_app.$fname))
                $this->update_system_info('logo', $fname);
        }
        $this->settings->set_flashdata('success', "Settings updated successfully.");
        return json_encode(['status' => 'success']);
    }

    function update_system_info($key, $value){
        $val = $this->conn->real_escape_string($value);
        if($this->conn->query("SELECT * FROM system_info WHERE meta_field = '$key'")->num_rows > 0)
            $this->conn->query("UPDATE system_info SET meta_value = '$val' WHERE meta_field = '$key'");
        else
            $this->conn->query("INSERT INTO system_info (meta_field, meta_value) VALUES ('$key', '$val')");
    }

    function get_db_tables_info(){
        $res = $this->conn->query("SHOW TABLES"); $tables = [];
        while($row = $res->fetch_row()) $tables[] = ['name' => $row[0], 'rows' => $this->conn->query("SELECT COUNT(*) FROM `{$row[0]}`")->fetch_row()[0]];
        return json_encode(['status' => 'success', 'tables' => $tables]);
    }

    function get_status_by_contact(){
        $contact = $this->conn->real_escape_string($_POST['contact']);
        $qry = $this->conn->query("SELECT t.code FROM transaction_list t INNER JOIN client_list c ON t.client_name = c.id WHERE c.contact = '$contact' ORDER BY t.date_created DESC LIMIT 1");
        if($qry->num_rows > 0) return json_encode(['status' => 'success', 'code' => $qry->fetch_assoc()['code']]);
        return json_encode(['status' => 'failed', 'msg' => 'No record found.']);
    }
}
