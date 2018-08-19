<?php
namespace Common\Model;

use Think\Model;

class CommonModel extends Model {

	/**
	 * 插入数据
	 * @param array 插入的数据
	 */
	public function createData($data)
    {
		return $this->data($data)->add();
	}

	/**
	 * 获取一条数据
	 * @param $condition array | string 条件
	 * @param $field array | string 字段
	 */
	public function getOne($condition, $field = '*')
	{
		return $this->where($condition)->field($field)->find();
	}

	/**
	 * 获取一个字段值
	 * @param $condition array | string 条件
	 * @param $field string 字段
	 */
	public function getOneField($condition, $field = 'id')
	{
		return $this->where($condition)->getField($field);
	}

	/**
	 * 获取字段值得一列表
	 * @param $condition array | string 条件
	 * @param $field string 字段
	 */
	public function getAllField($condition, $field = 'id')
	{
		return $this->where($condition)->getField($field, true);
	}


	/**
	 * 获取所有数据
	 * @param $condition array | string 条件
	 * @param $field string 字段
	 */
	public function getAll($condition, $field = '*')
	{
		return $this->where($condition)->field($field)->select();
	}

	/**
	 * 获取字段的总数据统计
	 */
	public function sum($condition, $field = 'id')
	{
		$sum = $this->where($condition)->getField("sum({$field})");
		return $sum !== null ? $sum : 0 ;
	}

	/**
	 * 通过Id查询数据
	 * @param int id
	 * @param array | string 字段
	 */
	public function getDataById($id, $field = '*')
    {
		return $this->field($field)->find($id);
	}

	/**
	 * 更具条件更新数据
	 * @param $condition array | string 条件
	 * @param $data array 数据
	 */
	public function updateData($condition, $data)
	{
		return $this->where($condition)->data($data)->save() !== false ?  true : false;
	}

	/**
	 * 更新字段值加一
	 * @param $condition array | string 条件
	 * @param $field string 增值字段
	 * @param $inc int 增值数值
	 */
	public function setFieldInc($condition, $field, $inc = 1)
	{
		return $this->wehre($condition)->setInc($field, $inc);
	}

	/**
	 * 更新字段值减一
	 * @param $condition array | string 条件
	 * @param $field string 减值字段
	 * @param $inc int 减值值数值
	 */
	public function setFieldDec($condition, $field, $inc = 1)
	{
		return $this->wehre($condition)->setDec($field, $inc);
	}

	/**
	 * 查询总数据条数
	 * @param $condition array | string 条件
	 */
	public function getCount($condition)
	{
		return $this->where($condition)->count('id');
	}

	/**
	 * 验证是否有符合的数据
	 * @param $condition array | string 条件
	 */
	public function isExist($condition)
	{
		return $this->getCount($condition) > 0 ? true : false;
	}

	/**
	 * 查询多条数据
	 * @param $where array | string 条件
	 * @param $field array | string 查询字段
	 * @param $order array | string 排序
	 * @param $offset  开始位置
	 * @param $limit int 查询条数
	 */
	public function getList($where, $offset = 0, $limit = 25, $field = '*', $order = 'id')
	{
		return $this->where($where)->field($field)->order($order)->limit($offset, $limit)->select();
	}


}