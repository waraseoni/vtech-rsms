<?php
trait FinancialTrait {
    function save_expense(){
        if (!CsrfProtection::validatePOST()) return json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k, array('id', 'csrf_token'))){
                if(!empty($data)) $data .=",";
                $v = $this->conn->real_escape_string($v);
                $data .= " `{$k}`='{$v}' ";
            }
        }
        if(empty($id)) $sql = "INSERT INTO `expense_list` set {$data}";
        else $sql = "UPDATE `expense_list` set {$data} where id = '{$id}'";
        if($this->conn->query($sql)) return json_encode(['status'=>'success']);
        return json_encode(['status'=>'failed', 'error'=>$this->conn->error]);
    }

    function delete_expense(){
        extract($_POST);
        if($this->conn->query("DELETE FROM `expense_list` where id = '{$id}'")) return json_encode(['status'=>'success']);
        return json_encode(['status'=>'failed']);
    }

    function save_lender(){
        if (!CsrfProtection::validatePOST()) return json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id', 'csrf_token'))){
                if(!is_numeric($v)) $v = $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$v}' ";
            }
        }
        if(empty($id)) $sql = "INSERT INTO `lender_list` set {$data} ";
        else $sql = "UPDATE `lender_list` set {$data} where id = '{$id}' ";
        if($this->conn->query($sql)){
            $this->settings->set_flashdata('success', empty($id) ? "New Lender successfully saved." : "Lender details updated successfully.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'err' => $this->conn->error]);
    }

    function delete_lender(){
        extract($_POST);
        if($this->conn->query("DELETE FROM `lender_list` where id = '{$id}'")){
            $this->settings->set_flashdata('success',"Lender successfully deleted.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
    }

    function save_loan_payment(){
        if (!CsrfProtection::validatePOST()) return json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id', 'csrf_token'))){
                if(!is_numeric($v)) $v = $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$v}' ";
            }
        }
        if(empty($id)) $sql = "INSERT INTO `loan_payments` set {$data} ";
        else $sql = "UPDATE `loan_payments` set {$data} where id = '{$id}' ";
        if($this->conn->query($sql)){
            $this->settings->set_flashdata('success',"EMI Payment successfully saved.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
    }

    function delete_loan_payment(){
        extract($_POST);
        if($this->conn->query("DELETE FROM `loan_payments` where id = '{$id}'")){
            $this->settings->set_flashdata('success',"Payment record deleted.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
    }

    function save_client_loan(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id', 'months', 'csrf_token'))){ 
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
            }
        }
        if(!empty($id)) $sql = "UPDATE `client_loans` SET {$data} WHERE id = '{$id}'";
        else $sql = "INSERT INTO `client_loans` SET {$data}";
        if($this->conn->query($sql)){
            $this->settings->set_flashdata('success', "Loan details successfully saved.");
            return json_encode(['status' => 'success']);
        } 
        return json_encode(['status' => 'failed', 'err' => $this->conn->error]);
    }

    function save_client_payment(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id', 'csrf_token'))){
                if(!empty($data)) $data .= ",";
                if($v === '' && in_array($k, ['loan_id', 'job_id', 'bill_no'])) $data .= " `{$k}`=NULL ";
                else $data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
            }
        }
        if(empty($id)) $sql = "INSERT INTO `client_payments` SET {$data}";
        else $sql = "UPDATE `client_payments` SET {$data} WHERE id = '{$id}'";
        if($this->conn->query($sql)) return json_encode(['status' => 'success']);
        return json_encode(['status' => 'failed', 'msg' => $this->conn->error]);
    }

    function save_payment(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id', 'csrf_token'))){
                if(!empty($data)) $data .= ",";
                if($v === '' && in_array($k, ['loan_id', 'job_id', 'bill_no'])) $data .= " `{$k}`=NULL ";
                else $data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
            }
        }
        if(empty($id)) $sql = "INSERT INTO `client_payments` SET {$data}";
        else $sql = "UPDATE `client_payments` SET {$data} WHERE id = '{$id}'";
        if($this->conn->query($sql)) return json_encode(['status' => 'success', 'msg' => 'Payment saved successfully.']);
        return json_encode(['status' => 'failed', 'msg' => $this->conn->error]);
    }

    function get_payment(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if(!$id) return json_encode(['status' => 'failed', 'msg' => 'Invalid ID']);
        $qry = $this->conn->query("SELECT * FROM client_payments WHERE id = '{$id}'");
        if($qry->num_rows > 0) return json_encode(['status' => 'success', 'data' => $qry->fetch_assoc()]);
        return json_encode(['status' => 'failed', 'msg' => 'Payment not found']);
    }

    function delete_payment(){
        extract($_POST);
        $id = isset($id) ? intval($id) : 0;
        if(!$id) return json_encode(['status' => 'failed', 'msg' => 'Invalid ID']);
        if($this->conn->query("DELETE FROM client_payments WHERE id = '$id'")) return json_encode(['status' => 'success', 'msg' => 'Payment deleted successfully.']);
        return json_encode(['status' => 'failed', 'msg' => $this->conn->error]);
    }

    function close_loan(){
        extract($_POST);
        if($this->conn->query("UPDATE `client_loans` SET `status` = 0 WHERE id = '{$id}'")){
            $this->settings->set_flashdata('success', "Loan successfully closed.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
    }

    function delete_client_loan(){
        extract($_POST);
        if($this->conn->query("DELETE FROM `client_loans` WHERE id = '{$id}'")){
            $this->settings->set_flashdata('success', "Loan record deleted successfully.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
    }
}
