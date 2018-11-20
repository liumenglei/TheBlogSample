<?php
    /**
     * EloquentRepository.php
     * 
     * Created by PhpStorm.
     * author: liuml
     * DateTime: 2018/9/11  18:14
     */
    // namespace App\Repositories;

    // use Illuminate\Support\Facades\DB;

    class EloquentRepository
    {
        /**
         * @var \Illuminate\Database\Eloquent\Model
         */
        protected $model;
        /**
         * @var 批量插入或更新需要设置的key
         */
        protected $insertKey;
        /**
         * @var 自定义表名
         */
        protected $table;
        /**
         * @var 将要执行的sql
         */
        protected $waitSql;
        /**
         * @var 批量更新需要设置的更新的字段
         */
        protected $updateKeyVal;
        /**
         * @var 创建时间字段
         */
        protected $created_at = 'created_at';
        /**
         * @var 更新时间字段
         */
        protected $updated_at = 'updated_at';
        /**
         * @var 时间格式
         */
        protected $timeFormat = 'Y-m-d H:i:s';

        
        /**
         * uOrCreate 批量更新或创建
         * @param string $table
         * @param string $key
         * @param array  $data
         * author: liuml  
         * DateTime: 2018/9/13  15:33
         */
        public function uOrCreate(array $data)
        {
            $sql = '';
            foreach ($data as $key => $vo) {
                // 自动填充时间字段
                $this->addTimeField($vo);

                $sql .= '(';
                array_map(function($v) use (&$sql) {
                    $sql .= '"' . $v . '",';
                }, $vo);
                $sql = rtrim($sql, ',') . '),';
                empty($this->insertKey) && $this->setInsertKey(array_keys($vo));
                empty($this->updateKeyVal) && $this->setUpdateKey(array_keys($vo));
            }
            $table         = $this->table ? : 'default_table';//$this->model->getTable();
            $sqlHead       = "INSERT INTO {$table} ({$this->insertKey}) values ";
            $sql           = rtrim($sql, ',') . " ON DUPLICATE KEY UPDATE {$this->updateKeyVal};";
            $this->waitSql = $sqlHead . $sql;
            return $this;
        }

        /**
         * addTimeField 自动填充时间字段
         * @param $arr
         * @return mixed
         * @author   liuml  
         * @DateTime 2018/9/13  19:45
         */
        public function addTimeField(&$arr)
        {
            $time = time();

            if ($this->created_at) {
                $arr[$this->created_at] = $this->timeFormat == 'time' ? $time : date($this->timeFormat, $time);
            }

            if ($this->updated_at) {
                $arr[$this->updated_at] = $this->timeFormat == 'time' ? $time : date($this->timeFormat, $time);
            }

            return $arr;
        }

        /**
         * 设置更新语句的键值对
         * setUpdateKey
         * @param $keyVal 可以传入k=v的字符串(多个之间以英文逗号隔开)，或者数组[ 'title'=>'values(title)', 'details'=>'values(details)'],或者['title','details']<注：传入第二种数组格式的话默认会转为第一种格式数组后进行操作>;
         * @return $this
         * @author   liuml  
         * @DateTime 2018/9/13  15:33
         */
        public function setUpdateKey($keyVal)
        {
            // 是字符串则直接返回
            if (is_string($keyVal)) {
                $this->updateKeyVal = $keyVal;
                return $this;
            }
            // 是数组则进一步处理
            if (is_array($keyVal)) {
                // 判断传入的数组是否是关联数组
                $keyVal = $this->is_assoc($keyVal) ? : $this->keyToKV($keyVal);
                // 处理数组成 k=v 格式字符串
                $this->updateKeyVal = $this->arrayToKv($keyVal);
            } else {
                throw new \Exception('The setUpdateKey function needs to pass in a string or an array');
            }
            return $this;
        }

        /**
         * is_assoc 判断是否关联数组还是索引数组
         * @param $var
         * @return bool
         * @author   liuml  <liumenglei0211@163.com>
         * @DateTime 2018/9/14  17:20
         */
        private function is_assoc($var)
        {
            return is_array($var) && array_diff_key($var, array_keys(array_keys($var)));
        }

        /**
         * arrayToKv  数组转键值对
         * @param array $array
         * @param string $delimiter
         * @return string
         * author: liuml  <liumenglei0211@163.com>
         * DateTime: 2018/9/13  15:43
         */
        private function arrayToKv(array $array, $delimiter = ',')
        {
            $str = '';
            array_walk($array, function ($v, $k, $delimiter) use (&$str) {
                $str .= "{$k}={$v}{$delimiter}";
            }, $delimiter);
            return rtrim($str, $delimiter);
        }

        /**
         * keyToKV 将索引数组转为固定格式的关联数组
         * @param $key
         * @author   liuml  
         * @DateTime 2018/9/13  18:08
         */
        private function keyToKV($key)
        {
            $updateKeyVal = [];
            array_map(function($v) use (&$updateKeyVal) {
                // 更新数据的时候创建时间字段默认不需要更新，特殊要求的话需要调用setUpdateKey方法自定义需要更新的字段
                if ($v != $this->created_at) {
                    $updateKeyVal[$v] = "VALUES({$v})";
                }
            }, $key);
            return $updateKeyVal;
        }

        /**
         * setTimeField 设置自动添加的时间字段，默认created_at,updated_at,如果不要自动添加时间字段则传入空值或null就行
         * @param array $timeField
         * @return $this
         * @author   liuml 
         * @DateTime 2018/9/13  19:20
         */
        public function setTimeField(array $timeField)
        {
            $this->created_at = $timeField['create'] ?? '';
            $this->updated_at = $timeField['update'] ?? '';
            return $this;
        }

        public function setTimeFormat(string $format)
        {
            $this->timeFormat = $format;
            return $this;
        }

        /**
         * setInsertKey 设置批量更新的key
         * @param array $key
         * @return $this
         * author: liuml  
         * DateTime: 2018/9/13  15:33
         */
        public function setInsertKey($key)
        {
            if (is_string($key)) {
                $this->insertKey = $key;
                return $this;
            }
            if (is_array($key)) {
                $this->insertKey = implode(',', $key);
            } else {
                throw new \Exception('The setInsertKey function needs to pass in a string or an array');
            }
            return $this;
        }

        /**
         * setTable 设置表名
         * @param string $table
         * @return $this
         * author: liuml  
         * DateTime: 2018/9/13  15:33
         */
        public function setTable($table = '')
        {
            $this->table = $this->table ? : $table;
            return $this;
        }

        /**
         * sqlExec 执行sql语句
         * @return mixed
         * author: liuml  
         * DateTime: 2018/9/13  15:33
         */
        public function execSql()
        {
            $db = DB::reconnect();
            return $db->getPdo()->exec($this->waitSql);
        }

        /**
         * getSql
         * @return 将要执行的sql
         * @author   liuml  
         * @DateTime 2018/9/13  19:19
         */
        public function getSql()
        {
            return $this->waitSql;
        }


        /**
         * UpdateOrCreate 存在及更新，不存在即创建
         * @author   liuml  
         * @DateTime 2018/9/14  9:46
         */
        public function updateOrCreate(array $attributes, array $values)
        {
            return $this->model->updateOrCreate($attributes, $values);
        }

        /**
         * UpdateOrInsert 存在及更新，不存在即创建
         * @author   liuml  
         * @DateTime 2018/9/14  9:46
         */
        public function updateOrInsert(array $attributes, array $values)
        {
            return $this->model->updateOrInsert($attributes, $values);
        }

        /**
         * ObjectToArray 对象使用 toArray 转为数组
         * @param $obj
         * @return array
         * @author   liuml  
         * @DateTime 2018/9/20  10:28
         */
        public function ObjectToArray($obj)
        {
            if (is_object($obj)) {
                return collect($obj->items())->toArray();
            } else {
                return '';
            }

        }

        /**
         * listTimeToDate 列表时间戳转格式化时间
         * @param $data
         * @author   liuml  
         * @DateTime 2018/9/20  10:30
         */
        public function listTimeToDate(array $data, array $filed = ['created_at'], string $format = 'Y-m-d H:i:s')
        {
            array_walk_recursive($data, function(&$v, $k, $filed) use ($format) {
                if (in_array($k, $filed))
                    $v = date($format, $v);
                return $v;
            }, $filed);
            return $data;
        }


    }

// 示例。
    $er = new EloquentRepository();
    $data = [
        [
            'title' => '123',
            'details' => '123',
            'set_key' => '123',
            'set_value' => '123',
            'type' => '123',
            'created_at' => '123',
            'updated_at' => '123'
        ],
        [
            'title' => '12233',
            'details' => '123',
            'set_key' => '12334',
            'set_value' => '123',
            'type' => '123',
            'created_at' => '123',
            'updated_at' => '123'
        ]
    ];

    echo $er->setTable('system_config')->uOrCreate($data)->getSql();

    // INSERT INTO system_config (title,details,set_key,set_value,type,created_at,updated_at) values ("123","123","123","123","123","2018-11-20 06:06:46","2018-11-20 06:06:46"),("12233","123","12334","123","123","2018-11-20 06:06:46","2018-11-20 06:06:46") ON DUPLICATE KEY UPDATE title=VALUES(title),details=VALUES(details),set_key=VALUES(set_key),set_value=VALUES(set_value),type=VALUES(type),updated_at=VALUES(updated_at);