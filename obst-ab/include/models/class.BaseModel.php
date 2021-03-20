<?php
    abstract class BaseModel {
    
        protected $mysql;
        public $table;
        
        public function __construct(simpleMySQL &$mysql)
        {
            $this->mysql = $mysql;
        }
        
        public function count($where='')
        {
            if(!empty($where))
                $where = "WHERE $where";
            $query = $this->mysql->sql_query("SELECT COUNT(*) AS counted FROM ".$this->table." $where");
            if($query === false)
                return false;
            
            return intval($query[0]['counted']);
        }
        
        public function exists($id)
        {
            $stmt = "SELECT COUNT(*) FROM ".$this->table." WHERE id = '$id' LIMIT 1";
            
            $query = $this->mysql->sql_query($stmt);
            
            if($query === false || count($query) != 1)
                return false;
            else
                return true;
        }
        
        public function getById($id)
        {
            $stmt = "SELECT * FROM ".$this->table." WHERE id = '$id' LIMIT 1";
            
            $query = $this->mysql->sql_query($stmt);
            
            if($query === false || count($query) != 1)
                return false;
            
            return $query[0];
        }

        public function select($stmt)
        {
            return $this->mysql->sql_query($stmt);
        }
        
        public function get($columns='*', $order='', $where='', $limit='', $offset='')
        {
            if(!empty($order))
                $order = "ORDER BY $order";
            if(!empty($limit) && !empty($offset))
                $limit = "LIMIT $offset,$limit";
            elseif(!empty($limit))
                $limit = "LIMIT $limit";
            if(!empty($where))
                $where = "WHERE $where";

            $stmt = "SELECT $columns FROM ".$this->table." $where $order $limit";
            
            return $this->select($stmt);
        }
        
        public function insert($columns, $values)
        {
            if(is_array($columns))
            {
                $i = 0;
                $columns = $this->array_prepare($columns, "`");
                $columns = implode(',', $columns);
            }
            if(is_array($values))
            {
                $i = 0;
                $values = $this->array_prepare($values, "'");
                $values = implode(',', $values);
            }
                
            $stmt = "INSERT INTO ".$this->table." ($columns) VALUES ($values)";
            
            $query = $this->mysql->sql_query($stmt);
            
            if(!$query)
                return false;
            else
                return true;
        }
        
        public function update($set, $where, $limit=1)
        {
            if(is_array($set))
            {
                $i = 0;
                $set = implode(',', $columns);
            }
            
            $stmt = "UPDATE ".$this->table." SET $set WHERE $where LIMIT $limit";
            
            $query = $this->mysql->sql_query($stmt);
            
            if(!$query)
                return false;
            else
                return true;
        }
        
        public function array_prepare(&$array, $encapsulator)
        {
            $i = 0;
            for($i; $i<count($array); $i++)
                $array[$i] = $encapsulator.$array[$i].$encapsulator;
            
            return $array;
        }
        
        public function delete($where, $limit=1)
        {
            if(!empty($limit))
                $limit = "LIMIT ".$limit;
            
            $where = "WHERE $where";
            if(empty($where))
                throw new Exception("where must have a value!");
                
            $stmt = "DELETE FROM ".$this->table." $where $limit";
            
            $query = $this->mysql->sql_query($stmt);
            if($query)
                return true;
            else
                return false;
        }
        
        public function deleteById($id)
        {
            return $this->delete("id='$id'",1);
        }
        
    };
?>
