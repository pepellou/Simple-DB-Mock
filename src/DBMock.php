<?php

include_once 'src/QueryAnalyzer.php';

class DBMock {
	
	var $data;
	var $autoinc;
	var $additionalFields;

	public function __construct(
	) {
		$this->data = array();
		$this->autoinc = array();
		$this->additionalFields = array();
	}

	public function setFields(
		$table,
		$fields
	) {
		$this->additionalFields[$table] = $fields;
	}

	public function getAdditionalFields(
		$table
	) {
		return (isset($this->additionalFields[$table]))
			? $this->additionalFields[$table]
			: array();
	}

	public function getData(
		$table
	) {
		$data = array();
		foreach ($this->data[$table] as $row) {
			foreach ($this->getAdditionalFields($table) as $field) {
				if (!isset($row[$field]))
					$row[$field] = null;
			}
			$data []= $row;
		}
		return $data;
	}

	public function query(
		$query
	) {
		$analysis = $this->analyze($query);
		$table = $analysis->table();
		if (!isset($this->data[$table])) {
			$this->data[$table] = array();
			$this->autoinc[$table] = 1;
		}
		switch ($analysis->type()) {
			case "insert":
				$this->data[$table][]= $this->newRow($analysis, $this->autoinc[$table]++);
				return true;
			case "select":
				$select = $analysis->selected_fields();
				$where = $analysis->where_condition();
				$data = array();
				foreach ($this->getData($table) as $row) {
					if ($this->evalRow($row, $where)) {
						$data []= $row;
					}
				}
				if ($select == array('*'))
					return $data;
				else if ($select[0] == 'MAX')
					return $this->getMax($data, $select[2]);
				else if ($select == array('COUNT', '(', '*', ')'))
					return count($data);
				else {
					print_r($select);
				}
				return null;
			case 'update':
				$data = array();
				$where = $analysis->where_condition();
				foreach ($this->getData($table) as $row) {
					if ($this->evalRow($row, $where)) {
						foreach($analysis->setters() as $field => $value) {
							$row[$field] = $value;
						}
					}
					$data []= $row;
				}
				$this->data[$table] = $data;
				return true;
		}
		return null;
	}

	private function getMax(
		$data,
		$field
	) {
		$max = "";
		foreach ($data as $row) {
			if ($row[$field] > $max)
				$max = $row[$field];
		}
		return $max;
	}

	private function evalRow(
		$row,
		$where
	) {
		switch ($where[0]) {
			case 'true':
				return true;
			case '=':
				return ($row[$where[1]] == $where[2]) || ("'{$row[$where[1]]}'" == $where[2]);
			case 'AND':
				return ($this->evalRow($where[1]) && $this->evalRow($where[2]));
			case 'OR':
				return ($this->evalRow($where[1]) || $this->evalRow($where[2]));
		}
		return false;
	}

	private function analyze(
		$query
	) {
		return new QueryAnalyzer($query);
	}

	private function newRow(
		$analyzer,
		$id
	) {
		$fields = $analyzer->selected_fields();
		$values = $analyzer->values();
		$row = array("id" => $id);
		for ($pos = 0; $pos < count($fields); $pos++) {
			$row[$fields[$pos]] = $values[$pos];
		}
		return $row;
	}

}

?>
