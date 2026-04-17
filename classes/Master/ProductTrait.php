<?php
trait ProductTrait {
    function save_service(){
        // CSRF Validation
        if (!CsrfProtection::validatePOST()) {
            return json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
        }
        
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id', 'csrf_token'))){
                if(!empty($data)) $data .=",";
                $v = $this->conn->real_escape_string($v);
                $data .= " `{$k}`='{$v}' ";
            }
        }
        $check = $this->conn->query("SELECT * FROM `service_list` where `name` = '{$name}' ".(!empty($id) ? " and id != '{$id}' " : "")." ")->num_rows;
        if($this->capture_err())
            return $this->capture_err();
        if($check > 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "Service Name already exists.";
            return json_encode($resp);
            exit;
        }
        if(empty($id)){
            $sql = "INSERT INTO `service_list` set {$data} ";
        }else{
            $sql = "UPDATE `service_list` set {$data} where id = '{$id}' ";
        }
        $save = $this->conn->query($sql);
        if($save){
            $resp['status'] = 'success';
            if(empty($id))
                $resp['msg'] = "New Service successfully saved.";
            else
                $resp['msg'] = " Service successfully updated.";
        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        if($resp['status'] == 'success')
            $this->settings->set_flashdata('success',$resp['msg']);
        return json_encode($resp);
    }

    function delete_service(){
        extract($_POST);
        $del = $this->conn->query("UPDATE `service_list` set `delete_flag` = 1 where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success'," Service successfully deleted.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    function save_product(){
        if (!CsrfProtection::validatePOST()) {
            return json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
        }
        extract($_POST);
        $check = $this->conn->query("SELECT * FROM `product_list` where `name` = '{$name}' ".(!empty($id) ? " and id != '{$id}' " : "")." ")->num_rows;
        if($check > 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "Product Name already exists. Please use a unique name.";
            return json_encode($resp);
        }
        $resp = array('status'=>'success');
        $cost_price = !empty($cost_price) ? $cost_price : 0;
        if(empty($id)){
            $stmt = $this->conn->prepare("INSERT INTO `product_list` (`name`, `description`, `cost_price`, `price`, `status`) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddi", $name, $description, $cost_price, $price, $status);
        } else {
            $stmt = $this->conn->prepare("UPDATE `product_list` SET `name`=?, `description`=?, `cost_price`=?, `price`=?, `status`=? WHERE id = ?");
            $stmt->bind_param("ssddii", $name, $description, $cost_price, $price, $status, $id);
        }
        if($stmt->execute()){
            $resp['id'] = empty($id) ? $this->conn->insert_id : $id;
            $resp['msg'] = 'Product Details successfully saved.';
            if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
                if(!is_dir(base_app.'uploads/products'))
                    mkdir(base_app.'uploads/products', 0777, true);
                $ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
                $fname = 'uploads/products/'.$resp['id'].'.'.$ext;
                if(is_file(base_app.$fname)) unlink(base_app.$fname);
                $move = move_and_compress_uploaded_file($_FILES['img']['tmp_name'], base_app.$fname);
                if($move){
                    $img_stmt = $this->conn->prepare("UPDATE `product_list` SET `image_path` = ? WHERE id = ?");
                    $img_stmt->bind_param("si", $fname, $resp['id']);
                    $img_stmt->execute();
                    $img_stmt->close();
                }
            }
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Database Error: ' . $stmt->error;
        }
        $stmt->close();
        if($resp['status'] == 'success')
            $this->settings->set_flashdata('success', $resp['msg']);
        return json_encode($resp);
    }

    function delete_product(){
        extract($_POST);
        $resp = array('status'=>'success');
        $stmt = $this->conn->prepare("DELETE FROM `product_list` WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()){
            $resp['msg'] = 'Product Details successfully deleted.';
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Database Error: ' . $stmt->error;
        }
        $stmt->close();
        if($resp['status'] == 'success')
            $this->settings->set_flashdata('success', $resp['msg']);
        return json_encode($resp);
    }

    function save_inventory(){
        if (!CsrfProtection::validatePOST()) {
            return json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
        }
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id', 'csrf_token'))){
                if(!empty($data)) $data .=",";
                $v = $this->conn->real_escape_string($v);
                $data .= " `{$k}`='{$v}' ";
            }
        }
        if(empty($id)){
            $sql = "INSERT INTO `inventory_list` set {$data} ";
        }else{
            $sql = "UPDATE `inventory_list` set {$data} where id = '{$id}' ";
        }
        $save = $this->conn->query($sql);
        if($save){
            $resp['status'] = 'success';
            if(empty($id))
                $resp['msg'] = "New Stock has been saved successfully.";
            else
                $resp['msg'] = " Stock has been updated successfully.";
        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        if($resp['status'] == 'success')
            $this->settings->set_flashdata('success',$resp['msg']);
        return json_encode($resp);
    }

    function delete_inventory(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `inventory_list` where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success'," Stock has been deleted successfully.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }
}
