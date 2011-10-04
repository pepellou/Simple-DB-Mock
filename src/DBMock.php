<?php

include_once 'src/QueryAnalyzer.php';

class DBMock {
	
	var $data;

	public function __construct(
	) {
		$this->data = array();
		$this->autoinc = array();
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
				foreach ($this->data[$table] as $row) {
					if ($this->evalRow($row, $where)) {
						$data []= $row;
					}
				}
				if ($select == array('*'))
					return $data;
				else if ($select == array('COUNT', '(', '*', ')'))
					return count($data);
				else {
					print_r($select);
				}
		}
		return null;
	}

	private function evalRow(
		$row,
		$where
	) {
		switch ($where[0]) {
			case 'true':
				return true;
			case '=':
				return ($row[$where[1]] == $where[2]);
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
