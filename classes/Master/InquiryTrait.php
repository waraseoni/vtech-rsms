<?php
trait InquiryTrait {
    function save_message(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id'))){
                if(!is_numeric($v))
                    $v = $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$v}' ";
            }
        }
        if(empty($id)){
            $sql = "INSERT INTO `message_list` set {$data} ";
        }else{
            $sql = "UPDATE `message_list` set {$data} where id = '{$id}' ";
        }
        
        $save = $this->conn->query($sql);
        if($save){
            $resp['status'] = 'success';
            if(empty($id))
                $resp['msg'] = "Your message has successfully sent.";
            else
                $resp['msg'] = "Message details has been updated successfully.";
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = "An error occured.";
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        if($resp['status'] =='success' && !empty($id))
            $this->settings->set_flashdata('success',$resp['msg']);
        if($resp['status'] =='success' && empty($id))
            $this->settings->set_flashdata('pop_msg',$resp['msg']);
        return json_encode($resp);
    }
    
    function delete_message(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `message_list` where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Message has been deleted successfully.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    function delete_img(){
        extract($_POST);
        if(is_file($path)){
            if(unlink($path)){
                $resp['status'] = 'success';
            }else{
                $resp['status'] = 'failed';
                $resp['error'] = 'failed to delete '.$path;
            }
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = 'Unkown '.$path.' path';
        }
        return json_encode($resp);
    }
}
