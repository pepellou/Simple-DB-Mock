<?php

include_once 'src/QueryAnalyzer.php';

class DBMock {
	
	var $data;
	var $autoinc;
	var $additionalFields;
	var $autoIncTables;

	public function __construct(
	) {
		$this->data = array();
		$this->autoinc = array();
		$this->additionalFields = array();
		$this->autoIncTables = array();
	}

	public function addAutoInc(
		$table
	) {
		$this->autoIncTables []= $table;
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

	private function getDataSingleTable(
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

	private function getDataJoinTables(
		$tables
	) {
		$datas = array();
		foreach ($tables as $table) {
			$datas[$table] = $this->getDataSingleTable($table);
		}
		return $this->mergeRows($datas);
	}

	private function mergeRowsTable(
		$previous,
		$news,
		$table
	) {
		$results = array();
		foreach ($previous as $rowPrevious) {
			foreach ($news as $rowNext) {
				$results []= array_merge($rowPrevious, $rowNext);
			}
		}
		return $results;
	}

	private function findDuplicatedFields(
		$datas
	) {
		$duplicated = array();
		$fields = array();
		foreach ($datas as $table => $rows) {
			if (isset($rows[0])) {
				foreach ($rows[0] as $field => $value) {
					if (in_array($field, $fields))
						if (!in_array($field, $duplicated))
							$duplicated []= $field;
					$fields []= $field;
				}
			}
		}
		return $duplicated;
	}
	
	private function removeDuplicated(
		$rows,
		$duplicated,
		$table
	) {
		$results = array();
		foreach ($rows as $row) {
			$newRow = array();
			foreach ($row as $field => $value) {
				if (in_array($field, $duplicated))
					$field = "$table.$field";
				$newRow[$field] = $value;
			}
			$results []= $newRow;
		}
		return $results;
	}

	private function removeDuplicatedFields(
		$datas
	) {
		$results = array();
		$duplicatedFields = $this->findDuplicatedFields($datas);
		foreach ($datas as $table => $rows) {
			$results[$table] = $this->removeDuplicated($rows, $duplicatedFields, $table);
		}
		return $results;
	}

	private function mergeRows(
		$datas
	) {
		$results = array();
		$datas = $this->removeDuplicatedFields($datas);
		foreach ($datas as $table => $rows) {
			if (count($results) == 0) {
				$results = $rows;
			} else {
				$temp = $this->mergeRowsTable($results, $rows, $table);
				$results = $temp;
			}
		}
		return $results;
	}

	public function getData(
		$table
	) {
		return (is_array($table))
			? $this->getDataJoinTables($table)
			: $this->getDataSingleTable($table);
	}

	private function initTable(
		$table
	) {
		if (is_array($table)) {
			foreach($table as $t) {
				$this->initTable($t);
			}
		} else {
			if (!isset($this->data[$table])) {
				$this->data[$table] = array();
				$this->autoinc[$table] = 1;
			}
		}
	}
	
	private function getAutoInc(
		$table
	) {
		return (in_array($table, $this->autoIncTables))
			? $this->autoinc[$table]++
			: null;
	}

	public function query(
		$query
	) {
		$analysis = $this->analyze($query);
		$table = $analysis->table();
		$this->initTable($table);
		switch ($analysis->type()) {
			case "insert":
				$this->data[$table][]= $this->newRow($analysis, $this->getAutoInc($table));
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
					// TODO: process selections
					return $data;
				}
				return null;
			case 'update':
				$data = array();
				$where = $analysis->where_condition();
				foreach ($this->getData($table) as $row) {
					if ($this->evalRow($row, $where)) {
						foreach($analysis->setters() as $field => $value) {
							$row[$field] = $this->trim($value);
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

	private function isString(
		$string
	) {
		$string = trim($string);
		$first = substr($string, 0, 1);
		$last = substr($string, strlen($string) - 1);
		return (($first == $last) && in_array($first, array("\"", "'")));
	}

	private function getFieldName(
		$name
	) {
		$words = split("\.", $name);
		return $words[count($words) - 1];
	}

	private function evalFieldName(
		$row,
		$name
	) {
		return (isset($row[$name]))
			? $row[$name]
			: $row[$this->getFieldName($name)];
	}

	private function evalRow(
		$row,
		$where
	) {
		switch ($where[0]) {
			case 'true':
				return true;
			case '=':
				$left = $where[1];
				$right = $where[2];
				$val1 = (is_numeric($left))
					? $left
					: ($this->isString($left))
						? $this->trim($left)
						: $this->evalFieldName($row, $left);
				$val2 = (is_numeric($right))
					? $right
					: ($this->isString($right))
						? $this->trim($right)
						: $this->evalFieldName($row, $right);
				return $val1 == $val2;
			case 'AND':
				return ($this->evalRow($row, $where[1]) && $this->evalRow($row, $where[2]));
			case 'OR':
				return ($this->evalRow($row, $where[1]) || $this->evalRow($row, $where[2]));
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
		$id = null
	) {
		$fields = $analyzer->selected_fields();
		$values = $analyzer->values();
		$row = array();
		if ($id != null)
			$row["id"] = $id;
		for ($pos = 0; $pos < count($fields); $pos++) {
			$row[$fields[$pos]] = $this->trim($values[$pos]);
		}
		return $row;
	}

	private function trim(
		$value
	) {
		$value = trim($value);
		return ($this->isString($value))
			? $this->trim(substr($value, 1, strlen($value) - 2))
			: $value;
	}

}

?>
