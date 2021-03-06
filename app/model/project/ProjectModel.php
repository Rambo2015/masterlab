<?php

namespace main\app\model\project;

use main\app\classes\ProjectLogic;
use main\app\model\CacheModel;

/**
 *   项目模型
 */
class ProjectModel extends CacheModel
{
    public $prefix = 'project_';
    public $table = 'main';
    const   DATA_KEY = 'project_main/';

    protected static $instance;

    public function __construct($uid = '', $persistent = false)
    {
        parent::__construct($uid, $persistent);
        $this->uid = $uid;
    }

    public static function getInstance($persistent = false)
    {
        $index = intval($persistent);
        if (!isset(self::$instance[$index]) || !is_object(self::$instance[$index])) {
            self::$instance[$index]  = new self($persistent);
        }
        return self::$instance[$index] ;
    }

    /**
     * 获取项目总数
     * @return number
     */
    public function getAllCount()
    {
        $field = "count(*) as cc ";
        return (int)$this->getField($field, ['archived' => 'N']);
    }

    public function getAll($primaryKey = true, $fields = '*')
    {
        if ($fields == '*') {
            $table = $this->getTable();
            $fields = " id as k,{$table}.*";
        }
        return $this->getRows($fields, array('archived' => 'N'), null, 'id', 'desc', null, $primaryKey);
    }

    public function getAll2($primaryKey = true,  $fields = '*')
    {
        if ($fields == '*') {
            $table = $this->getTable();
            $fields = " id as k,{$table}.*";
        }
        return $this->getRows($fields, [], null, null, null, null, $primaryKey);
    }

    public function filterByType($typeId, $primaryKey = false, $fields = '*')
    {
        if ($fields == '*') {
            $table = $this->getTable();
            $fields = " id as k,{$table}.*";
        }
        return $this->getRows($fields, array('type' => $typeId, 'archived' => 'N'), null, 'id', 'desc', null, $primaryKey);
    }

    /**
     * 通过名字搜索
     * @param $keyword
     * @param string $orderBy
     * @param string $sort
     * @return array
     */
    public function filterByNameOrKey($keyword, $orderBy = 'id', $sort = 'desc')
    {
        $table = $this->getTable();
        $params = array();
        $where = wrapBlank("WHERE (`name` LIKE '%$keyword%' OR `key` LIKE '%$keyword%') AND archived='N' ");
        $orderBy = " ORDER BY $orderBy $sort";
        $sql = "SELECT * FROM " . $table . $where . $orderBy;
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 通过名字搜索(带所属模板)
     * @param $keyword
     * @param $tplId
     * @param string $orderBy
     * @param string $sort
     * @return mixed[]
     */
    public function filterByNameOrKeyAndTpl($keyword, $tplId, $orderBy = 'id', $sort = 'desc')
    {
        $table = $this->getTable();
        $params = array();
        $where = wrapBlank("WHERE `project_tpl_id`=$tplId AND (`name` LIKE '%$keyword%' OR `key` LIKE '%$keyword%') AND archived='N' ");
        $orderBy = " ORDER BY $orderBy $sort";
        $sql = "SELECT * FROM " . $table . $where . $orderBy;
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * @param $page
     * @param $page_size
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getFilter($page, $page_size)
    {
        $table = $this->prefix . $this->table;
        $params = array();

        $sqlCount = "SELECT count(id) as cc FROM {$table} ";
        $total = $this->getFieldBySql($sqlCount, $params);

        $start = $page_size * ($page - 1);
        $limit = wrapBlank("LIMIT {$start}, " . $page_size);
        $order = wrapBlank("ORDER BY id DESC");
        $where = wrapBlank("WHERE archived='N' ");
        $where .= $order . $limit;
        $sql = "SELECT * FROM " . $table . $where;
        $rows = $this->db->fetchAll($sql, $params);

        return array($rows, $total);
    }

    /**
     * 更新记录
     * @param $id
     * @param $row
     * @return array
     * @throws \Exception
     */
    public function updateById($updateInfo, $projectId)
    {
        $where = ['id' => $projectId];
        $flag = $this->update($updateInfo, $where);
        return $flag;
    }

    /**
     * @param $projectId
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getKeyById($projectId)
    {
        $table = $this->getTable();
        $fields = "`key`";

        $sql = "SELECT {$fields}  FROM {$table} Where id= {$projectId} ";
        $key = $this->getFieldBySql($sql);
        return $key;
    }

    /**
     * @param $projectId
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getById($projectId)
    {
        $fields = "*";
        $where = ['id' => $projectId];
        $row = $this->getRow($fields, $where);
        return $row;
    }

    /**
     * @param $projectId
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNameById($projectId)
    {
        $fields = "name";
        $where = ['id' => $projectId];
        $row = $this->getRow($fields, $where);
        return $row;
    }

    /**
     * @param $projectId
     * @return false|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getFieldNameById($projectId)
    {
        $field = "name";
        $where = ['id' => $projectId];
        $row = $this->getField($field, $where);
        return $row;
    }

    /**
     * @param $key
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getByKey($key)
    {
        $fields = "*,{$this->primaryKey} as k";
        $where = ['key' => trim($key)];
        $row = $this->getRow($fields, $where);
        return $row;
    }

    /**
     * @param $name
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getByName($name)
    {
        $fields = "*,{$this->primaryKey} as k";
        $where = ['name' => $name];
        $row = $this->getRow($fields, $where);
        return $row;
    }

    /**
     * @param $originId
     * @return array
     */
    public function getsByOrigin($originId)
    {
        $fields = "*";
        $where = ['org_id' => $originId];
        $rows = $this->getRows($fields, $where);
        return $rows;
    }

    /**
     * @param $name
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function checkNameExist($name)
    {
        $fields = "count(*) as cc";
        $where = ['name' => $name];
        $count = $this->getField($fields, $where);
        return $count > 0;
    }

    /**
     * @param $id
     * @param $name
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function checkIdNameExist($id, $name)
    {
        $table = $this->getTable();
        $conditions['id'] = $id;
        $conditions['name'] = $name;
        $sql = "SELECT count(*) as cc  FROM {$table} Where id!=:id AND name=:name  ";
        $count = $this->getFieldBySql($sql, $conditions);
        return $count > 0;
    }

    /**
     * @param $key
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function checkKeyExist($key)
    {
        $fields = "count(*) as cc";
        $where = ['key' => $key];
        $count = $this->getField($fields, $where);
        return $count > 0;
    }

    /**
     * @param $id
     * @param $key
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function checkIdKeyExist($id, $key)
    {
        $table = $this->getTable();
        $conditions['id'] = $id;
        $conditions['key'] = $key;
        $sql = "SELECT count(*) as cc  FROM {$table} Where id!=:id AND `key`=:key  ";
        $count = $this->getFieldBySql($sql, $conditions);
        return $count > 0;
    }

    /**
     * 通过项目ID数组来获取项目信息
     * @param $projectIdArr
     * @return array
     */
    public function getProjectsByIdArr($projectIdArr)
    {
        $idInString = implode(",", $projectIdArr);

        $table = $this->getTable();
        $params = array();

        $where = wrapBlank("WHERE id IN (");
        $where .= $idInString.wrapBlank(")");
        $sql = "SELECT * FROM " . $table . $where;
        $rows = $this->db->fetchAll($sql, $params);

        return $rows;
    }

    /**
     * 获取所有项目的简单信息
     * @return array
     */
    public function getAllByFields($fields)
    {
        //$fields = 'id,org_id,org_path,name,url,key';
        return $this->getRows($fields);
    }
}
