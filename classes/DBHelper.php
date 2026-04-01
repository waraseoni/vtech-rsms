<?php
/**
 * Database Helper Class
 * Provides prepared statement methods for secure database queries
 */
class DBHelper {
    
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function execute($stmt, $params = []) {
        if (!empty($params)) {
            $types = '';
            $values = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $values[] = $param;
            }
            
            $stmt->bind_param($types, ...$values);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    public function fetch($stmt) {
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function fetchAll($stmt) {
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function getRow($sql, $params = []) {
        $stmt = $this->prepare($sql);
        if (!$stmt) return null;
        
        if (!empty($params)) {
            $this->execute($stmt, $params);
        } else {
            $stmt->execute();
        }
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function getRows($sql, $params = []) {
        $stmt = $this->prepare($sql);
        if (!$stmt) return [];
        
        if (!empty($params)) {
            $this->execute($stmt, $params);
        } else {
            $stmt->execute();
        }
        
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function executeQuery($sql, $params = []) {
        $stmt = $this->prepare($sql);
        if (!$stmt) return false;
        
        if (!empty($params)) {
            $this->execute($stmt, $params);
        } else {
            $stmt->execute();
        }
        
        return $stmt;
    }
    
    public function insert($table, $data) {
        $columns = array_keys($data);
        $values = array_values($data);
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . 
               implode(', ', array_fill(0, count($values), '?')) . ")";
        
        $stmt = $this->prepare($sql);
        if (!$stmt) return false;
        
        $this->execute($stmt, $values);
        return $stmt->insert_id;
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = "{$key} = ?";
        }
        
        $values = array_values($data);
        $values = array_merge($values, $whereParams);
        
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
        
        $stmt = $this->prepare($sql);
        if (!$stmt) return false;
        
        $this->execute($stmt, $values);
        return $stmt->affected_rows;
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $this->prepare($sql);
        if (!$stmt) return false;
        
        $this->execute($stmt, $params);
        return $stmt->affected_rows;
    }
    
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    public function getLastError() {
        return $this->conn->error;
    }
}

function db($conn = null) {
    global $conn;
    return new DBHelper($conn);
}
