<?php
/**
 * 封装的PDO类
 * 简易连接调用 直接执行sql语句
 * 
 */
 
class CPDO{
    
    protected static $_instance = null;
    protected static $db_name = '';
    protected $dsn;
    protected $dbh;
    
    public function __construct($db_host, $db_user, $db_pwd, $db_name, $db_char) {
        try {
            $this->dsn = 'mysql:host='.$db_host.';dbname='.$db_name;
            $this->dbh = new PDO($this->dsn, $db_user, $db_pwd);
            self::$db_name = $db_name;
            $this->dbh->exec('set character_set_connection='.$db_char.',character_set_results='.$db_char.',character_set_client=binary');
        } catch (PDOException $e) {
            $this->output_error($e->getMessage());
        }
    }
    
    // 禁止克隆
    private function __clone() {}
    
    // 单例模式连接
    public static function getInstance($db_host, $db_user, $db_pwd, $db_name, $db_char) {
        // 多连接
        if (self::$_instance === null || $db_name!==self::$db_name) {
        
            self::$_instance = new self($db_host, $db_user, $db_pwd, $db_name, $db_char);
        }
        return self::$_instance;
    }
    
    // 查询语句
    public function query($str_sql, $query_model='All', $debug=false){
        if($debug === true){
            $this->debug($str_sql);
        }
        $recordset = $this->dbh->query($str_sql);
        $this->get_pdo_error();
        if ($recordset) {
            $recordset->setFetchMode(PDO::FETCH_ASSOC);
            if ($query_model=='All') {
                $result = $recordset->fetchAll();
            } else if ($query_model=='Row') {
                $result = $recordset->fetch();
            }else{
                $result = false;
            }
        }else{
            $result = null;
        }
        return $result;
    }
    
    // 执行sql语句
    public function exec_sql($str_sql, $debug=false){
        if ($debug===true) {
            $this->debug($str_sql);
        }
        $result = $this->dbh->exec($str_sql);
        $this->get_pdo_error();
        return $result;
    }
    
    // 事务提交
    private function commit(){
        $this->dbh->commit;
    }
    
    // 事务回滚
    private function rollback(){
        $this->dbh->rollback;
    }
    
    // transaction 多条语句事务提交
    public function exec_transaction($array_sql){
        $retval = 1;
        $this->beginTransaction();
        foreach($array_sql as $str_sql){
            if($this->exec_sql($str_sql) == 0){
                $retval = 0;
            }
        }
        if($retval == 0){
            $this->rollback;
            return false;
        } else {
            $this->commit;
            return true;
        }
    }
    
    // 调试模式
    private function debug($debuginfo){
        echo "<pre>".var_dump($debuginfo)."</pre>";
        exit;
    }
    
    // 获取数据库操作错误
    private function get_pdo_error(){
        if($this->dbh->errorCode() != '00000'){
            $array_error = $this->dbh->errorInfo();
            $this->output_error($array_error);
        }
    }
    
    // 输入错误
    private function output_error($str_error_msg){
        throw new Exception('Mysql Error:'.json_encode($str_error_msg));
    }
    
    // 销毁数据库连接
    public function destruct(){
        $this->dbh = null;
    }
    
}


// 第二次封装
class PDODB{
    protected $pdo;
    protected $res;
    protected $config;
     
    /*构造函数*/
    function __construct($config){
        $this->Config = $config;
        $this->connect();
    }
     
    /*数据库连接*/
    public function connect(){
        $this->pdo = new PDO($this->Config['dsn'], $this->Config['name'], $this->Config['password']);
        $this->pdo->query('set names utf8;');
        //把结果序列化成stdClass
        //$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        //自己写代码捕获Exception
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->file_name = '/log/'.date('Y-m-d')."_log.log";
        $this->logtime = date('H:i:s',time());
    }
     
    /*数据库关闭*/
    public function close(){
        $this->pdo = null;
    }
     
    public function query($sql){
        $res = $this->pdo->query($sql);
        if($res){
            $this->res = $res;
        }
    }
    public function exec($sql){
        $res = $this->pdo->exec($sql);
        if($res){
            $this->res = $res;
        }
    }
    public function fetchAll(){
        return $this->res->fetchAll();
    }
    public function fetch(){
        return $this->res->fetch();
    }
    public function fetchColumn(){
        return $this->res->fetchColumn();
    }
    public function lastInsertId(){
        return $this->res->lastInsertId();
    }
     
    /**
     * 参数说明
     * int              $debug      是否开启调试，开启则输出sql语句
     *                              0   不开启
     *                              1   开启
     *                              2   开启并终止程序
     * int              $mode       返回类型
     *                              0   返回多条记录
     *                              1   返回单条记录
     *                              2   返回行数
     * string/array     $table      数据库表，两种传值模式
     *                              普通模式：
     *                              'tb_member, tb_money'
     *                              数组模式：
     *                              array('tb_member', 'tb_money')
     * string/array     $fields     需要查询的数据库字段，允许为空，默认为查找全部，两种传值模式
     *                              普通模式：
     *                              'username, password'
     *                              数组模式：
     *                              array('username', 'password')
     * string/array     $sqlwhere   查询条件，允许为空，两种传值模式
     *                              普通模式：
     *                              'and type = 1 and username like "%os%"'
     *                              数组模式：
     *                              array('type = 1', 'username like "%os%"')
     * string           $orderby    排序，默认为id倒序
     */
    public function select($debug, $mode, $table, $fields="*", $sqlwhere="", $orderby="tbid desc"){
        //参数处理
        if(is_array($table)){
            $table = implode(', ', $table);
        }
        if(is_array($fields)){
            $fields = implode(', ', $fields);
        }
        if(is_array($sqlwhere)){
            $sqlwhere = ' and '.implode(' and ', $sqlwhere);
        }
        //数据库操作
        if($debug === 0){
            if($mode === 2){
                $this->query("select count(tbid) from $table where 1=1 $sqlwhere");
                $return = $this->fetchColumn();
            }else if($mode === 1){
                $this->query("select $fields from $table where 1=1 $sqlwhere order by $orderby");
                $return = $this->fetch();
            }else{
                $this->query("select $fields from $table where 1=1 $sqlwhere order by $orderby");
                $return = $this->fetchAll();
            }
            return $return;
        }else{
            if($mode === 2){
                echo "select count(tbid) from $table where 1=1 $sqlwhere";
            }else if($mode === 1){
                echo "select $fields from $table where 1=1 $sqlwhere order by $orderby";
            }
            else{
                echo "select $fields from $table where 1=1 $sqlwhere order by $orderby";
            }
            if($debug === 2){
                exit;
            }
        }
    }
     
    /**
     * 参数说明
     * int              $debug      是否开启调试，开启则输出sql语句
     *                              0   不开启
     *                              1   开启
     *                              2   开启并终止程序
     * int              $mode       返回类型
     *                              0   无返回信息
     *                              1   返回执行条目数
     *                              2   返回最后一次插入记录的id
     * string/array     $table      数据库表，两种传值模式
     *                              普通模式：
     *                              'tb_member, tb_money'
     *                              数组模式：
     *                              array('tb_member', 'tb_money')
     * string/array     $set        需要插入的字段及内容，两种传值模式
     *                              普通模式：
     *                              'username = "test", type = 1, dt = now()'
     *                              数组模式：
     *                              array('username = "test"', 'type = 1', 'dt = now()')
     */
    public function insert($debug, $mode, $table, $set){
        //参数处理
        if(is_array($table)){
            $table = implode(', ', $table);
        }
        if(is_array($set)){
            $set = implode(', ', $set);
        }
        //数据库操作
        if($debug === 0){
            if($mode === 2){
                $this->query("insert into $table set $set");
                $return = $this->lastInsertId();
            }else if($mode === 1){
                $this->exec("insert into $table set $set");
                $return = $this->res;
            }else{
                $this->query("insert into $table set $set");
                $return = NULL;
            }
            return $return;
        }else{
            echo "insert into $table set $set";
            if($debug === 2){
                exit;
            }
        }
    }
     
    /**
     * 参数说明
     * int              $debug      是否开启调试，开启则输出sql语句
     *                              0   不开启
     *                              1   开启
     *                              2   开启并终止程序
     * int              $mode       返回类型
     *                              0   无返回信息
     *                              1   返回执行条目数
     * string           $table      数据库表，两种传值模式
     *                              普通模式：
     *                              'tb_member, tb_money'
     *                              数组模式：
     *                              array('tb_member', 'tb_money')
     * string/array     $set        需要更新的字段及内容，两种传值模式
     *                              普通模式：
     *                              'username = "test", type = 1, dt = now()'
     *                              数组模式：
     *                              array('username = "test"', 'type = 1', 'dt = now()')
     * string/array     $sqlwhere   修改条件，允许为空，两种传值模式
     *                              普通模式：
     *                              'and type = 1 and username like "%os%"'
     *                              数组模式：
     *                              array('type = 1', 'username like "%os%"')
     */
    public function update($debug, $mode, $table, $set, $sqlwhere=""){
        //参数处理
        if(is_array($table)){
            $table = implode(', ', $table);
        }
        if(is_array($set)){
            $set = implode(', ', $set);
        }
        if(is_array($sqlwhere)){
            $sqlwhere = ' and '.implode(' and ', $sqlwhere);
        }
        //数据库操作
        if($debug === 0){
            if($mode === 1){
                $this->exec("update $table set $set where 1=1 $sqlwhere");
                $return = $this->res;
            }else{
                $this->query("update $table set $set where 1=1 $sqlwhere");
                $return = NULL;
            }
            return $return;
        }else{
            echo "update $table set $set where 1=1 $sqlwhere";
            if($debug === 2){
                exit;
            }
        }
    }
     
    /**
     * 参数说明
     * int              $debug      是否开启调试，开启则输出sql语句
     *                              0   不开启
     *                              1   开启
     *                              2   开启并终止程序
     * int              $mode       返回类型
     *                              0   无返回信息
     *                              1   返回执行条目数
     * string           $table      数据库表
     * string/array     $sqlwhere   删除条件，允许为空，两种传值模式
     *                              普通模式：
     *                              'and type = 1 and username like "%os%"'
     *                              数组模式：
     *                              array('type = 1', 'username like "%os%"')
     */
    public function delete($debug, $mode, $table, $sqlwhere=""){
        //参数处理
        if(is_array($sqlwhere)){
            $sqlwhere = ' and '.implode(' and ', $sqlwhere);
        }
        //数据库操作
        if($debug === 0){
            if($mode === 1){
                $this->exec("delete from $table where 1=1 $sqlwhere");
                $return = $this->res;
            }else{
                $this->query("delete from $table where 1=1 $sqlwhere");
                $return = NULL;
            }
            return $return;
        }else{
            echo "delete from $table where 1=1 $sqlwhere";
            if($debug === 2){
                exit;
            }
        }
    }
}