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
        if(!isset($_FILES['backup_file'])) return json_encode(['status' => 'failed']);
        $sql = file_get_contents($_FILES['backup_file']['tmp_name']);
        preg_match_all('/CREATE TABLE `([^`]+)`/', $sql, $m);
        return json_encode(['status' => 'success', 'analysis' => ['tables_in_backup' => count($m[1])]]);
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
