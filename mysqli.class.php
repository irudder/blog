<?php

class mysqli{
    private $icon;
    private $filename;
    private $datetime;

	function __construct() {
		        
        $dbconfi['DB_NAME']='192.168.62.196';
		$dbconfi['DB_USER']='zy';
		$dbconfi['DB_PWD']='zy';
		$dbconfi['DATABASE_NAME']='hcq';

		$this->icon = new mysqli($dbconfi['DB_NAME'],$dbconfi['DB_USER'],$dbconfi['DB_PWD'],$dbconfi['DATABASE_NAME']);

		if(mysqli_connect_errno())
		{
		    echo mysqli_connect_error();
		}
		
		$this->filename='../../uploads/log/'.date('Y-m-d',time()).'_sql.log';
		$this->datetime= date('H:i:s',time());
		$this->icon->query("set names utf8");
		return $this->icon;
	}

    /***
    **更新表
    ***/
	function update($sql){
		error_log($this->datetime.':'.$sql."\r\n", 3, $this->filename);//write_log
		return $this->icon->query($sql);
	}
    
    /**
    **返回查询一行数据
    **/
	public function query($sql){
		error_log($this->datetime.':'.$sql."\r\n", 3, $this->filename);//write_log
		$rett=$this->icon->query($sql);
		$rr=$rett->fetch_assoc();
		return $rr;
	}
    
    /**
    **写入一行数据
    **/
    public function write($sql){
        error_log($this->datetime.':'.$sql."\r\n", 3, $this->filename);//write_log
		$rett=$this->icon->query($sql);
        return $rett;
    }
    

	function __destruct(){
		$this->icon->close();
	}
}

?>