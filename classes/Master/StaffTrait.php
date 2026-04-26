<?php
trait StaffTrait {
    public function save_mechanic(){
        if (!CsrfProtection::validatePOST()) {
            return json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
        }
        extract($_POST);
        $data = "";
        $avatar_file = '';
        if(isset($_FILES['avatar']['tmp_name']) && !empty($_FILES['avatar']['tmp_name'])){
            $avatar = $_FILES['avatar'];
            $ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
            $fname = 'avatar_'.(isset($_POST['id']) ? $_POST['id'] : time()).'.'.$ext;
            $upload_path = base_app . 'uploads/avatars/';
            if(!is_dir($upload_path)) mkdir($upload_path, 0777, true);
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 2097152;
            if(in_array(strtolower($ext), $allowed_types) && $avatar['size'] <= $max_size){
                if(resize_image($avatar['tmp_name'], $upload_path.$fname, 300, 300, 70)){
                    $avatar_file = $fname;
                    if(isset($_POST['current_avatar']) && $_POST['current_avatar'] != 'default-avatar.jpg'){
                        $old_file = $upload_path.$_POST['current_avatar'];
                        if(file_exists($old_file) && $_POST['current_avatar'] != $fname) @unlink($old_file);
                    }
                    if(!empty($data)) $data .= ",";
                    $data .= " `avatar`='{$fname}' ";
                }
            }
        }
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id','current_avatar', 'csrf_token')) && $k != 'avatar'){
                if(!is_numeric($v)) $v = $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$v}' ";
            }
        }
        // Commission logic moved after save to get correct $mid for new records
        $old_commission = 0;
        if(!empty($id)){
            $old_comm_row = $this->conn->query("SELECT commission_percent FROM mechanic_list where id = '{$id}'")->fetch_array();
            $old_commission = $old_comm_row ? $old_comm_row['commission_percent'] : 0;
        }
        if(empty($id)){
            $sql = "INSERT INTO `mechanic_list` set {$data} ";
        } else {
            $old_salary_row = $this->conn->query("SELECT daily_salary FROM mechanic_list where id = '{$id}'")->fetch_array();
            $old_salary = $old_salary_row ? $old_salary_row['daily_salary'] : 0;
            $sql = "UPDATE `mechanic_list` set {$data} where id = '{$id}' ";
        }
        $save = $this->conn->query($sql);
        if($save){
            $mid = empty($id) ? $this->conn->insert_id : $id;
            $resp['status'] = 'success';
            if(empty($id) || (isset($old_salary) && isset($daily_salary) && $old_salary != $daily_salary)){
                $effective_date = date('Y-m-d');
                $this->conn->query("INSERT INTO `mechanic_salary_history` SET mechanic_id = '{$mid}', salary = '{$daily_salary}', effective_date = '{$effective_date}'");
            }
            // Log Commission History if changed
            if(empty($id) || (isset($commission_percent) && $old_commission != $commission_percent)){
                $effective_date = date('Y-m-d');
                $this->conn->query("INSERT INTO `mechanic_commission_history` SET mechanic_id = '{$mid}', commission_percent = '{$commission_percent}', effective_date = '{$effective_date}'");
            }
            $this->log_activity(empty($id) ? "Added Mechanic" : "Updated Mechanic", "Mechanics", $mid, "Name: " . ($firstname . ' ' . $lastname));
            if(empty($id)) $this->settings->set_flashdata('success',"New Mechanic successfully saved.");
            else $this->settings->set_flashdata('success',"Mechanic Details successfully updated.");
        } else {
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        return json_encode($resp);
    }

    public function save_mechanic_photo(){
        $resp = array();
        if(empty($_POST['id'])) return json_encode(['status' => 'failed', 'msg' => 'Mechanic ID is required']);
        $id = $_POST['id'];
        $upload_path = base_app . 'uploads/avatars/';
        if(!is_dir($upload_path)) mkdir($upload_path, 0777, true);
        if(isset($_FILES['avatar']) && !empty($_FILES['avatar']['tmp_name'])){
            $avatar = $_FILES['avatar'];
            $ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
            $fname = 'avatar_'.$id.'_'.time().'.'.$ext;
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if(!in_array(strtolower($ext), $allowed_types)) return json_encode(['status' => 'failed', 'msg' => 'Only JPG, JPEG, PNG & GIF files are allowed']);
            if($avatar['size'] > 2097152) return json_encode(['status' => 'failed', 'msg' => 'File size too large! Maximum 2MB allowed']);
            $old_avatar_qry = $this->conn->query("SELECT avatar FROM mechanic_list WHERE id = '{$id}'");
            $old_avatar = ($old_avatar_qry->num_rows > 0) ? $old_avatar_qry->fetch_assoc()['avatar'] : 'default-avatar.jpg';
            if(resize_image($avatar['tmp_name'], $upload_path.$fname, 300, 300, 70)){
                if($this->conn->query("UPDATE mechanic_list SET avatar = '{$fname}' WHERE id = '{$id}'")){
                    if($old_avatar != 'default-avatar.jpg' && $old_avatar != $fname && file_exists($upload_path.$old_avatar)) @unlink($upload_path.$old_avatar);
                    $resp['status'] = 'success';
                    $resp['avatar'] = $fname;
                } else {
                    $resp['status'] = 'failed';
                    $resp['msg'] = 'Database update failed';
                }
            } else {
                $resp['status'] = 'failed';
                $resp['msg'] = 'Failed to upload image';
            }
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'No image selected';
        }
        return json_encode($resp);
    }

    public function get_mechanic_photo(){
        if(empty($_POST['id'])) return json_encode(['status' => 'failed', 'msg' => 'Mechanic ID is required']);
        $id = $_POST['id'];
        $qry = $this->conn->query("SELECT avatar FROM mechanic_list WHERE id = '{$id}'");
        if($qry->num_rows > 0){
            return json_encode(['status' => 'success', 'avatar' => $qry->fetch_assoc()['avatar']]);
        }
        return json_encode(['status' => 'failed', 'msg' => 'Mechanic not found']);
    }

    function delete_mechanic(){
        extract($_POST);
        if($this->conn->query("UPDATE `mechanic_list` set `delete_flag` = 1 where id = '{$id}'")){
            $this->log_activity("Deleted Mechanic", "Mechanics", $id);
            $this->settings->set_flashdata('success'," Mechanic successfully deleted.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
    }

    function save_attendance(){
        extract($_POST);
        $curr_date = isset($curr_date) ? $curr_date : date('Y-m-d');
        $user_type = $this->settings->userdata('type');
        $user_mechanic_id = $this->settings->userdata('mechanic_id');
        $errors = 0;

        if(!isset($mechanic_id) || !is_array($mechanic_id)){
            return json_encode(['status' => 'failed', 'msg' => 'No mechanic data received.']);
        }

        foreach($mechanic_id as $k => $mid){
            // Security: Non-admin can only mark own attendance
            if($user_type != 1 && $mid != $user_mechanic_id) continue;

            $stat = isset($status[$mid]) ? $this->conn->real_escape_string($status[$mid]) : 'P';
            $mid  = $this->conn->real_escape_string($mid);

            $check = $this->conn->query("SELECT id FROM attendance_list WHERE mechanic_id = '{$mid}' AND curr_date = '{$curr_date}'")->num_rows;
            if($check > 0)
                $save = $this->conn->query("UPDATE attendance_list SET status = '{$stat}' WHERE mechanic_id = '{$mid}' AND curr_date = '{$curr_date}'");
            else
                $save = $this->conn->query("INSERT INTO attendance_list (mechanic_id, status, curr_date) VALUES ('{$mid}', '{$stat}', '{$curr_date}')");

            if(!$save) $errors++;
        }

        if($errors == 0){
            return json_encode(['status' => 'success', 'msg' => 'Attendance successfully saved.']);
        }
        return json_encode(['status' => 'failed', 'msg' => 'An error occurred while saving attendance.']);
    }

    function save_advance(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id', 'csrf_token'))){
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
            }
        }
        if(empty($id)) $sql = "INSERT INTO advance_payments set {$data}";
        else $sql = "UPDATE advance_payments set {$data} where id = '{$id}'";
        if($this->conn->query($sql)) return json_encode(['status'=>'success']);
        return json_encode(['status'=>'failed', 'err'=>$this->conn->error]);
    }

    function delete_advance(){
        extract($_POST);
        if($this->conn->query("DELETE FROM advance_payments where id = '{$id}'")) return json_encode(['status'=>'success']);
        return json_encode(['status'=>'failed']);
    }

    function update_salary_rate(){
        extract($_POST);
        if(empty($id) || empty($new_salary) || empty($effective_date)) return json_encode(array('status' => 'failed', 'msg' => 'Please fill all fields.'));
        if($this->conn->query("UPDATE mechanic_list set daily_salary = '{$new_salary}' where id = '{$id}'")){
            $this->conn->query("INSERT INTO `mechanic_salary_history` SET mechanic_id = '{$id}', salary = '{$new_salary}', effective_date = '{$effective_date}'");
            return json_encode(array('status' => 'success'));
        }
        return json_encode(array('status' => 'failed', 'msg' => $this->conn->error));
    }

    function delete_salary_history(){
        extract($_POST);
        if($this->conn->query("DELETE FROM `mechanic_salary_history` WHERE id = '{$id}'")) return json_encode(array('status' => 'success'));
        return json_encode(array('status' => 'failed', 'error' => $this->conn->error));
    }

    function update_history_entry(){
        extract($_POST);
        if($this->conn->query("UPDATE `mechanic_salary_history` SET salary = '{$h_salary}', effective_date = '{$h_date}' WHERE id = '{$h_id}'")){
            $m_id = $this->conn->query("SELECT mechanic_id FROM `mechanic_salary_history` WHERE id = '{$h_id}'")->fetch_array()['mechanic_id'];
            $latest = $this->conn->query("SELECT salary FROM `mechanic_salary_history` WHERE mechanic_id = '$m_id' ORDER BY effective_date DESC, id DESC LIMIT 1")->fetch_array();
            $this->conn->query("UPDATE `mechanic_list` SET daily_salary = '{$latest['salary']}' WHERE id = '$m_id'");
            return json_encode(array('status' => 'success'));
        }
        return json_encode(array('status' => 'failed'));
    }

    // =============================================
    // COMMISSION RATE MASTER FUNCTIONS
    // =============================================

    /**
     * Save a new commission rate for a mechanic.
     * - Inserts a row into mechanic_commission_history with the effective_date.
     * - Updates commission_percent in mechanic_list to the LATEST rate (by effective_date).
     */
    function update_commission_rate(){
        extract($_POST);
        if(empty($id) || !isset($new_rate) || empty($effective_date))
            return json_encode(['status' => 'failed', 'msg' => 'Saari fields bhari honi chahiye.']);

        $id             = $this->conn->real_escape_string($id);
        $new_rate       = floatval($new_rate);
        $effective_date = $this->conn->real_escape_string($effective_date);

        // Insert into history
        $ins = $this->conn->query("INSERT INTO `mechanic_commission_history` SET
            mechanic_id       = '{$id}',
            commission_percent = '{$new_rate}',
            effective_date    = '{$effective_date}'");

        if(!$ins)
            return json_encode(['status' => 'failed', 'msg' => $this->conn->error]);

        // Sync mechanic_list.commission_percent to the LATEST effective rate
        $latest = $this->conn->query("SELECT commission_percent FROM `mechanic_commission_history`
            WHERE mechanic_id = '{$id}'
            ORDER BY effective_date DESC, id DESC LIMIT 1")->fetch_assoc();
        if($latest)
            $this->conn->query("UPDATE `mechanic_list` SET commission_percent = '{$latest['commission_percent']}' WHERE id = '{$id}'");

        $this->log_activity("Updated Commission Rate", "Mechanics", $id, "New Rate: {$new_rate}% from {$effective_date}");
        return json_encode(['status' => 'success']);
    }

    /**
     * Edit an existing commission history row (rate + effective_date).
     * After editing, syncs mechanic_list to the new latest rate.
     */
    function update_commission_history_entry(){
        extract($_POST);
        if(empty($h_id) || !isset($h_rate) || empty($h_date))
            return json_encode(['status' => 'failed', 'msg' => 'Invalid data.']);

        $h_id   = $this->conn->real_escape_string($h_id);
        $h_rate = floatval($h_rate);
        $h_date = $this->conn->real_escape_string($h_date);

        if(!$this->conn->query("UPDATE `mechanic_commission_history` SET
            commission_percent = '{$h_rate}',
            effective_date     = '{$h_date}'
            WHERE id = '{$h_id}'"))
            return json_encode(['status' => 'failed', 'msg' => $this->conn->error]);

        // Sync mechanic_list to the latest effective rate
        $m_id = $this->conn->query("SELECT mechanic_id FROM `mechanic_commission_history` WHERE id = '{$h_id}'")->fetch_array()['mechanic_id'];
        $latest = $this->conn->query("SELECT commission_percent FROM `mechanic_commission_history`
            WHERE mechanic_id = '{$m_id}'
            ORDER BY effective_date DESC, id DESC LIMIT 1")->fetch_assoc();
        if($latest)
            $this->conn->query("UPDATE `mechanic_list` SET commission_percent = '{$latest['commission_percent']}' WHERE id = '{$m_id}'");

        return json_encode(['status' => 'success']);
    }

    /**
     * Delete a single commission history record.
     * After deletion, syncs mechanic_list to the new latest rate (or 0 if none left).
     */
    function delete_commission_history(){
        extract($_POST);
        if(empty($id)) return json_encode(['status' => 'failed', 'msg' => 'ID required.']);
        $id = $this->conn->real_escape_string($id);

        // Get mechanic_id before deletion
        $row = $this->conn->query("SELECT mechanic_id FROM `mechanic_commission_history` WHERE id = '{$id}'")->fetch_assoc();
        if(!$row) return json_encode(['status' => 'failed', 'msg' => 'Record not found.']);
        $m_id = $row['mechanic_id'];

        if(!$this->conn->query("DELETE FROM `mechanic_commission_history` WHERE id = '{$id}'"))
            return json_encode(['status' => 'failed', 'msg' => $this->conn->error]);

        // Sync mechanic_list to remaining latest rate
        $latest = $this->conn->query("SELECT commission_percent FROM `mechanic_commission_history`
            WHERE mechanic_id = '{$m_id}'
            ORDER BY effective_date DESC, id DESC LIMIT 1")->fetch_assoc();
        $sync_rate = $latest ? $latest['commission_percent'] : 0;
        $this->conn->query("UPDATE `mechanic_list` SET commission_percent = '{$sync_rate}' WHERE id = '{$m_id}'");

        return json_encode(['status' => 'success']);
    }
}
